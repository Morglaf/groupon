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
        
        if (!$commande) {
            error_log("getCommandeData: Commande $commande_id non trouvée");
            return null;
        }
        
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
        error_log("getCommandeData: Participants trouvés: " . count($participants_data));
        
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
            
            $participants[$participant['user_id']] = [
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
        
        error_log("getCommandeData: Succès pour commande $commande_id");
        return $commande;
        
    } catch (PDOException $e) {
        error_log('Erreur getCommandeData: ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());
        return null;
    } catch (Exception $e) {
        error_log('Erreur générale getCommandeData: ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());
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
function createUser($prenom, $nom, $email, $password, $telephone = null) {
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
        'telephone' => $telephone,
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
    // Vérifier si les paramètres sont valides
    if (empty($to) || empty($subject) || empty($message)) {
        error_log('Erreur sendEmail: Paramètres manquants');
        return false;
    }
    
    // Vérifier si l'email de destination est valide
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('Erreur sendEmail: Email de destination invalide: ' . $to);
        return false;
    }
    
    // Préparer les en-têtes
    $headers = [];
    $headers[] = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">";
    $headers[] = "Reply-To: " . SMTP_FROM_EMAIL;
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    $headers_string = implode("\r\n", $headers);
    
    // Si SMTP est configuré avec authentification, utiliser PHPMailer ou une solution SMTP
    if (SMTP_AUTH && !empty(SMTP_USERNAME) && !empty(SMTP_PASSWORD)) {
        // Pour une configuration SMTP avancée, on pourrait utiliser PHPMailer
        // Pour l'instant, on utilise la fonction mail() avec les en-têtes SMTP
        error_log('Configuration SMTP avec authentification détectée - utilisation de mail() basique');
    }
    
    // Utiliser la fonction mail() de PHP
    $result = mail($to, $subject, $message, $headers_string);
    
    if (!$result) {
        error_log('Erreur sendEmail: Échec de l\'envoi vers ' . $to);
    } else {
        error_log('Email envoyé avec succès vers ' . $to);
    }
    
    return $result;
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

/**
 * Récupère les commandes créées par un utilisateur
 * @param string $user_id ID de l'utilisateur
 * @return array Liste des commandes
 */
function getUserCommandesAdmin($user_id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT * FROM " . DB_PREFIX . "commandes 
            WHERE admin_id = ? 
            ORDER BY date_creation DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Erreur getUserCommandesAdmin: ' . $e->getMessage());
        return [];
    }
}

/**
 * Récupère les commandes où un utilisateur est participant
 * @param string $user_id ID de l'utilisateur
 * @return array Liste des commandes
 */
function getUserCommandesParticipant($user_id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT c.* 
            FROM " . DB_PREFIX . "commandes c
            JOIN " . DB_PREFIX . "participants p ON c.id = p.commande_id
            WHERE p.user_id = ? 
            ORDER BY c.date_creation DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Erreur getUserCommandesParticipant: ' . $e->getMessage());
        return [];
    }
}

/**
 * Génère un PDF de la commande avec TCPDF
 * @param string $commande_id ID de la commande
 * @return string|null Chemin du fichier PDF généré ou null si échec
 */
function generateCommandePDF($commande_id) {
    try {
        // Vérifier si TCPDF est disponible
        if (!class_exists('TCPDF')) {
            error_log('TCPDF non disponible pour generateCommandePDF');
            return null;
        }
        
        $commande_data = getCommandeData($commande_id);
        if (!$commande_data) {
            return null;
        }
        
        // Créer une instance TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Informations du document
        $app_name = defined('APP_NAME') ? APP_NAME : 'Groupon';
        $pdf->SetCreator($app_name);
        $pdf->SetAuthor($app_name);
        $pdf->SetTitle('Commande: ' . $commande_data['titre']);
        $pdf->SetSubject('Export de commande groupée');
        
        // Marges
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Police par défaut
        $pdf->SetFont('helvetica', '', 10);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Titre
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RÉSUMÉ ENTREPRISE - ' . strtoupper($commande_data['titre']), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Informations générales
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Informations générales', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $info = [
            'Type de commande' => ucfirst($commande_data['type_commande']),
            'Date limite' => formatDate($commande_data['date_limite']),
            'Date de récupération' => formatDate($commande_data['date_recuperation']),
            'Adresse de récupération' => $commande_data['adresse_recuperation'],
            'Montant total' => number_format($commande_data['montant_total'], 2, ',', ' ') . ' €',
            'Poids total' => number_format($commande_data['poids_total'], 2, ',', ' ') . ' kg',
            'Frais de port' => number_format($commande_data['frais_port'], 2, ',', ' ') . ' €'
        ];
        
        foreach ($info as $label => $value) {
            $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
            $pdf->Cell(0, 6, $value, 0, 1, 'L');
        }
        
        $pdf->Ln(5);
        
        // Résumé global des produits commandés
        if (!empty($commande_data['participants'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'RÉSUMÉ GLOBAL DES COMMANDES', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            
            // Calculer le total par variation
            $totaux_variations = [];
            foreach ($commande_data['participants'] as $participant) {
                if (!empty($participant['commandes'])) {
                    foreach ($participant['commandes'] as $article) {
                        $variation_id = $article['variation_id'];
                        if (!isset($totaux_variations[$variation_id])) {
                            $totaux_variations[$variation_id] = [
                                'quantite' => 0,
                                'variation_info' => null
                            ];
                        }
                        $totaux_variations[$variation_id]['quantite'] += $article['quantite'];
                        
                        // Récupérer les infos de la variation
                        if (!$totaux_variations[$variation_id]['variation_info']) {
                            foreach ($commande_data['produits'] as $produit) {
                                foreach ($produit['variations'] as $variation) {
                                    if ($variation['id'] == $variation_id) {
                                        $totaux_variations[$variation_id]['variation_info'] = [
                                            'produit_nom' => $produit['nom'],
                                            'variation_nom' => $variation['nom'],
                                            'prix' => $variation['prix']
                                        ];
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Afficher le résumé
            if (!empty($totaux_variations)) {
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(60, 5, 'Produit - Variation', 1, 0, 'C');
                $pdf->Cell(25, 5, 'Quantité', 1, 0, 'C');
                $pdf->Cell(25, 5, 'Prix unit.', 1, 0, 'C');
                $pdf->Cell(30, 5, 'Total', 1, 1, 'C');
                
                foreach ($totaux_variations as $variation_id => $data) {
                    $variation_info = $data['variation_info'];
                    if ($variation_info) {
                        $pdf->SetFont('helvetica', '', 8);
                        $pdf->Cell(60, 5, $variation_info['produit_nom'] . ' - ' . $variation_info['variation_nom'], 1, 0, 'L');
                        $pdf->Cell(25, 5, $data['quantite'], 1, 0, 'C');
                        $pdf->Cell(25, 5, number_format($variation_info['prix'], 2, ',', ' ') . ' €', 1, 0, 'R');
                        $pdf->Cell(30, 5, number_format($variation_info['prix'] * $data['quantite'], 2, ',', ' ') . ' €', 1, 1, 'R');
                    }
                }
            }
        }
        
        // Résumé par participant avec colis
        if (!empty($commande_data['participants'])) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'COLIS PAR PARTICIPANT', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            
            foreach ($commande_data['participants'] as $participant) {
                $user_data = getUserData($participant['user_id']);
                $user_name = $user_data ? $user_data['prenom'] . ' ' . $user_data['nom'] : 'Utilisateur #' . $participant['user_id'];
                
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(0, 6, 'Colis: ' . $user_name, 0, 1);
                
                // Produits du colis
                if (!empty($participant['commandes'])) {
                    $pdf->SetFont('helvetica', 'B', 9);
                    $pdf->Cell(0, 4, 'Contenu du colis:', 0, 1);
                    
                    foreach ($participant['commandes'] as $article) {
                        // Trouver les infos de la variation
                        $variation_info = null;
                        foreach ($commande_data['produits'] as $produit) {
                            foreach ($produit['variations'] as $variation) {
                                if ($variation['id'] == $article['variation_id']) {
                                    $variation_info = [
                                        'produit_nom' => $produit['nom'],
                                        'variation_nom' => $variation['nom'],
                                        'prix' => $variation['prix']
                                    ];
                                    break 2;
                                }
                            }
                        }
                        
                        if ($variation_info) {
                            $pdf->SetFont('helvetica', '', 8);
                            $pdf->Cell(10, 4, '', 0, 0); // Indentation
                            $pdf->Cell(0, 4, '• ' . $variation_info['produit_nom'] . ' - ' . $variation_info['variation_nom'] . ' (x' . $article['quantite'] . ') - ' . number_format($variation_info['prix'] * $article['quantite'], 2, ',', ' ') . ' €', 0, 1);
                        }
                    }
                }
                
                // Total du colis
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(0, 4, 'Total colis: ' . number_format($participant['montant_total'], 2, ',', ' ') . ' €', 0, 1);
                $pdf->Cell(0, 4, 'Statut: ' . ($participant['statut_paiement'] ? 'PAYE' : 'NON PAYE'), 0, 1);
                
                $pdf->Ln(5);
            }
        }
        
        // Générer le nom de fichier
        $filename = 'commande_' . $commande_id . '_' . date('Ymd_His') . '.pdf';
        $file_path = DATA_DIR . '/exports/' . $filename;
        
        // Sauvegarder le PDF
        $pdf->Output($file_path, 'F');
        
        return $file_path;
        
    } catch (Exception $e) {
        error_log('Erreur generateCommandePDF: ' . $e->getMessage());
        return null;
    }
}

/**
 * Génère un PDF de checklist pour la commande avec cases à cocher
 * @param string $commande_id ID de la commande
 * @return string|null Chemin du fichier PDF généré ou null si échec
 */
function generateCommandeChecklistPDF($commande_id) {
    try {
        // Vérifier si TCPDF est disponible
        if (!class_exists('TCPDF')) {
            error_log('TCPDF non disponible pour generateCommandeChecklistPDF');
            return null;
        }
        
        $commande_data = getCommandeData($commande_id);
        if (!$commande_data) {
            return null;
        }
        
        // Créer une instance TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Informations du document
        $app_name = defined('APP_NAME') ? APP_NAME : 'Groupon';
        $pdf->SetCreator($app_name);
        $pdf->SetAuthor($app_name);
        $pdf->SetTitle('Checklist: ' . $commande_data['titre']);
        $pdf->SetSubject('Liste de contrôle - Commande groupée');
        
        // Marges
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(10);
        
        // Police par défaut
        $pdf->SetFont('helvetica', '', 10);
        
        // Ajouter une page
        $pdf->AddPage();
        
        // Titre
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'LISTE DE CONTRÔLE - ' . strtoupper($commande_data['titre']), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Informations générales
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Informations de la commande', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(40, 5, 'Date limite:', 0, 0);
        $pdf->Cell(0, 5, date('d/m/Y', strtotime($commande_data['date_limite'])), 0, 1);
        
        $pdf->Cell(40, 5, 'Récupération:', 0, 0);
        $pdf->Cell(0, 5, date('d/m/Y', strtotime($commande_data['date_recuperation'])), 0, 1);
        
        $pdf->Cell(40, 5, 'Adresse:', 0, 0);
        $pdf->Cell(0, 5, $commande_data['adresse_recuperation'], 0, 1);
        
        $pdf->Ln(5);
        
        // Section Participants et Produits
        if (!empty($commande_data['participants'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'PARTICIPANTS ET PRODUITS', 0, 1);
            $pdf->Ln(2);
            
            foreach ($commande_data['participants'] as $participant) {
                $user_data = getUserData($participant['user_id']);
                $user_name = $user_data ? $user_data['prenom'] . ' ' . $user_data['nom'] : 'Utilisateur #' . $participant['user_id'];
                
                // Nom du participant avec case à cocher
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(5, 6, '[ ]', 0, 0); // Case à cocher simple
                $pdf->Cell(0, 6, $user_name, 0, 1);
                
                // Contact (email et téléphone)
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Cell(10, 4, '', 0, 0); // Indentation
                $pdf->Cell(0, 4, 'Email: ' . $user_data['email'], 0, 1);
                if (!empty($user_data['telephone'])) {
                    $pdf->Cell(10, 4, '', 0, 0); // Indentation
                    $pdf->Cell(0, 4, 'Tel: ' . $user_data['telephone'], 0, 1);
                }
                
                // Informations financières
                $pdf->SetFont('helvetica', '', 9);
                $pdf->Cell(10, 4, '', 0, 0); // Indentation
                $pdf->Cell(0, 4, 'Montant produits: ' . number_format($participant['montant_produits'], 2, ',', ' ') . ' €', 0, 1);
                
                if ($commande_data['type_commande'] !== 'sans_frais') {
                    $pdf->Cell(10, 4, '', 0, 0);
                    $pdf->Cell(0, 4, 'Part frais de port: ' . number_format($participant['part_frais_port'], 2, ',', ' ') . ' €', 0, 1);
                }
                
                $pdf->Cell(10, 4, '', 0, 0);
                $pdf->Cell(0, 4, 'Total: ' . number_format($participant['montant_total'], 2, ',', ' ') . ' €', 0, 1);
                
                // Statut paiement avec case à cocher
                $pdf->Cell(10, 4, '', 0, 0);
                $pdf->Cell(5, 4, $participant['statut_paiement'] ? '[X]' : '[ ]', 0, 0);
                $pdf->Cell(0, 4, ' Paiement reçu', 0, 1);
                
                // Produits du participant
                if (!empty($participant['commandes'])) {
                    $pdf->Cell(10, 4, '', 0, 0);
                    $pdf->SetFont('helvetica', 'B', 9);
                    $pdf->Cell(0, 4, 'Produits commandés:', 0, 1);
                    
                    foreach ($participant['commandes'] as $article) {
                        // Trouver les infos de la variation
                        $variation_info = null;
                        foreach ($commande_data['produits'] as $produit) {
                            foreach ($produit['variations'] as $variation) {
                                if ($variation['id'] == $article['variation_id']) {
                                    $variation_info = [
                                        'produit_nom' => $produit['nom'],
                                        'variation_nom' => $variation['nom'],
                                        'prix' => $variation['prix']
                                    ];
                                    break 2;
                                }
                            }
                        }
                        
                        if ($variation_info) {
                            $pdf->Cell(15, 4, '', 0, 0); // Indentation supplémentaire
                            $pdf->SetFont('helvetica', '', 8);
                            $pdf->Cell(5, 4, '[ ]', 0, 0); // Case à cocher pour le produit
                            $pdf->Cell(0, 4, $variation_info['produit_nom'] . ' - ' . $variation_info['variation_nom'] . ' (x' . $article['quantite'] . ') - ' . number_format($variation_info['prix'] * $article['quantite'], 2, ',', ' ') . ' €', 0, 1);
                        }
                    }
                }
                
                $pdf->Ln(3);
            }
        }
        
        $pdf->Ln(5);
        
        // Section Notes
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'Notes:', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        
        // Lignes pour les notes
        for ($i = 0; $i < 8; $i++) {
            $pdf->Cell(0, 4, '_________________________________________________', 0, 1);
        }
        
        // Générer le nom de fichier
        $filename = 'checklist_' . $commande_id . '_' . date('Ymd_His') . '.pdf';
        $file_path = DATA_DIR . '/exports/' . $filename;
        
        // Sauvegarder le PDF
        $pdf->Output($file_path, 'F');
        
        return $file_path;
        
    } catch (Exception $e) {
        error_log('Erreur generateCommandeChecklistPDF: ' . $e->getMessage());
        return null;
    }
}

/**
 * Récupère les données de l'utilisateur connecté
 * @return array|null Données de l'utilisateur ou null si non connecté
 */
function getCurrentUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return getUserData($_SESSION['user_id']);
}

/**
 * Met à jour le profil d'un utilisateur
 * @param string $user_id ID de l'utilisateur
 * @param array $data Données à mettre à jour
 * @return bool Succès ou échec
 */
function updateUserProfile($user_id, $data) {
    try {
        $pdo = getDB();
        
        // Vérifier si la colonne telephone existe
        $stmt = $pdo->query("SHOW COLUMNS FROM " . DB_PREFIX . "users LIKE 'telephone'");
        $column = $stmt->fetch();
        
        if ($column) {
            // Colonne telephone existe
            $stmt = $pdo->prepare("
                UPDATE " . DB_PREFIX . "users 
                SET prenom = ?, nom = ?, email = ?, telephone = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $data['prenom'],
                $data['nom'],
                $data['email'],
                $data['telephone'],
                $user_id
            ]);
        } else {
            // Colonne telephone n'existe pas encore
            $stmt = $pdo->prepare("
                UPDATE " . DB_PREFIX . "users 
                SET prenom = ?, nom = ?, email = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $data['prenom'],
                $data['nom'],
                $data['email'],
                $user_id
            ]);
        }
        
    } catch (PDOException $e) {
        error_log('Erreur updateUserProfile: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le mot de passe d'un utilisateur
 * @param string $user_id ID de l'utilisateur
 * @param string $new_password Nouveau mot de passe
 * @return bool Succès ou échec
 */
function updateUserPassword($user_id, $new_password) {
    try {
        $pdo = getDB();
        
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "users 
            SET password_hash = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$password_hash, $user_id]);
        
    } catch (PDOException $e) {
        error_log('Erreur updateUserPassword: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une commande et toutes ses données associées
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur (pour vérifier les droits)
 * @return bool Succès ou échec
 */
function deleteCommande($commande_id, $user_id) {
    try {
        $pdo = getDB();
        
        // Vérifier que l'utilisateur est admin de la commande
        if (!isCommandeAdmin($commande_id, $user_id)) {
            return false;
        }
        
        $pdo->beginTransaction();
        
        // Supprimer les articles commandés
        $stmt = $pdo->prepare("
            DELETE ac FROM " . DB_PREFIX . "articles_commandes ac
            JOIN " . DB_PREFIX . "participants p ON ac.participant_id = p.id
            WHERE p.commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        
        // Supprimer les participants
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "participants WHERE commande_id = ?");
        $stmt->execute([$commande_id]);
        
        // Supprimer les variations
        $stmt = $pdo->prepare("
            DELETE v FROM " . DB_PREFIX . "variations v
            JOIN " . DB_PREFIX . "produits p ON v.produit_id = p.id
            WHERE p.commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        
        // Supprimer les produits
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "produits WHERE commande_id = ?");
        $stmt->execute([$commande_id]);
        
        // Supprimer les paliers de frais
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "paliers_frais WHERE commande_id = ?");
        $stmt->execute([$commande_id]);
        
        // Supprimer la commande
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "commandes WHERE id = ?");
        $stmt->execute([$commande_id]);
        
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur deleteCommande: ' . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un utilisateur est admin d'une commande
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool
 */
function isCommandeAdmin($commande_id, $user_id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT admin_id FROM " . DB_PREFIX . "commandes WHERE id = ?");
        $stmt->execute([$commande_id]);
        $commande = $stmt->fetch();
        
        return $commande && $commande['admin_id'] === $user_id;
    } catch (PDOException $e) {
        error_log('Erreur isCommandeAdmin: ' . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un utilisateur est participant d'une commande
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool
 */
function isCommandeParticipant($commande_id, $user_id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM " . DB_PREFIX . "participants 
            WHERE commande_id = ? AND user_id = ?
        ");
        $stmt->execute([$commande_id, $user_id]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log('Erreur isCommandeParticipant: ' . $e->getMessage());
        return false;
    }
}

/**
 * Crée une nouvelle commande
 * @param string $user_id ID de l'utilisateur créateur
 * @param string $titre Titre de la commande
 * @param string $type_commande Type de commande (poids, nombre, montant)
 * @param string $date_limite Date limite
 * @param string $date_recuperation Date de récupération
 * @param string $adresse_recuperation Adresse de récupération
 * @param string $description Description de la commande
 * @param array $paliers Paliers de frais
 * @param array $produits Produits avec variations
 * @return string|null ID de la commande créée ou null si échec
 */
function createCommande($user_id, $titre, $type_commande, $date_limite, $date_recuperation, $adresse_recuperation, $description, $paliers, $produits) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();
        
        // Générer un ID unique pour la commande
        $commande_id = generateUniqueId();
        
        // Insérer la commande
        $stmt = $pdo->prepare("
            INSERT INTO " . DB_PREFIX . "commandes 
            (id, titre, admin_id, description, type_commande, date_creation, date_limite, 
             date_recuperation, adresse_recuperation, montant_total, poids_total, 
             frais_port, public) 
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, 0, 0, 0, 0)
        ");
        $stmt->execute([
            $commande_id,
            $titre,
            $user_id,
            $description,
            $type_commande,
            $date_limite,
            $date_recuperation,
            $adresse_recuperation
        ]);
        
        // Insérer les paliers de frais
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
        
        // Insérer les produits et leurs variations
        foreach ($produits as $produit) {
            $produit_id = generateUniqueId();
            
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
                $variation_id = generateUniqueId();
                
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
        
        $pdo->commit();
        return $commande_id;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur createCommande: ' . $e->getMessage());
        return null;
    }
}

/**
 * Exporte une commande au format JSON
 * @param string $commande_id ID de la commande
 * @return string|null Contenu JSON ou null en cas d'erreur
 */
function exportCommandeJSON($commande_id) {
    try {
        $commande_data = getCommandeData($commande_id);
        if (!$commande_data) {
            return null;
        }
        
        // Préparer les données pour l'export JSON
        $export_data = [
            'id' => $commande_data['id'],
            'titre' => $commande_data['titre'],
            'description' => $commande_data['description'],
            'type_commande' => $commande_data['type_commande'],
            'date_limite' => $commande_data['date_limite'],
            'date_recuperation' => $commande_data['date_recuperation'],
            'adresse_recuperation' => $commande_data['adresse_recuperation'],
            'montant_total' => $commande_data['montant_total'],
            'poids_total' => $commande_data['poids_total'],
            'frais_port' => $commande_data['frais_port'],
            'statut' => $commande_data['statut'],
            'created_at' => $commande_data['created_at'],
            'updated_at' => $commande_data['updated_at'],
            'admin_id' => $commande_data['admin_id'],
            'produits' => $commande_data['produits'],
            'paliers_frais' => $commande_data['paliers_frais'],
            'participants' => $commande_data['participants']
        ];
        
        return json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        error_log('Erreur exportCommandeJSON: ' . $e->getMessage());
        return null;
    }
}

/**
 * Importe une commande depuis un fichier JSON
 * @param string $json_content Contenu JSON de la commande
 * @param string $user_id ID de l'utilisateur qui importe
 * @return string|null ID de la nouvelle commande ou null en cas d'erreur
 */
function importCommandeJSON($json_content, $user_id) {
    try {
        // Décoder le JSON
        $data = json_decode($json_content, true);
        if (!$data) {
            throw new Exception('Format JSON invalide');
        }
        
        // Vérifier les champs obligatoires
        $required_fields = ['titre', 'type_commande', 'date_limite', 'date_recuperation', 'adresse_recuperation'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Champ obligatoire manquant: $field");
            }
        }
        
        // Générer un nouvel ID pour éviter les conflits
        $new_commande_id = generateUniqueId();
        
        // Insérer la commande principale
        $stmt = getDB()->prepare("
            INSERT INTO " . DB_PREFIX . "commandes (
                id, titre, description, type_commande, date_limite, date_recuperation,
                adresse_recuperation, montant_total, poids_total, frais_port, public,
                admin_id, date_creation, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $new_commande_id,
            $data['titre'],
            $data['description'] ?? '',
            $data['type_commande'],
            $data['date_limite'],
            $data['date_recuperation'],
            $data['adresse_recuperation'],
            $data['montant_total'] ?? 0,
            $data['poids_total'] ?? 0,
            $data['frais_port'] ?? 0,
            $data['public'] ?? 1,
            $user_id,
            $data['date_limite']
        ]);
        
        // Importer les produits
        if (!empty($data['produits'])) {
            foreach ($data['produits'] as $produit_data) {
                $produit_id = generateUniqueId();
                
                $stmt = getDB()->prepare("
                    INSERT INTO " . DB_PREFIX . "produits (id, commande_id, nom, url, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $produit_id,
                    $new_commande_id,
                    $produit_data['nom'],
                    $produit_data['url'] ?? ''
                ]);
                
                // Importer les variations du produit
                if (!empty($produit_data['variations'])) {
                    foreach ($produit_data['variations'] as $variation_data) {
                        $variation_id = generateUniqueId();
                        
                        $stmt = getDB()->prepare("
                            INSERT INTO " . DB_PREFIX . "variations (id, produit_id, nom, poids, prix, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        
                        $stmt->execute([
                            $variation_id,
                            $produit_id,
                            $variation_data['nom'],
                            $variation_data['poids'] ?? 0,
                            $variation_data['prix'] ?? 0
                        ]);
                    }
                }
            }
        }
        
        // Importer les paliers de frais
        if (!empty($data['paliers_frais'])) {
            foreach ($data['paliers_frais'] as $palier_data) {
                $palier_id = generateUniqueId();
                
                $stmt = getDB()->prepare("
                    INSERT INTO " . DB_PREFIX . "paliers_frais (id, commande_id, min_value, max_value, frais, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $palier_id,
                    $new_commande_id,
                    $palier_data['min'] ?? 0,
                    $palier_data['max'] ?? null,
                    $palier_data['frais'] ?? 0
                ]);
            }
        }
        
        // Importer les participants (sans les utilisateurs existants)
        if (!empty($data['participants'])) {
            foreach ($data['participants'] as $participant_data) {
                // Vérifier si l'utilisateur existe
                $user_exists = getUserData($participant_data['user_id']);
                if ($user_exists) {
                    $participant_id = generateUniqueId();
                    
                    $stmt = getDB()->prepare("
                        INSERT INTO " . DB_PREFIX . "participants (
                            id, commande_id, user_id, montant_produits, part_frais_port,
                            montant_total, statut_paiement, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $participant_id,
                        $new_commande_id,
                        $participant_data['user_id'],
                        $participant_data['montant_produits'] ?? 0,
                        $participant_data['part_frais_port'] ?? 0,
                        $participant_data['montant_total'] ?? 0,
                        $participant_data['statut_paiement'] ?? 0
                    ]);
                }
            }
        }
        
        return $new_commande_id;
        
    } catch (Exception $e) {
        error_log('Erreur importCommandeJSON: ' . $e->getMessage());
        return null;
    }
}

/**
 * Ajoute un participant à une commande
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function addParticipant($commande_id, $user_id) {
    try {
        $pdo = getDB();
        
        // Vérifier si le participant existe déjà
        if (isCommandeParticipant($commande_id, $user_id)) {
            return true; // Déjà participant
        }
        
        $participant_id = generateUniqueId();
        $stmt = $pdo->prepare("
            INSERT INTO " . DB_PREFIX . "participants 
            (id, commande_id, user_id, montant_produits, part_frais_port, montant_total, statut_paiement, created_at) 
            VALUES (?, ?, ?, 0.00, 0.00, 0.00, 0, NOW())
        ");
        
        return $stmt->execute([$participant_id, $commande_id, $user_id]);
    } catch (PDOException $e) {
        error_log('Erreur addParticipant: ' . $e->getMessage());
        return false;
    }
}

/**
 * Retire un participant d'une commande
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function removeParticipant($commande_id, $user_id) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();
        
        // Supprimer les articles commandés du participant
        $stmt = $pdo->prepare("
            DELETE ac FROM " . DB_PREFIX . "articles_commandes ac
            INNER JOIN " . DB_PREFIX . "participants p ON ac.participant_id = p.id
            WHERE p.commande_id = ? AND p.user_id = ?
        ");
        $stmt->execute([$commande_id, $user_id]);
        
        // Supprimer le participant
        $stmt = $pdo->prepare("
            DELETE FROM " . DB_PREFIX . "participants 
            WHERE commande_id = ? AND user_id = ?
        ");
        $result = $stmt->execute([$commande_id, $user_id]);
        
        $pdo->commit();
        
        // Recalculer les totaux de la commande
        if ($result) {
            recalculateCommandeTotals($commande_id);
        }
        
        return $result;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur removeParticipant: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les données d'un participant pour une commande
 * @param array $commande_data Données de la commande
 * @param string $user_id ID de l'utilisateur
 * @return array|null Données du participant ou null
 */
function getParticipantCommande($commande_data, $user_id) {
    if (!isset($commande_data['participants'][$user_id])) {
        return null;
    }
    return $commande_data['participants'][$user_id];
}

/**
 * Ajoute ou met à jour un article commandé
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @param string $variation_id ID de la variation
 * @param int $quantite Quantité commandée
 * @return bool Succès ou échec
 */
function addArticle($commande_id, $user_id, $variation_id, $quantite) {
    try {
        $pdo = getDB();
        
        // Récupérer l'ID du participant
        $stmt = $pdo->prepare("
            SELECT id FROM " . DB_PREFIX . "participants 
            WHERE commande_id = ? AND user_id = ?
        ");
        $stmt->execute([$commande_id, $user_id]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            // Si le participant n'existe pas, l'ajouter automatiquement
            if (!addParticipant($commande_id, $user_id)) {
                return false; // Impossible d'ajouter le participant
            }
            
            // Récupérer à nouveau l'ID du participant
            $stmt = $pdo->prepare("
                SELECT id FROM " . DB_PREFIX . "participants 
                WHERE commande_id = ? AND user_id = ?
            ");
            $stmt->execute([$commande_id, $user_id]);
            $participant = $stmt->fetch();
            
            if (!$participant) {
                return false; // Toujours pas trouvé
            }
        }
        
        $participant_id = $participant['id'];
        
        // Vérifier si l'article existe déjà
        $stmt = $pdo->prepare("
            SELECT id FROM " . DB_PREFIX . "articles_commandes 
            WHERE participant_id = ? AND variation_id = ?
        ");
        $stmt->execute([$participant_id, $variation_id]);
        $existing = $stmt->fetch();
        
        $success = false;
        if ($existing) {
            if ($quantite > 0) {
                // Mettre à jour la quantité
                $stmt = $pdo->prepare("
                    UPDATE " . DB_PREFIX . "articles_commandes 
                    SET quantite = ?, updated_at = NOW() 
                    WHERE participant_id = ? AND variation_id = ?
                ");
                $success = $stmt->execute([$quantite, $participant_id, $variation_id]);
            } else {
                // Supprimer l'article si la quantité est 0
                $stmt = $pdo->prepare("
                    DELETE FROM " . DB_PREFIX . "articles_commandes 
                    WHERE participant_id = ? AND variation_id = ?
                ");
                $success = $stmt->execute([$participant_id, $variation_id]);
            }
        } else {
            // Créer un nouvel article seulement si la quantité est > 0
            if ($quantite > 0) {
                $article_id = generateUniqueId();
                $stmt = $pdo->prepare("
                    INSERT INTO " . DB_PREFIX . "articles_commandes 
                    (id, participant_id, variation_id, quantite, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $success = $stmt->execute([$article_id, $participant_id, $variation_id, $quantite]);
            } else {
                // Si la quantité est 0 et l'article n'existe pas, considérer comme succès
                $success = true;
            }
        }
        
        // Recalculer les montants du participant
        if ($success) {
            recalculateParticipantAmounts($commande_id, $user_id);
        }
        
        return $success;
    } catch (PDOException $e) {
        error_log('Erreur addArticle: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les informations d'une variation
 * @param array $commande_data Données de la commande
 * @param string $variation_id ID de la variation
 * @return array|null Informations de la variation ou null
 */
function getVariationInfo($commande_data, $variation_id) {
    foreach ($commande_data['produits'] as $produit) {
        foreach ($produit['variations'] as $variation) {
            if ($variation['id'] === $variation_id) {
                return [
                    'produit_nom' => $produit['nom'],
                    'variation_nom' => $variation['nom'],
                    'poids' => $variation['poids'],
                    'prix' => $variation['prix']
                ];
            }
        }
    }
    return null;
}

/**
 * Recalcule les montants d'un participant
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function recalculateParticipantAmounts($commande_id, $user_id) {
    try {
        $pdo = getDB();
        
        // Récupérer l'ID du participant
        $stmt = $pdo->prepare("
            SELECT id FROM " . DB_PREFIX . "participants 
            WHERE commande_id = ? AND user_id = ?
        ");
        $stmt->execute([$commande_id, $user_id]);
        $participant = $stmt->fetch();
        
        if (!$participant) {
            return false;
        }
        
        $participant_id = $participant['id'];
        
        // Calculer le montant des produits
        $stmt = $pdo->prepare("
            SELECT SUM(ac.quantite * v.prix) as montant_produits
            FROM " . DB_PREFIX . "articles_commandes ac
            JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
            WHERE ac.participant_id = ?
        ");
        $stmt->execute([$participant_id]);
        $result = $stmt->fetch();
        $montant_produits = $result['montant_produits'] ?? 0;
        
        // Mettre à jour le montant des produits du participant
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "participants 
            SET montant_produits = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$montant_produits, $participant_id]);
        
        // Recalculer tous les totaux de la commande (inclut la répartition des frais)
        return recalculateCommandeTotals($commande_id);
    } catch (PDOException $e) {
        error_log('Erreur recalculateParticipantAmounts: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut de paiement d'un participant
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @param bool $statut_paiement Nouveau statut de paiement
 * @return bool Succès ou échec
 */
function updatePaiementStatus($commande_id, $user_id, $statut_paiement) {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "participants 
            SET statut_paiement = ?, updated_at = NOW()
            WHERE commande_id = ? AND user_id = ?
        ");
        
        return $stmt->execute([$statut_paiement ? 1 : 0, $commande_id, $user_id]);
    } catch (PDOException $e) {
        error_log('Erreur updatePaiementStatus: ' . $e->getMessage());
        return false;
    }
}

/**
 * Calcule les frais de port selon les paliers définis
 * @param string $commande_id ID de la commande
 * @param string $type_commande Type de commande (poids, nombre, montant, sans_frais)
 * @param float $valeur Valeur à évaluer
 * @return float Frais de port calculés
 */
function calculateFraisPort($commande_id, $type_commande, $valeur) {
    try {
        // Si le type de commande est "sans_frais", retourner 0
        if ($type_commande === 'sans_frais') {
            return 0.0;
        }
        
        $pdo = getDB();
        
        // Trouver le palier correspondant
        $stmt = $pdo->prepare("
            SELECT frais 
            FROM " . DB_PREFIX . "paliers_frais 
            WHERE commande_id = ? 
            AND ? >= min_value 
            AND (max_value IS NULL OR ? < max_value)
            ORDER BY min_value DESC 
            LIMIT 1
        ");
        $stmt->execute([$commande_id, $valeur, $valeur]);
        $result = $stmt->fetch();
        
        return $result ? (float) $result['frais'] : 0.0;
    } catch (PDOException $e) {
        error_log('Erreur calculateFraisPort: ' . $e->getMessage());
        return 0.0;
    }
}

/**
 * Recalcule tous les totaux d'une commande
 * @param string $commande_id ID de la commande
 * @return bool Succès ou échec
 */
function recalculateCommandeTotals($commande_id) {
    try {
        $pdo = getDB();
        
        // Récupérer les données de la commande
        $stmt = $pdo->prepare("SELECT type_commande FROM " . DB_PREFIX . "commandes WHERE id = ?");
        $stmt->execute([$commande_id]);
        $commande = $stmt->fetch();
        
        if (!$commande) {
            return false;
        }
        
        $type_commande = $commande['type_commande'];
        
        // Calculer les totaux selon le type de commande
        if ($type_commande === 'poids') {
            // Calculer le poids total
        $stmt = $pdo->prepare("
                SELECT SUM(ac.quantite * v.poids) as poids_total
                FROM " . DB_PREFIX . "articles_commandes ac
                JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
                JOIN " . DB_PREFIX . "participants p ON ac.participant_id = p.id
                WHERE p.commande_id = ?
            ");
            $stmt->execute([$commande_id]);
            $result = $stmt->fetch();
            $valeur_totale = $result['poids_total'] ?? 0;
            
        } elseif ($type_commande === 'nombre') {
            // Calculer le nombre total d'articles
            $stmt = $pdo->prepare("
                SELECT SUM(ac.quantite) as nombre_total
                FROM " . DB_PREFIX . "articles_commandes ac
                JOIN " . DB_PREFIX . "participants p ON ac.participant_id = p.id
                WHERE p.commande_id = ?
            ");
            $stmt->execute([$commande_id]);
            $result = $stmt->fetch();
            $valeur_totale = $result['nombre_total'] ?? 0;
            
        } else { // montant
            // Calculer le montant total des produits
            $stmt = $pdo->prepare("
                SELECT SUM(ac.quantite * v.prix) as montant_total
                FROM " . DB_PREFIX . "articles_commandes ac
                JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
                JOIN " . DB_PREFIX . "participants p ON ac.participant_id = p.id
                WHERE p.commande_id = ?
            ");
            $stmt->execute([$commande_id]);
            $result = $stmt->fetch();
            $valeur_totale = $result['montant_total'] ?? 0;
        }
        
        // Calculer les frais de port
        $frais_port = calculateFraisPort($commande_id, $type_commande, $valeur_totale);
        
        // Calculer le montant total des produits
        $stmt = $pdo->prepare("
            SELECT SUM(ac.quantite * v.prix) as montant_produits
            FROM " . DB_PREFIX . "articles_commandes ac
            JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
            JOIN " . DB_PREFIX . "participants p ON ac.participant_id = p.id
            WHERE p.commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        $result = $stmt->fetch();
        $montant_total = $result['montant_produits'] ?? 0;
        
        // Calculer le poids total
        $stmt = $pdo->prepare("
            SELECT SUM(ac.quantite * v.poids) as poids_total
            FROM " . DB_PREFIX . "articles_commandes ac
            JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
            JOIN " . DB_PREFIX . "participants p ON ac.participant_id = p.id
            WHERE p.commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        $result = $stmt->fetch();
        $poids_total = $result['poids_total'] ?? 0;
        
        // Mettre à jour la commande
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "commandes 
            SET montant_total = ?, poids_total = ?, frais_port = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$montant_total, $poids_total, $frais_port, $commande_id]);
        
        // Recalculer les montants de tous les participants
        recalculateAllParticipantsAmounts($commande_id);
        
        return true;
    } catch (PDOException $e) {
        error_log('Erreur recalculateCommandeTotals: ' . $e->getMessage());
        return false;
    }
}

/**
 * Recalcule les montants de tous les participants d'une commande
 * @param string $commande_id ID de la commande
 * @return bool Succès ou échec
 */
function recalculateAllParticipantsAmounts($commande_id) {
    try {
        $pdo = getDB();
        
        // Récupérer les frais de port et le montant total de la commande
        $stmt = $pdo->prepare("SELECT frais_port, montant_total FROM " . DB_PREFIX . "commandes WHERE id = ?");
        $stmt->execute([$commande_id]);
        $commande = $stmt->fetch();
        
        if (!$commande) {
            return false;
        }
        
        $frais_port_total = $commande['frais_port'];
        $montant_total_commande = $commande['montant_total'];
        
        // Récupérer tous les participants
        $stmt = $pdo->prepare("
            SELECT id, user_id 
            FROM " . DB_PREFIX . "participants 
            WHERE commande_id = ?
        ");
        $stmt->execute([$commande_id]);
        $participants = $stmt->fetchAll();
        
        // Mettre à jour chaque participant
        foreach ($participants as $participant) {
            // Recalculer le montant des produits pour ce participant
            $stmt = $pdo->prepare("
                SELECT SUM(ac.quantite * v.prix) as montant_produits
                FROM " . DB_PREFIX . "articles_commandes ac
                JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
                WHERE ac.participant_id = ?
            ");
            $stmt->execute([$participant['id']]);
        $result = $stmt->fetch();
            $montant_produits = $result['montant_produits'] ?? 0;
            
            // Calculer la part des frais de port proportionnellement
            $part_frais_port = 0;
            if ($montant_total_commande > 0) {
                $part_frais_port = ($frais_port_total * $montant_produits) / $montant_total_commande;
            }
            
        $montant_total = $montant_produits + $part_frais_port;
        
        // Mettre à jour le participant
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "participants 
            SET montant_produits = ?, part_frais_port = ?, montant_total = ?, updated_at = NOW()
            WHERE id = ?
        ");
            $stmt->execute([$montant_produits, $part_frais_port, $montant_total, $participant['id']]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log('Erreur recalculateAllParticipantsAmounts: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un produit à une commande
 * @param string $commande_id ID de la commande
 * @param string $nom_produit Nom du produit
 * @param array $variations Liste des variations
 * @param string $url_produit URL du produit (optionnel)
 * @return bool Succès ou échec
 */
function addProduit($commande_id, $nom_produit, $variations, $url_produit = null) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();
        
        // Insérer le produit
        $produit_id = generateUniqueId();
        $stmt = $pdo->prepare("
            INSERT INTO " . DB_PREFIX . "produits (id, commande_id, nom, url) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$produit_id, $commande_id, $nom_produit, $url_produit]);
        
        // Insérer les variations
        foreach ($variations as $variation) {
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
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur addProduit: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un produit d'une commande
 * @param string $commande_id ID de la commande
 * @param string $produit_id ID du produit
 * @return bool Succès ou échec
 */
function deleteProduit($commande_id, $produit_id) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();
        
        // Supprimer les articles commandés liés aux variations de ce produit
        $stmt = $pdo->prepare("
            DELETE ac FROM " . DB_PREFIX . "articles_commandes ac
            JOIN " . DB_PREFIX . "variations v ON ac.variation_id = v.id
            WHERE v.produit_id = ?
        ");
        $stmt->execute([$produit_id]);
        
        // Supprimer les variations
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "variations WHERE produit_id = ?");
        $stmt->execute([$produit_id]);
        
        // Supprimer le produit
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "produits WHERE id = ? AND commande_id = ?");
        $stmt->execute([$produit_id, $commande_id]);
        
        $pdo->commit();
        
        // Recalculer les totaux
        recalculateCommandeTotals($commande_id);
        
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur deleteProduit: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une variation d'un produit
 * @param string $commande_id ID de la commande
 * @param string $produit_id ID du produit
 * @param string $variation_id ID de la variation
 * @return bool Succès ou échec
 */
function deleteVariation($commande_id, $produit_id, $variation_id) {
    try {
        $pdo = getDB();
        $pdo->beginTransaction();
        
        // Supprimer les articles commandés liés à cette variation
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "articles_commandes WHERE variation_id = ?");
        $stmt->execute([$variation_id]);
        
        // Supprimer la variation
        $stmt = $pdo->prepare("DELETE FROM " . DB_PREFIX . "variations WHERE id = ? AND produit_id = ?");
        $stmt->execute([$variation_id, $produit_id]);
        
        $pdo->commit();
        
        // Recalculer les totaux
        recalculateCommandeTotals($commande_id);
        
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('Erreur deleteVariation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour les dates d'une commande
 * @param string $commande_id ID de la commande
 * @param string $date_limite Nouvelle date limite
 * @param string $date_recuperation Nouvelle date de récupération
 * @return bool Succès ou échec
 */
function updateCommandeDates($commande_id, $date_limite, $date_recuperation) {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "commandes 
            SET date_limite = ?, date_recuperation = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$date_limite, $date_recuperation, $commande_id]);
    } catch (PDOException $e) {
        error_log('Erreur updateCommandeDates: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour la description d'une commande
 * @param string $commande_id ID de la commande
 * @param string $description Nouvelle description
 * @return bool Succès ou échec
 */
function updateCommandeDescription($commande_id, $description) {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "commandes 
            SET description = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$description, $commande_id]);
    } catch (PDOException $e) {
        error_log('Erreur updateCommandeDescription: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut public d'une commande
 * @param string $commande_id ID de la commande
 * @param bool $is_public Nouveau statut public
 * @return bool Succès ou échec
 */
function updateCommandePublicStatus($commande_id, $is_public) {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->prepare("
            UPDATE " . DB_PREFIX . "commandes 
            SET public = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$is_public ? 1 : 0, $commande_id]);
    } catch (PDOException $e) {
        error_log('Erreur updateCommandePublicStatus: ' . $e->getMessage());
        return false;
    }
}

/**
 * Duplique une commande
 * @param string $commande_id ID de la commande à dupliquer
 * @param string $user_id ID de l'utilisateur qui duplique
 * @return string|null ID de la nouvelle commande ou null si échec
 */
function duplicateCommande($commande_id, $user_id) {
    try {
        $commande_data = getCommandeData($commande_id);
        if (!$commande_data) {
            return null;
        }
        
        // Créer une nouvelle commande avec les mêmes données
        $new_commande_id = createCommande(
            $user_id,
            $commande_data['titre'] . ' (Copie)',
            $commande_data['type_commande'],
            $commande_data['date_limite'],
            $commande_data['date_recuperation'],
            $commande_data['adresse_recuperation'],
            $commande_data['description'] ?? '',
            $commande_data['paliers_frais'],
            $commande_data['produits']
        );
        
        // Mettre à jour la description si elle existe
        if (!empty($commande_data['description'])) {
            updateCommandeDescription($new_commande_id, $commande_data['description']);
        }
        
        return $new_commande_id;
    } catch (Exception $e) {
        error_log('Erreur duplicateCommande: ' . $e->getMessage());
        return null;
    }
}

/**
 * Envoie un rappel aux participants d'une commande
 * @param string $commande_id ID de la commande
 * @return bool Succès ou échec
 */
function envoyerRappel($commande_id) {
    try {
        $commande_data = getCommandeData($commande_id);
        if (!$commande_data) {
            return false;
        }
        
        $admin_data = getUserData($commande_data['admin_id']);
        $admin_name = $admin_data ? $admin_data['prenom'] . ' ' . $admin_data['nom'] : 'Administrateur';
        
        $subject = APP_NAME . ' - Rappel pour la commande: ' . $commande_data['titre'];
        $commande_url = APP_URL . '/commande.php?id=' . $commande_id;
        
        $success_count = 0;
        foreach ($commande_data['participants'] as $participant) {
            $user_data = getUserData($participant['user_id']);
            if ($user_data) {
                $message = "<html><body>";
                $message .= "<h1>Rappel - Commande: " . htmlspecialchars($commande_data['titre']) . "</h1>";
                $message .= "<p>Bonjour " . htmlspecialchars($user_data['prenom']) . ",</p>";
                $message .= "<p>Ceci est un rappel pour la commande groupée organisée par " . htmlspecialchars($admin_name) . ".</p>";
                $message .= "<p><strong>Date limite:</strong> " . formatDate($commande_data['date_limite']) . "</p>";
                $message .= "<p><strong>Date de récupération:</strong> " . formatDate($commande_data['date_recuperation']) . "</p>";
                $message .= "<p><strong>Adresse de récupération:</strong> " . htmlspecialchars($commande_data['adresse_recuperation']) . "</p>";
                $message .= "<p>Vous pouvez consulter et modifier votre commande en cliquant sur le lien suivant:</p>";
                $message .= "<p><a href='" . $commande_url . "'>" . $commande_url . "</a></p>";
                $message .= "<p>Cordialement,<br>" . htmlspecialchars($admin_name) . "</p>";
                $message .= "</body></html>";
                
                if (sendEmail($user_data['email'], $subject, $message)) {
                    $success_count++;
                }
            }
        }
        
        return $success_count > 0;
    } catch (Exception $e) {
        error_log('Erreur envoyerRappel: ' . $e->getMessage());
        return false;
    }
}

/**
 * Envoie les informations de récupération aux participants
 * @param string $commande_id ID de la commande
 * @return bool Succès ou échec
 */
function envoyerInfosRecuperation($commande_id) {
    try {
        $commande_data = getCommandeData($commande_id);
        if (!$commande_data) {
            return false;
        }
        
        $admin_data = getUserData($commande_data['admin_id']);
        $admin_name = $admin_data ? $admin_data['prenom'] . ' ' . $admin_data['nom'] : 'Administrateur';
        
        $subject = APP_NAME . ' - Informations de récupération: ' . $commande_data['titre'];
        
        $success_count = 0;
        foreach ($commande_data['participants'] as $participant) {
            $user_data = getUserData($participant['user_id']);
            if ($user_data) {
                $message = "<html><body>";
                $message .= "<h1>Informations de récupération</h1>";
                $message .= "<p>Bonjour " . htmlspecialchars($user_data['prenom']) . ",</p>";
                $message .= "<p>Voici les informations pour récupérer votre commande:</p>";
                $message .= "<h2>Commande: " . htmlspecialchars($commande_data['titre']) . "</h2>";
                $message .= "<p><strong>Date de récupération:</strong> " . formatDate($commande_data['date_recuperation']) . "</p>";
                $message .= "<p><strong>Adresse de récupération:</strong><br>" . nl2br(htmlspecialchars($commande_data['adresse_recuperation'])) . "</p>";
                $message .= "<h3>Votre commande:</h3>";
                $message .= "<p><strong>Montant des produits:</strong> " . number_format($participant['montant_produits'], 2, ',', ' ') . " €</p>";
                $message .= "<p><strong>Part des frais de port:</strong> " . number_format($participant['part_frais_port'], 2, ',', ' ') . " €</p>";
                $message .= "<p><strong>Total à payer:</strong> " . number_format($participant['montant_total'], 2, ',', ' ') . " €</p>";
                $message .= "<p><strong>Statut de paiement:</strong> " . ($participant['statut_paiement'] ? 'Payé' : 'Non payé') . "</p>";
                $message .= "<p>Cordialement,<br>" . htmlspecialchars($admin_name) . "</p>";
                $message .= "</body></html>";
                
                if (sendEmail($user_data['email'], $subject, $message)) {
                    $success_count++;
                }
            }
        }
        
        return $success_count > 0;
    } catch (Exception $e) {
        error_log('Erreur envoyerInfosRecuperation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les paliers de frais d'une commande
 * @param string $commande_id ID de la commande
 * @return array Liste des paliers
 */
function getCommandePaliers($commande_id) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT min_value, max_value, frais 
            FROM " . DB_PREFIX . "paliers_frais 
            WHERE commande_id = ? 
            ORDER BY min_value ASC
        ");
        $stmt->execute([$commande_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Erreur getCommandePaliers: ' . $e->getMessage());
        return [];
    }
}

/**
 * Vérifie un token Turnstile avec Cloudflare
 * @param string $token Token Turnstile à vérifier
 * @return bool True si la vérification réussit, false sinon
 */
function verifyTurnstile($token) {
    // Vérifier si Turnstile est activé
    if (!defined('USE_TURNSTILE') || !USE_TURNSTILE) {
        return true; // Si Turnstile n'est pas activé, considérer comme valide
    }
    
    // Vérifier si les clés sont configurées
    if (!defined('TURNSTILE_SECRET_KEY') || empty(TURNSTILE_SECRET_KEY)) {
        error_log('Erreur Turnstile: Clé secrète non configurée');
        return false;
    }
    
    if (empty($token)) {
        return false;
    }
    
    // Préparer les données pour l'API Cloudflare
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    // Effectuer la requête à l'API Cloudflare
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    
    if ($response === false) {
        error_log('Erreur Turnstile: Impossible de contacter l\'API Cloudflare');
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (!$result) {
        error_log('Erreur Turnstile: Réponse JSON invalide de l\'API');
        return false;
    }
    
    // Log pour debug (à retirer en production)
    error_log('Turnstile verification result: ' . json_encode($result));
    
    return isset($result['success']) && $result['success'] === true;
}
?>
