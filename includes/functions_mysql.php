<?php
require_once 'database.php';

/**
 * Fonctions MySQL pour remplacer le système JSON
 * Version optimisée avec base de données MySQL
 */

/**
 * Génère un ID unique
 * @return string
 */
function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

/**
 * Récupère les données d'un utilisateur
 * @param string $user_id ID de l'utilisateur
 * @return array|null Données de l'utilisateur ou null si non trouvé
 */
function getUserData($user_id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM " . DB_PREFIX . "users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log('Erreur getUserData: ' . $e->getMessage());
        return null;
    }
}

/**
 * Sauvegarde les données d'un utilisateur
 * @param array $user_data Données de l'utilisateur
 * @return bool Succès ou échec
 */
function saveUserData($user_data) {
    try {
        $pdo = getDB();
        
        // Vérifier si l'utilisateur existe déjà
        $existing = getUserData($user_data['id']);
        
        if ($existing) {
            // Mise à jour
            $stmt = $pdo->prepare("
                UPDATE " . DB_PREFIX . "users 
                SET email = ?, nom = ?, prenom = ?, password_hash = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([
                $user_data['email'],
                $user_data['nom'],
                $user_data['prenom'],
                $user_data['password_hash'],
                $user_data['id']
            ]);
        } else {
            // Insertion
            $stmt = $pdo->prepare("
                INSERT INTO " . DB_PREFIX . "users 
                (id, email, nom, prenom, password_hash, date_inscription) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $user_data['id'],
                $user_data['email'],
                $user_data['nom'],
                $user_data['prenom'],
                $user_data['password_hash'],
                $user_data['date_inscription']
            ]);
        }
    } catch (PDOException $e) {
        error_log('Erreur saveUserData: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les données d'une commande
 * @param string $commande_id ID de la commande
 * @return array|null Données de la commande ou null si non trouvée
 */
function getCommandeData($commande_id) {
    try {
        $pdo = getDB();
        
        // Récupérer la commande
        $stmt = $pdo->prepare("SELECT * FROM " . DB_PREFIX . "commandes WHERE id = ?");
        $stmt->execute([$commande_id]);
        $commande = $stmt->fetch();
        
        if (!$commande) return null;
        
        // Récupérer les produits et variations
        $stmt = $pdo->prepare("
            SELECT p.*, v.id as variation_id, v.nom as variation_nom, v.poids, v.prix
            FROM " . DB_PREFIX . "produits p
            LEFT JOIN " . DB_PREFIX . "variations v ON p.id = v.produit_id
            WHERE p.commande_id = ?
            ORDER BY p.created_at, v.created_at
        ");
        $stmt->execute([$commande_id]);
        $produits_data = $stmt->fetchAll();
        
        // Organiser les produits et variations
        $produits = [];
        foreach ($produits_data as $row) {
            $produit_id = $row['id'];
            
            if (!isset($produits[$produit_id])) {
                $produits[$produit_id] = [
                    'id' => $row['id'],
                    'nom' => $row['nom'],
                    'url' => $row['url'],
                    'variations' => []
                ];
            }
            
            if ($row['variation_id']) {
                $produits[$produit_id]['variations'][] = [
                    'id' => $row['variation_id'],
                    'nom' => $row['variation_nom'],
                    'poids' => (float) $row['poids'],
                    'prix' => (float) $row['prix']
                ];
            }
        }
        
        // Récupérer les participants
        $stmt = $pdo->prepare("
            SELECT p.*, u.prenom, u.nom, u.email
            FROM " . DB_PREFIX . "participants p
            LEFT JOIN " . DB_PREFIX . "users u ON p.user_id = u.id
            WHERE p.commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        $participants_data = $stmt->fetchAll();
        
        $participants = [];
        foreach ($participants_data as $participant) {
            // Récupérer les articles commandés
            $stmt = $pdo->prepare("
                SELECT ac.*, v.nom as variation_nom, p.nom as produit_nom
                FROM " . DB_PREFIX . "articles_commandes ac
                JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
                JOIN " . DB_PREFIX . "produits p ON v.produit_id = p.id
                WHERE ac.participant_id = ?
            ");
            $stmt->execute([$participant['id']]);
            $articles = $stmt->fetchAll();
            
            $participants[] = [
                'user_id' => $participant['user_id'],
                'commandes' => array_map(function($article) {
                    return [
                        'variation_id' => $article['variation_id'],
                        'quantite' => (int) $article['quantite']
                    ];
                }, $articles),
                'montant_produits' => (float) $participant['montant_produits'],
                'part_frais_port' => (float) $participant['part_frais_port'],
                'montant_total' => (float) $participant['montant_total'],
                'statut_paiement' => (bool) $participant['statut_paiement']
            ];
        }
        
        // Récupérer les paliers de frais
        $stmt = $pdo->prepare("SELECT * FROM " . DB_PREFIX . "paliers_frais WHERE commande_id = ? ORDER BY min_value");
        $stmt->execute([$commande_id]);
        $paliers = $stmt->fetchAll();
        
        $paliers_frais = array_map(function($palier) {
            return [
                'min' => (float) $palier['min_value'],
                'max' => $palier['max_value'] ? (float) $palier['max_value'] : null,
                'frais' => (float) $palier['frais']
            ];
        }, $paliers);
        
        // Construire le tableau final
        $commande['produits'] = array_values($produits);
        $commande['participants'] = $participants;
        $commande['paliers_frais'] = $paliers_frais;
        $commande['montant_total'] = (float) $commande['montant_total'];
        $commande['poids_total'] = (float) $commande['poids_total'];
        $commande['frais_port'] = (float) $commande['frais_port'];
        $commande['public'] = (bool) $commande['public'];
        
        return $commande;
        
    } catch (PDOException $e) {
        error_log('Erreur getCommandeData: ' . $e->getMessage());
        return null;
    }
}

/**
 * Sauvegarde les données d'une commande
 * @param array $commande_data Données de la commande
 * @return bool Succès ou échec
 */
function saveCommandeData($commande_data) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();
        
        // Vérifier si la commande existe
        $existing = getCommandeData($commande_data['id']);
        
        if ($existing) {
            // Mise à jour de la commande
            $stmt = $pdo->prepare("
                UPDATE " . DB_PREFIX . "commandes 
                SET titre = ?, description = ?, type_commande = ?, date_limite = ?, 
                    date_recuperation = ?, adresse_recuperation = ?, montant_total = ?, 
                    poids_total = ?, frais_port = ?, public = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $commande_data['titre'],
                $commande_data['description'] ?? null,
                $commande_data['type_commande'],
                $commande_data['date_limite'],
                $commande_data['date_recuperation'],
                $commande_data['adresse_recuperation'],
                $commande_data['montant_total'],
                $commande_data['poids_total'],
                $commande_data['frais_port'],
                $commande_data['public'] ? 1 : 0,
                $commande_data['id']
            ]);
        } else {
            // Insertion de la commande
            $stmt = $pdo->prepare("
                INSERT INTO " . DB_PREFIX . "commandes 
                (id, titre, admin_id, description, type_commande, date_creation, 
                 date_limite, date_recuperation, adresse_recuperation, montant_total, 
                 poids_total, frais_port, public) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $commande_data['id'],
                $commande_data['titre'],
                $commande_data['admin_id'],
                $commande_data['description'] ?? null,
                $commande_data['type_commande'],
                $commande_data['date_creation'],
                $commande_data['date_limite'],
                $commande_data['date_recuperation'],
                $commande_data['adresse_recuperation'],
                $commande_data['montant_total'],
                $commande_data['poids_total'],
                $commande_data['frais_port'],
                $commande_data['public'] ? 1 : 0
            ]);
        }
        
        // Sauvegarder les produits et variations
        saveCommandeProduits($commande_data['id'], $commande_data['produits'] ?? []);
        
        // Sauvegarder les participants
        saveCommandeParticipants($commande_data['id'], $commande_data['participants'] ?? []);
        
        // Sauvegarder les paliers de frais
        saveCommandePaliers($commande_data['id'], $commande_data['paliers_frais'] ?? []);
        
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur saveCommandeData: ' . $e->getMessage());
        return false;
    }
}

/**
 * Sauvegarde les produits d'une commande
 * @param string $commande_id ID de la commande
 * @param array $produits Liste des produits
 */
function saveCommandeProduits($commande_id, $produits) {
    try {
        $pdo = getDB();
        
        // Supprimer les anciens produits
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "produits WHERE commande_id = ?");
        $stmt->execute([$commande_id]);
        
        // Insérer les nouveaux produits
        foreach ($produits as $produit) {
            $produit_id = $produit['id'] ?? generateUniqueId();
            
            $stmt = $pdo->prepare("
                INSERT INTO " . DB_PREFIX . "produits (id, commande_id, nom, url) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $produit_id,
                $commande_id,
                $produit['nom'],
                $produit['url'] ?? null
            ]);
            
            // Insérer les variations
            foreach ($produit['variations'] as $variation) {
                $variation_id = $variation['id'] ?? generateUniqueId();
                
                $stmt = $pdo->prepare("
                    INSERT INTO " . DB_PREFIX . "variations (id, produit_id, nom, poids, prix) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $variation_id,
                    $produit_id,
                    $variation['nom'],
                    $variation['poids'],
                    $variation['prix']
                ]);
            }
        }
    } catch (PDOException $e) {
        error_log('Erreur saveCommandeProduits: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Sauvegarde les participants d'une commande
 * @param string $commande_id ID de la commande
 * @param array $participants Liste des participants
 */
function saveCommandeParticipants($commande_id, $participants) {
    try {
        $pdo = getDB();
        
        // Supprimer les anciens participants
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "participants WHERE commande_id = ?");
        $stmt->execute([$commande_id]);
        
        // Insérer les nouveaux participants
        foreach ($participants as $participant) {
            $participant_id = generateUniqueId();
            
            $stmt = $pdo->prepare("
                INSERT INTO " . DB_PREFIX . "participants 
                (id, commande_id, user_id, montant_produits, part_frais_port, montant_total, statut_paiement) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $participant_id,
                $commande_id,
                $participant['user_id'],
                $participant['montant_produits'],
                $participant['part_frais_port'],
                $participant['montant_total'],
                $participant['statut_paiement'] ? 1 : 0
            ]);
            
            // Insérer les articles commandés
            foreach ($participant['commandes'] as $article) {
                $article_id = generateUniqueId();
                
                $stmt = $pdo->prepare("
                    INSERT INTO " . DB_PREFIX . "articles_commandes 
                    (id, participant_id, variation_id, quantite) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $article_id,
                    $participant_id,
                    $article['variation_id'],
                    $article['quantite']
                ]);
            }
        }
    } catch (PDOException $e) {
        error_log('Erreur saveCommandeParticipants: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Sauvegarde les paliers de frais d'une commande
 * @param string $commande_id ID de la commande
 * @param array $paliers Liste des paliers
 */
function saveCommandePaliers($commande_id, $paliers) {
    try {
        $pdo = getDB();
        
        // Supprimer les anciens paliers
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "paliers_frais WHERE commande_id = ?");
        $stmt->execute([$commande_id]);
        
        // Insérer les nouveaux paliers
        foreach ($paliers as $palier) {
            $palier_id = generateUniqueId();
            
            $stmt = $pdo->prepare("
                INSERT INTO " . DB_PREFIX . "paliers_frais 
                (id, commande_id, min_value, max_value, frais) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $palier_id,
                $commande_id,
                $palier['min'],
                $palier['max'],
                $palier['frais']
            ]);
        }
    } catch (PDOException $e) {
        error_log('Erreur saveCommandePaliers: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Vérifie si une commande est fermée (date limite dépassée)
 * @param array $commande Données de la commande
 * @return bool Vrai si la commande est fermée
 */
function isCommandeClosed($commande) {
    $date_limite = new DateTime($commande['date_limite']);
    $now = new DateTime();
    return $now > $date_limite;
}

/**
 * Valide le format d'une adresse email
 * @param string $email Adresse email
 * @return bool Vrai si l'email est valide
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Vérifie si un utilisateur existe déjà avec cette adresse email
 * @param string $email Adresse email
 * @return bool Vrai si l'email existe déjà
 */
function emailExists($email) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM " . DB_PREFIX . "users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        error_log('Erreur emailExists: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère l'ID d'un utilisateur à partir de son email
 * @param string $email Adresse email
 * @return string|null ID de l'utilisateur ou null si non trouvé
 */
function getUserIdByEmail($email) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM " . DB_PREFIX . "users WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        error_log('Erreur getUserIdByEmail: ' . $e->getMessage());
        return null;
    }
}

/**
 * Crée un utilisateur
 * @param string $email Email
 * @param string $password Mot de passe
 * @param string $nom Nom
 * @param string $prenom Prénom
 * @return array|null Données de l'utilisateur ou null si échec
 */
function createUser($email, $password, $nom, $prenom) {
    if (!isValidEmail($email)) {
        return null;
    }
    
    if (emailExists($email)) {
        return null;
    }
    
    $user_id = generateUniqueId();
    $user_data = [
        'id' => $user_id,
        'email' => $email,
        'nom' => $nom,
        'prenom' => $prenom,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'date_inscription' => (new DateTime())->format('Y-m-d H:i:s')
    ];
    
    if (saveUserData($user_data)) {
        // Envoi email de bienvenue
        $subject = APP_NAME . ' - Bienvenue !';
        $message = "<html><body>";
        $message .= "<h1>Bienvenue sur " . APP_NAME . " !</h1>";
        $message .= "<p>Votre compte a été créé avec succès.</p>";
        $message .= "<p>Email : " . htmlspecialchars($email) . "</p>";
        $message .= "</body></html>";
        
        sendEmail($email, $subject, $message);
        
        return $user_data;
    }
    
    return null;
}

/**
 * Authentifie un utilisateur
 * @param string $email Email
 * @param string $password Mot de passe
 * @return array|null Données de l'utilisateur ou null si échec
 */
function authenticateUser($email, $password) {
    $user_id = getUserIdByEmail($email);
    if (!$user_id) {
        return null;
    }
    
    $user_data = getUserData($user_id);
    if (!$user_data) {
        return null;
    }
    
    if (password_verify($password, $user_data['password_hash'])) {
        return $user_data;
    }
    
    return null;
}

/**
 * Envoie un email
 * @param string $to Destinataire
 * @param string $subject Sujet
 * @param string $message Message
 * @return bool Succès ou échec
 */
function sendEmail($to, $subject, $message) {
    $headers = "From: " . EMAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Récupère les commandes publiques
 * @return array Liste des commandes publiques
 */
function getPublicCommandes() {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT c.*, u.prenom, u.nom 
            FROM " . DB_PREFIX . "commandes c
            LEFT JOIN " . DB_PREFIX . "users u ON c.admin_id = u.id
            WHERE c.public = 1 AND c.date_limite > NOW()
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Erreur getPublicCommandes: ' . $e->getMessage());
        return [];
    }
}

/**
 * Formate une date au format lisible
 * @param string $date Date au format ISO
 * @return string Date formatée
 */
function formatDate($date) {
    if (empty($date)) return '';
    
    $date_obj = new DateTime($date);
    return $date_obj->format('d/m/Y H:i');
}
?>
