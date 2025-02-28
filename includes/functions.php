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
    // Essayer avec le préfixe 'commande_'
    $file_path = COMMANDES_DIR . '/commande_' . $commande_id . '.json';
    
    // Si le fichier n'existe pas, essayer sans le préfixe
    if (!file_exists($file_path)) {
        $file_path = COMMANDES_DIR . '/' . $commande_id . '.json';
    }
    
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
    
    $commande_id = $commande_data['id'];
    
    // Vérifier si le fichier existe déjà avec le préfixe 'commande_'
    $file_path_with_prefix = COMMANDES_DIR . '/commande_' . $commande_id . '.json';
    $file_path_without_prefix = COMMANDES_DIR . '/' . $commande_id . '.json';
    
    // Utiliser le même format que celui existant, ou avec préfixe par défaut
    if (file_exists($file_path_without_prefix)) {
        $file_path = $file_path_without_prefix;
    } else {
        $file_path = $file_path_with_prefix;
    }
    
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

/**
 * Met à jour les dates d'une commande
 * @param string $commande_id ID de la commande
 * @param string $date_limite Nouvelle date limite
 * @param string $date_recuperation Nouvelle date de récupération
 * @return bool Succès ou échec
 */
function updateCommandeDates($commande_id, $date_limite, $date_recuperation) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    $commande_data['date_limite'] = $date_limite;
    $commande_data['date_recuperation'] = $date_recuperation;
    
    // Envoi d'un email de notification aux participants
    $subject = APP_NAME . ' - Modification des dates : ' . $commande_data['titre'];
    $message = "<html><body>";
    $message .= "<h1>Les dates de votre commande ont été modifiées</h1>";
    $message .= "<p>La commande <strong>" . htmlspecialchars($commande_data['titre']) . "</strong> a été mise à jour :</p>";
    $message .= "<p><strong>Nouvelle date limite :</strong> " . (new DateTime($date_limite))->format('d/m/Y H:i') . "</p>";
    $message .= "<p><strong>Nouvelle date de récupération :</strong> " . (new DateTime($date_recuperation))->format('d/m/Y H:i') . "</p>";
    $message .= "<p><a href='" . APP_URL . "/commande.php?id=" . $commande_id . "'>Consulter les détails de la commande</a></p>";
    $message .= "</body></html>";
    
    foreach ($commande_data['participants'] as $participant) {
        $user_data = getUserData($participant['user_id']);
        if ($user_data) {
            sendEmail($user_data['email'], $subject, $message);
        }
    }
    
    return saveCommandeData($commande_data);
}

/**
 * Supprime un produit d'une commande
 * @param string $commande_id ID de la commande
 * @param string $produit_id ID du produit
 * @return bool Succès ou échec
 */
function deleteProduit($commande_id, $produit_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    // Trouver l'index du produit
    $produit_index = -1;
    foreach ($commande_data['produits'] as $index => $produit) {
        if ($produit['id'] === $produit_id) {
            $produit_index = $index;
            break;
        }
    }
    
    if ($produit_index === -1) {
        return false;
    }
    
    // Récupérer les IDs des variations du produit
    $variation_ids = [];
    foreach ($commande_data['produits'][$produit_index]['variations'] as $variation) {
        $variation_ids[] = $variation['id'];
    }
    
    // Supprimer les articles correspondants des commandes des participants
    foreach ($commande_data['participants'] as &$participant) {
        $new_commandes = [];
        foreach ($participant['commandes'] as $article) {
            if (!in_array($article['variation_id'], $variation_ids)) {
                $new_commandes[] = $article;
            }
        }
        $participant['commandes'] = $new_commandes;
    }
    
    // Supprimer le produit
    array_splice($commande_data['produits'], $produit_index, 1);
    
    // Recalculer les montants
    $commande_data = recalculerMontants($commande_data);
    
    return saveCommandeData($commande_data);
}

/**
 * Supprime une variation d'un produit
 * @param string $commande_id ID de la commande
 * @param string $produit_id ID du produit
 * @param string $variation_id ID de la variation
 * @return bool Succès ou échec
 */
function deleteVariation($commande_id, $produit_id, $variation_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    // Trouver le produit
    $produit_index = -1;
    foreach ($commande_data['produits'] as $index => $produit) {
        if ($produit['id'] === $produit_id) {
            $produit_index = $index;
            break;
        }
    }
    
    if ($produit_index === -1) {
        return false;
    }
    
    // Trouver la variation
    $variation_index = -1;
    foreach ($commande_data['produits'][$produit_index]['variations'] as $index => $variation) {
        if ($variation['id'] === $variation_id) {
            $variation_index = $index;
            break;
        }
    }
    
    if ($variation_index === -1) {
        return false;
    }
    
    // Supprimer les articles correspondants des commandes des participants
    foreach ($commande_data['participants'] as &$participant) {
        $new_commandes = [];
        foreach ($participant['commandes'] as $article) {
            if ($article['variation_id'] !== $variation_id) {
                $new_commandes[] = $article;
            }
        }
        $participant['commandes'] = $new_commandes;
    }
    
    // Supprimer la variation
    array_splice($commande_data['produits'][$produit_index]['variations'], $variation_index, 1);
    
    // Si le produit n'a plus de variations, le supprimer
    if (empty($commande_data['produits'][$produit_index]['variations'])) {
        array_splice($commande_data['produits'], $produit_index, 1);
    }
    
    // Recalculer les montants
    $commande_data = recalculerMontants($commande_data);
    
    return saveCommandeData($commande_data);
}

/**
 * Recalcule les montants d'une commande
 * @param array $commande_data Données de la commande
 * @return array Données de la commande mises à jour
 */
function recalculerMontants($commande_data) {
    $montant_total = 0;
    $poids_total = 0;
    
    // Recalculer les montants pour chaque participant
    foreach ($commande_data['participants'] as &$participant) {
        $montant_produits = 0;
        
        foreach ($participant['commandes'] as $article) {
            foreach ($commande_data['produits'] as $produit) {
                foreach ($produit['variations'] as $variation) {
                    if ($variation['id'] === $article['variation_id']) {
                        $montant_produits += $variation['prix'] * $article['quantite'];
                        $poids_total += $variation['poids'] * $article['quantite'];
                        break;
                    }
                }
            }
        }
        
        $participant['montant_produits'] = $montant_produits;
        $montant_total += $montant_produits;
    }
    
    $commande_data['montant_total'] = $montant_total;
    $commande_data['poids_total'] = $poids_total;
    
    // Recalculer les frais de port
    $commande_data['frais_port'] = calculerFraisPort($commande_data);
    
    // Répartir les frais de port
    $commande_data = repartirFraisPort($commande_data);
    
    return $commande_data;
}

/**
 * Exporte une commande au format JSON
 * @param string $commande_id ID de la commande
 * @return string|null Données JSON ou null si échec
 */
function exportCommandeJSON($commande_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return null;
    }
    
    // Ajouter des informations supplémentaires pour l'export
    $commande_data['export_date'] = (new DateTime())->format('Y-m-d\TH:i:s');
    $commande_data['app_version'] = '1.0';
    
    return json_encode($commande_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

/**
 * Duplique une commande
 * @param string $commande_id ID de la commande à dupliquer
 * @param string $admin_id ID de l'administrateur de la nouvelle commande
 * @return string|null ID de la nouvelle commande ou null si échec
 */
function duplicateCommande($commande_id, $admin_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return null;
    }
    
    $new_commande_id = generateUniqueId();
    $new_commande = [
        'id' => $new_commande_id,
        'titre' => $commande_data['titre'] . ' (copie)',
        'admin_id' => $admin_id,
        'date_creation' => (new DateTime())->format('Y-m-d\TH:i:s'),
        'date_limite' => (new DateTime())->modify('+7 days')->format('Y-m-d\TH:i:s'),
        'date_recuperation' => (new DateTime())->modify('+14 days')->format('Y-m-d\TH:i:s'),
        'adresse_recuperation' => $commande_data['adresse_recuperation'],
        'type_commande' => $commande_data['type_commande'],
        'paliers_frais' => $commande_data['paliers_frais'],
        'produits' => $commande_data['produits'],
        'participants' => [],
        'poids_total' => 0,
        'montant_total' => 0,
        'frais_port' => 0,
        'description' => isset($commande_data['description']) ? $commande_data['description'] : ''
    ];
    
    if (saveCommandeData($new_commande)) {
        // Mettre à jour l'utilisateur admin
        $user_data = getUserData($admin_id);
        if ($user_data) {
            $user_data['commandes_admin'][] = $new_commande_id;
            saveUserData($user_data);
        }
        
        return $new_commande_id;
    }
    
    return null;
}

/**
 * Met à jour la description d'une commande
 * @param string $commande_id ID de la commande
 * @param string $description Nouvelle description
 * @return bool Succès ou échec
 */
function updateCommandeDescription($commande_id, $description) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return false;
    }
    
    $commande_data['description'] = $description;
    
    return saveCommandeData($commande_data);
}

/**
 * Génère un PDF pour la distribution de la commande
 * @param string $commande_id ID de la commande
 * @return string|null Chemin du fichier PDF ou null si échec
 */
function generateCommandePDF($commande_id) {
    $commande_data = getCommandeData($commande_id);
    if (!$commande_data) {
        return null;
    }
    
    // Créer le contenu HTML pour le PDF
    $html = '<html><head><style>';
    $html .= 'body { font-family: Arial, sans-serif; }';
    $html .= 'table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }';
    $html .= 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    $html .= 'th { background-color: #f2f2f2; }';
    $html .= '.total { font-weight: bold; }';
    $html .= '</style></head><body>';
    
    $html .= '<h1>' . htmlspecialchars($commande_data['titre']) . ' - Récapitulatif</h1>';
    
    if (isset($commande_data['description']) && !empty($commande_data['description'])) {
        $html .= '<p>' . nl2br(htmlspecialchars($commande_data['description'])) . '</p>';
    }
    
    $html .= '<p><strong>Date de récupération :</strong> ' . (new DateTime($commande_data['date_recuperation']))->format('d/m/Y H:i') . '</p>';
    $html .= '<p><strong>Adresse :</strong> ' . htmlspecialchars($commande_data['adresse_recuperation']) . '</p>';
    
    // Tableau récapitulatif par participant
    $html .= '<h2>Récapitulatif par participant</h2>';
    $html .= '<table>';
    $html .= '<tr><th>Participant</th><th>Produits</th><th>Montant</th><th>Frais</th><th>Total</th><th>Payé</th></tr>';
    
    foreach ($commande_data['participants'] as $participant) {
        $user_data = getUserData($participant['user_id']);
        $user_name = $user_data ? $user_data['prenom'] . ' ' . $user_data['nom'] : 'Utilisateur #' . $participant['user_id'];
        
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($user_name) . '</td>';
        
        // Liste des produits
        $html .= '<td><ul>';
        foreach ($participant['commandes'] as $article) {
            $variation_info = getVariationInfo($commande_data, $article['variation_id']);
            if ($variation_info) {
                $html .= '<li>' . htmlspecialchars($variation_info['produit_nom'] . ' - ' . $variation_info['variation_nom']) . ' x ' . $article['quantite'] . '</li>';
            }
        }
        $html .= '</ul></td>';
        
        $html .= '<td>' . number_format($participant['montant_produits'], 2, ',', ' ') . ' €</td>';
        $html .= '<td>' . number_format($participant['part_frais_port'], 2, ',', ' ') . ' €</td>';
        $html .= '<td>' . number_format($participant['montant_total'], 2, ',', ' ') . ' €</td>';
        $html .= '<td>' . ($participant['statut_paiement'] ? 'Oui' : 'Non') . '</td>';
        $html .= '</tr>';
    }
    
    // Ligne de total
    $html .= '<tr class="total">';
    $html .= '<td colspan="2">Total</td>';
    $html .= '<td>' . number_format($commande_data['montant_total'], 2, ',', ' ') . ' €</td>';
    $html .= '<td>' . number_format($commande_data['frais_port'], 2, ',', ' ') . ' €</td>';
    $html .= '<td>' . number_format($commande_data['montant_total'] + $commande_data['frais_port'], 2, ',', ' ') . ' €</td>';
    $html .= '<td></td>';
    $html .= '</tr>';
    
    $html .= '</table>';
    
    // Tableau récapitulatif par produit
    $html .= '<h2>Récapitulatif par produit</h2>';
    $html .= '<table>';
    $html .= '<tr><th>Produit</th><th>Variation</th><th>Quantité</th><th>Participants</th></tr>';
    
    foreach ($commande_data['produits'] as $produit) {
        foreach ($produit['variations'] as $variation) {
            $total_quantite = 0;
            $participants_list = [];
            
            foreach ($commande_data['participants'] as $participant) {
                foreach ($participant['commandes'] as $article) {
                    if ($article['variation_id'] === $variation['id']) {
                        $total_quantite += $article['quantite'];
                        
                        $user_data = getUserData($participant['user_id']);
                        $user_name = $user_data ? $user_data['prenom'] . ' ' . $user_data['nom'] : 'Utilisateur #' . $participant['user_id'];
                        $participants_list[] = $user_name . ' (' . $article['quantite'] . ')';
                    }
                }
            }
            
            if ($total_quantite > 0) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($produit['nom']) . '</td>';
                $html .= '<td>' . htmlspecialchars($variation['nom']) . '</td>';
                $html .= '<td>' . $total_quantite . '</td>';
                $html .= '<td>' . htmlspecialchars(implode(', ', $participants_list)) . '</td>';
                $html .= '</tr>';
            }
        }
    }
    
    $html .= '</table>';
    $html .= '</body></html>';
    
    // Générer le PDF (utilisation d'une bibliothèque comme TCPDF ou MPDF serait nécessaire)
    // Pour l'instant, on retourne simplement le HTML
    $pdf_path = 'data/exports/' . $commande_id . '_' . date('Ymd_His') . '.html';
    file_put_contents($pdf_path, $html);
    
    return $pdf_path;
}

/**
 * Importe une commande depuis un fichier JSON
 * 
 * @param string $json_data Données JSON de la commande
 * @param int $user_id ID de l'utilisateur qui importe la commande
 * @return string|false ID de la nouvelle commande ou false en cas d'erreur
 */
function importCommandeJSON($json_data, $user_id) {
    global $db;
    
    try {
        // Décoder les données JSON
        $data = json_decode($json_data, true);
        if (!$data || !isset($data['titre'], $data['type_commande'], $data['date_limite'], $data['date_recuperation'], $data['adresse_recuperation'], $data['produits'])) {
            return false;
        }
        
        // Générer un nouvel ID pour la commande
        $commande_id = generateUniqueId();
        
        // Créer la commande
        $commande = [
            'id' => $commande_id,
            'admin_id' => $user_id,
            'titre' => $data['titre'],
            'type_commande' => $data['type_commande'],
            'date_limite' => $data['date_limite'],
            'date_recuperation' => $data['date_recuperation'],
            'adresse_recuperation' => $data['adresse_recuperation'],
            'description' => $data['description'] ?? '',
            'public' => $data['public'] ?? false,
            'produits' => [],
            'participants' => [],
            'paliers_frais' => $data['paliers_frais'] ?? getDefaultPaliers(),
            'poids_total' => 0,
            'montant_total' => 0,
            'frais_port' => 0
        ];
        
        // Ajouter les produits et leurs variations
        foreach ($data['produits'] as $produit) {
            if (!isset($produit['nom'], $produit['variations'])) {
                continue;
            }
            
            $produit_id = generateUniqueId();
            $variations = [];
            
            foreach ($produit['variations'] as $variation) {
                if (!isset($variation['nom'], $variation['poids'], $variation['prix'])) {
                    continue;
                }
                
                $variations[] = [
                    'id' => generateUniqueId(),
                    'nom' => $variation['nom'],
                    'poids' => (float) $variation['poids'],
                    'prix' => (float) $variation['prix']
                ];
            }
            
            if (!empty($variations)) {
                $commande['produits'][] = [
                    'id' => $produit_id,
                    'nom' => $produit['nom'],
                    'url' => $produit['url'] ?? '',
                    'variations' => $variations
                ];
            }
        }
        
        // Enregistrer la commande
        if (isset($db) && is_object($db)) {
            if (!isset($db->commandes)) {
                $db->commandes = new stdClass();
            }
            $db->commandes->{$commande_id} = $commande;
        }
        
        // Sauvegarder également dans un fichier JSON
        $commande_file = COMMANDES_DIR . '/' . $commande_id . '.json';
        writeJsonFile($commande_file, $commande);
        
        return $commande_id;
    } catch (Exception $e) {
        error_log('Erreur lors de l\'importation de la commande: ' . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut public d'une commande
 * 
 * @param string $commande_id ID de la commande
 * @param bool $is_public Statut public (true/false)
 * @return bool Succès ou échec
 */
function updateCommandePublicStatus($commande_id, $is_public) {
    global $db;
    
    try {
        // Récupérer les données de la commande
        $commande = getCommandeData($commande_id);
        if (!$commande) {
            return false;
        }
        
        // Mettre à jour le statut public
        $commande['public'] = (bool) $is_public;
        
        // Sauvegarder les modifications
        if (!saveCommandeData($commande)) {
            return false;
        }
        
        // Mettre à jour également dans $db si disponible
        if (isset($db) && isset($db->commandes) && is_object($db->commandes) && isset($db->commandes->{$commande_id})) {
            $db->commandes->{$commande_id} = $commande;
        }
        
        return true;
    } catch (Exception $e) {
        error_log('Erreur lors de la mise à jour du statut public: ' . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les commandes publiques
 * 
 * @return array Liste des commandes publiques
 */
function getPublicCommandes() {
    global $db;
    
    $public_commandes = [];
    
    try {
        // Vérifier si $db et $db->commandes existent
        if (isset($db) && isset($db->commandes) && is_object($db->commandes)) {
            foreach ($db->commandes as $commande_id => $commande) {
                if (isset($commande['public']) && $commande['public'] === true) {
                    $public_commandes[] = $commande;
                }
            }
        } else {
            // Méthode alternative pour récupérer les commandes si $db n'est pas disponible
            if (file_exists(COMMANDES_DIR)) {
                $files = glob(COMMANDES_DIR . '/*.json');
                foreach ($files as $file) {
                    $commande_data = readJsonFile($file);
                    if ($commande_data && isset($commande_data['public']) && $commande_data['public'] === true) {
                        $public_commandes[] = $commande_data;
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Erreur lors de la récupération des commandes publiques: ' . $e->getMessage());
    }
    
    return $public_commandes;
}

/**
 * Vérifie la validité d'un token Cloudflare Turnstile
 * 
 * @param string $token Token Turnstile à vérifier
 * @return bool Succès ou échec
 */
function verifyTurnstile($token) {
    if (!USE_TURNSTILE) {
        return true; // Si Turnstile n'est pas activé, on considère que c'est valide
    }
    
    if (empty($token)) {
        return false;
    }
    
    $data = [
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['success']) && $result['success'] === true;
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