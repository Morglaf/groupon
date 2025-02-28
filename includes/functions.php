<?php
require_once 'config.php';

/**
 * Génère un ID unique
 * @return string
 */
function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

/**
 * Verrouille un fichier pour l'écriture
 * @param string $file_path Chemin du fichier
 * @return resource|false Descripteur de fichier ou false si échec
 */
function lockFile($file_path) {
    $file = fopen($file_path, 'c+');
    if (!$file) return false;
    
    $lock_acquired = false;
    $timeout = time() + LOCK_TIMEOUT;
    
    while (time() < $timeout) {
        if (flock($file, LOCK_EX | LOCK_NB)) {
            $lock_acquired = true;
            break;
        }
        usleep(100000); // 100ms
    }
    
    if (!$lock_acquired) {
        fclose($file);
        return false;
    }
    
    return $file;
}

/**
 * Déverrouille et ferme un fichier
 * @param resource $file Descripteur de fichier
 */
function unlockFile($file) {
    flock($file, LOCK_UN);
    fclose($file);
}

/**
 * Lit les données d'un fichier JSON
 * @param string $file_path Chemin du fichier
 * @return array|null Données JSON ou null si erreur
 */
function readJsonFile($file_path) {
    if (!file_exists($file_path)) {
        return null;
    }
    
    $json_content = file_get_contents($file_path);
    if ($json_content === false) {
        return null;
    }
    
    $data = json_decode($json_content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }
    
    return $data;
}

/**
 * Écrit des données dans un fichier JSON
 * @param string $file_path Chemin du fichier
 * @param array $data Données à écrire
 * @return bool Succès ou échec
 */
function writeJsonFile($file_path, $data) {
    $file = lockFile($file_path);
    if ($file === false) {
        return false;
    }
    
    ftruncate($file, 0);
    rewind($file);
    
    $json_content = json_encode($data, JSON_PRETTY_PRINT);
    $result = fwrite($file, $json_content);
    
    unlockFile($file);
    return $result !== false;
}

/**
 * Récupère les données d'un utilisateur
 * @param string $user_id ID de l'utilisateur
 * @return array|null Données de l'utilisateur ou null si non trouvé
 */
function getUserData($user_id) {
    $file_path = USERS_DIR . '/user_' . $user_id . '.json';
    return readJsonFile($file_path);
}

/**
 * Récupère les données d'une commande
 * @param string $commande_id ID de la commande
 * @return array|null Données de la commande ou null si non trouvée
 */
function getCommandeData($commande_id) {
    $file_path = COMMANDES_DIR . '/commande_' . $commande_id . '.json';
    return readJsonFile($file_path);
}

/**
 * Sauvegarde les données d'un utilisateur
 * @param array $user_data Données de l'utilisateur
 * @return bool Succès ou échec
 */
function saveUserData($user_data) {
    if (!isset($user_data['id'])) {
        return false;
    }
    
    $file_path = USERS_DIR . '/user_' . $user_data['id'] . '.json';
    return writeJsonFile($file_path, $user_data);
}

/**
 * Sauvegarde les données d'une commande
 * @param array $commande_data Données de la commande
 * @return bool Succès ou échec
 */
function saveCommandeData($commande_data) {
    if (!isset($commande_data['id'])) {
        return false;
    }
    
    $file_path = COMMANDES_DIR . '/commande_' . $commande_data['id'] . '.json';
    return writeJsonFile($file_path, $commande_data);
}

/**
 * Calcule les frais de port basés sur le poids
 * @param float $poids_total Poids total en kg
 * @param array $paliers_frais Paliers de frais
 * @return float Frais de port
 */
function calculerFraisPortPoids($poids_total, $paliers_frais) {
    foreach ($paliers_frais as $palier) {
        if ($poids_total >= $palier['min'] && $poids_total < $palier['max']) {
            return $palier['frais'];
        }
    }
    
    // Si aucun palier ne correspond, on prend le dernier
    $dernier_palier = end($paliers_frais);
    return $dernier_palier['frais'];
}

/**
 * Calcule les frais de port basés sur le nombre d'articles
 * @param int $nb_articles Nombre d'articles
 * @param array $paliers_frais Paliers de frais
 * @return float Frais de port
 */
function calculerFraisPortNombre($nb_articles, $paliers_frais) {
    foreach ($paliers_frais as $palier) {
        if ($nb_articles >= $palier['min'] && $nb_articles < $palier['max']) {
            return $palier['frais'];
        }
    }
    
    // Si aucun palier ne correspond, on prend le dernier
    $dernier_palier = end($paliers_frais);
    return $dernier_palier['frais'];
}

/**
 * Calcule les frais de port basés sur le montant total
 * @param float $montant_total Montant total
 * @param array $paliers_frais Paliers de frais
 * @return float Frais de port
 */
function calculerFraisPortMontant($montant_total, $paliers_frais) {
    foreach ($paliers_frais as $palier) {
        if ($montant_total >= $palier['min'] && $montant_total < $palier['max']) {
            return $palier['frais'];
        }
    }
    
    // Si aucun palier ne correspond, on prend le dernier
    $dernier_palier = end($paliers_frais);
    return $dernier_palier['frais'];
}

/**
 * Calcule les frais de port pour une commande
 * @param array $commande Données de la commande
 * @return float Frais de port
 */
function calculerFraisPort($commande) {
    switch ($commande['type_commande']) {
        case 'poids':
            return calculerFraisPortPoids($commande['poids_total'], $commande['paliers_frais']);
        
        case 'nombre':
            $nb_articles = 0;
            foreach ($commande['participants'] as $participant) {
                foreach ($participant['commandes'] as $cmd) {
                    $nb_articles += $cmd['quantite'];
                }
            }
            return calculerFraisPortNombre($nb_articles, $commande['paliers_frais']);
        
        case 'montant':
            return calculerFraisPortMontant($commande['montant_total'], $commande['paliers_frais']);
        
        default:
            return 0;
    }
}

/**
 * Répartit les frais de port entre les participants
 * @param array $commande Données de la commande
 * @return array Commande mise à jour
 */
function repartirFraisPort($commande) {
    $frais_port = calculerFraisPort($commande);
    $commande['frais_port'] = $frais_port;
    
    $montant_total_participants = 0;
    foreach ($commande['participants'] as $participant) {
        $montant_total_participants += $participant['montant_produits'];
    }
    
    // Répartition proportionnelle des frais de port
    foreach ($commande['participants'] as &$participant) {
        $ratio = $participant['montant_produits'] / $montant_total_participants;
        $participant['part_frais_port'] = round($frais_port * $ratio, 2);
        $participant['montant_total'] = $participant['montant_produits'] + $participant['part_frais_port'];
    }
    
    return $commande;
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
    $files = glob(USERS_DIR . '/user_*.json');
    
    foreach ($files as $file) {
        $user_data = readJsonFile($file);
        if ($user_data && isset($user_data['email']) && $user_data['email'] === $email) {
            return true;
        }
    }
    
    return false;
}

/**
 * Récupère l'ID d'un utilisateur à partir de son email
 * @param string $email Adresse email
 * @return string|null ID de l'utilisateur ou null si non trouvé
 */
function getUserIdByEmail($email) {
    $files = glob(USERS_DIR . '/user_*.json');
    
    foreach ($files as $file) {
        $user_data = readJsonFile($file);
        if ($user_data && isset($user_data['email']) && $user_data['email'] === $email) {
            return $user_data['id'];
        }
    }
    
    return null;
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
        'date_inscription' => (new DateTime())->format('Y-m-d\TH:i:s'),
        'commandes_admin' => [],
        'commandes_participant' => [],
        'historique_commandes' => []
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
 * Crée une nouvelle commande
 * @param string $admin_id ID de l'admin
 * @param string $titre Titre de la commande
 * @param string $type_commande Type de commande (poids, nombre, montant)
 * @param array $paliers_frais Paliers de frais
 * @param string $date_limite Date limite
 * @param string $date_recuperation Date de récupération
 * @param string $adresse_recuperation Adresse de récupération
 * @return array|null Données de la commande ou null si échec
 */
function createCommande($admin_id, $titre, $type_commande, $paliers_frais, $date_limite, $date_recuperation, $adresse_recuperation) {
    $commande_id = generateUniqueId();
    $commande_data = [
        'id' => $commande_id,
        'titre' => $titre,
        'admin_id' => $admin_id,
        'date_creation' => (new DateTime())->format('Y-m-d\TH:i:s'),
        'date_limite' => $date_limite,
        'date_recuperation' => $date_recuperation,
        'adresse_recuperation' => $adresse_recuperation,
        'type_commande' => $type_commande,
        'paliers_frais' => $paliers_frais,
        'produits' => [],
        'participants' => [],
        'poids_total' => 0,
        'montant_total' => 0,
        'frais_port' => 0
    ];
    
    if (saveCommandeData($commande_data)) {
        // Mettre à jour l'utilisateur admin
        $user_data = getUserData($admin_id);
        if ($user_data) {
            $user_data['commandes_admin'][] = $commande_id;
            saveUserData($user_data);
            
            // Envoi email de confirmation
            $admin_email = $user_data['email'];
            $subject = APP_NAME . ' - Nouvelle commande créée';
            $message = "<html><body>";
            $message .= "<h1>Votre commande a été créée avec succès !</h1>";
            $message .= "<p>Titre : " . htmlspecialchars($titre) . "</p>";
            $message .= "<p>ID : " . $commande_id . "</p>";
            $message .= "<p>Lien de partage : " . APP_URL . "/commande.php?id=" . $commande_id . "</p>";
            $message .= "</body></html>";
            
            sendEmail($admin_email, $subject, $message);
        }
        
        return $commande_data;
    }
    
    return null;
}

/**
 * Ajoute un produit à une commande
 * @param string $commande_id ID de la commande
 * @param string $nom Nom du produit
 * @param array $variations Variations du produit
 * @param string $url URL du produit (optionnel)
 * @return bool Succès ou échec
 */
function addProduit($commande_id, $nom, $variations, $url = '') {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    $produit_id = generateUniqueId();
    $produit = [
        'id' => $produit_id,
        'nom' => $nom,
        'variations' => $variations,
        'url' => $url
    ];
    
    $commande_data['produits'][] = $produit;
    
    return saveCommandeData($commande_data);
}

/**
 * Ajoute un participant à une commande
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool Succès ou échec
 */
function addParticipant($commande_id, $user_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data || isCommandeClosed($commande_data)) {
        return false;
    }
    
    $user_data = getUserData($user_id);
    if (!$user_data) {
        return false;
    }
    
    // Vérifier si l'utilisateur est déjà participant
    foreach ($commande_data['participants'] as $participant) {
        if ($participant['user_id'] === $user_id) {
            return true; // Déjà participant
        }
    }
    
    // Ajouter le participant
    $commande_data['participants'][] = [
        'user_id' => $user_id,
        'commandes' => [],
        'montant_produits' => 0,
        'part_frais_port' => 0,
        'montant_total' => 0,
        'statut_paiement' => false
    ];
    
    // Mettre à jour l'utilisateur
    $user_data['commandes_participant'][] = $commande_id;
    saveUserData($user_data);
    
    return saveCommandeData($commande_data);
}

/**
 * Ajoute un article à la commande d'un participant
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @param string $variation_id ID de la variation
 * @param int $quantite Quantité
 * @return bool Succès ou échec
 */
function addArticle($commande_id, $user_id, $variation_id, $quantite) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data || isCommandeClosed($commande_data)) {
        return false;
    }
    
    // Trouver la variation
    $variation = null;
    foreach ($commande_data['produits'] as $produit) {
        foreach ($produit['variations'] as $var) {
            if ($var['id'] === $variation_id) {
                $variation = $var;
                break 2;
            }
        }
    }
    
    if (!$variation) {
        return false;
    }
    
    // Trouver le participant
    $participant_index = -1;
    foreach ($commande_data['participants'] as $index => $participant) {
        if ($participant['user_id'] === $user_id) {
            $participant_index = $index;
            break;
        }
    }
    
    if ($participant_index === -1) {
        return false;
    }
    
    // Vérifier si l'article existe déjà
    $article_exists = false;
    foreach ($commande_data['participants'][$participant_index]['commandes'] as &$article) {
        if ($article['variation_id'] === $variation_id) {
            $article['quantite'] = $quantite;
            $article_exists = true;
            break;
        }
    }
    
    // Ajouter l'article si nouveau
    if (!$article_exists) {
        $commande_data['participants'][$participant_index]['commandes'][] = [
            'variation_id' => $variation_id,
            'quantite' => $quantite
        ];
    }
    
    // Recalculer les montants
    $montant_produits = 0;
    $poids_total = 0;
    
    foreach ($commande_data['participants'][$participant_index]['commandes'] as $article) {
        foreach ($commande_data['produits'] as $produit) {
            foreach ($produit['variations'] as $var) {
                if ($var['id'] === $article['variation_id']) {
                    $montant_produits += $var['prix'] * $article['quantite'];
                    $poids_total += $var['poids'] * $article['quantite'];
                    break;
                }
            }
        }
    }
    
    $commande_data['participants'][$participant_index]['montant_produits'] = $montant_produits;
    
    // Recalculer les totaux
    $montant_total = 0;
    $poids_total_commande = 0;
    
    foreach ($commande_data['participants'] as $participant) {
        $montant_total += $participant['montant_produits'];
        
        foreach ($participant['commandes'] as $article) {
            foreach ($commande_data['produits'] as $produit) {
                foreach ($produit['variations'] as $var) {
                    if ($var['id'] === $article['variation_id']) {
                        $poids_total_commande += $var['poids'] * $article['quantite'];
                        break;
                    }
                }
            }
        }
    }
    
    $commande_data['montant_total'] = $montant_total;
    $commande_data['poids_total'] = $poids_total_commande;
    
    // Répartir les frais de port
    $commande_data = repartirFraisPort($commande_data);
    
    return saveCommandeData($commande_data);
}

/**
 * Met à jour le statut de paiement d'un participant
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @param bool $statut Statut de paiement
 * @return bool Succès ou échec
 */
function updatePaiementStatus($commande_id, $user_id, $statut) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    foreach ($commande_data['participants'] as &$participant) {
        if ($participant['user_id'] === $user_id) {
            $participant['statut_paiement'] = $statut;
            return saveCommandeData($commande_data);
        }
    }
    
    return false;
}

/**
 * Envoie un rappel aux participants
 * @param string $commande_id ID de la commande
 * @return bool Succès ou échec
 */
function envoyerRappel($commande_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    $date_limite = new DateTime($commande_data['date_limite']);
    $date_limite_str = $date_limite->format('d/m/Y H:i');
    
    $subject = APP_NAME . ' - Rappel : commande ' . $commande_data['titre'];
    $message = "<html><body>";
    $message .= "<h1>Rappel : votre commande se termine bientôt !</h1>";
    $message .= "<p>La commande <strong>" . htmlspecialchars($commande_data['titre']) . "</strong> se termine le " . $date_limite_str . ".</p>";
    $message .= "<p>Vérifiez votre commande avant la fermeture :</p>";
    $message .= "<p><a href='" . APP_URL . "/commande.php?id=" . $commande_id . "'>Accéder à la commande</a></p>";
    $message .= "</body></html>";
    
    $success = true;
    
    foreach ($commande_data['participants'] as $participant) {
        $user_data = getUserData($participant['user_id']);
        if ($user_data) {
            $sent = sendEmail($user_data['email'], $subject, $message);
            $success = $success && $sent;
        }
    }
    
    return $success;
}

/**
 * Envoie les informations de récupération aux participants
 * @param string $commande_id ID de la commande
 * @return bool Succès ou échec
 */
function envoyerInfosRecuperation($commande_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    $date_recup = new DateTime($commande_data['date_recuperation']);
    $date_recup_str = $date_recup->format('d/m/Y H:i');
    
    $subject = APP_NAME . ' - Informations de récupération : ' . $commande_data['titre'];
    $message = "<html><body>";
    $message .= "<h1>Votre commande est prête à être récupérée !</h1>";
    $message .= "<p>La commande <strong>" . htmlspecialchars($commande_data['titre']) . "</strong> est disponible pour récupération.</p>";
    $message .= "<p><strong>Date :</strong> " . $date_recup_str . "</p>";
    $message .= "<p><strong>Adresse :</strong> " . htmlspecialchars($commande_data['adresse_recuperation']) . "</p>";
    $message .= "<p><a href='" . APP_URL . "/commande.php?id=" . $commande_id . "'>Consulter les détails de la commande</a></p>";
    $message .= "</body></html>";
    
    $success = true;
    
    foreach ($commande_data['participants'] as $participant) {
        $user_data = getUserData($participant['user_id']);
        if ($user_data) {
            $sent = sendEmail($user_data['email'], $subject, $message);
            $success = $success && $sent;
        }
    }
    
    return $success;
}

/**
 * Vérifie si un utilisateur est admin d'une commande
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool Vrai si l'utilisateur est admin
 */
function isCommandeAdmin($commande_id, $user_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    return $commande_data['admin_id'] === $user_id;
}

/**
 * Vérifie si un utilisateur est participant d'une commande
 * @param string $commande_id ID de la commande
 * @param string $user_id ID de l'utilisateur
 * @return bool Vrai si l'utilisateur est participant
 */
function isCommandeParticipant($commande_id, $user_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    foreach ($commande_data['participants'] as $participant) {
        if ($participant['user_id'] === $user_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * Récupère les informations d'une variation
 * @param array $commande Données de la commande
 * @param string $variation_id ID de la variation
 * @return array|null Données de la variation ou null si non trouvée
 */
function getVariationInfo($commande, $variation_id) {
    foreach ($commande['produits'] as $produit) {
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
 * Récupère la commande d'un participant
 * @param array $commande Données de la commande
 * @param string $user_id ID de l'utilisateur
 * @return array|null Données de la commande du participant ou null si non trouvé
 */
function getParticipantCommande($commande, $user_id) {
    foreach ($commande['participants'] as $participant) {
        if ($participant['user_id'] === $user_id) {
            return $participant;
        }
    }
    
    return null;
}
?>