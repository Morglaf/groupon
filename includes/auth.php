<?php
require_once 'config.php';
require_once 'functions_mysql.php';

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Retourne l'ID de l'utilisateur connecté
 * @return string|null
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Retourne les données de l'utilisateur connecté
 * @return array|null
 */
function getCurrentUser() {
    $user_id = getCurrentUserId();
    if (!$user_id) {
        return null;
    }
    
    return getUserData($user_id);
}

/**
 * Connecte un utilisateur
 * @param array $user_data Données de l'utilisateur
 */
function loginUser($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['user_name'] = $user_data['prenom'] . ' ' . $user_data['nom'];
    $_SESSION['user_email'] = $user_data['email'];
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser() {
    // Sauvegarder les préférences utilisateur
    $theme = $_SESSION['theme'] ?? 'light';
    $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
    
    // Nettoyer la session
    $_SESSION = array();
    
    // Restaurer les préférences
    $_SESSION['theme'] = $theme;
    $_SESSION['lang'] = $lang;
    
    // Ne pas détruire complètement la session pour conserver les préférences
    // session_destroy();
}

/**
 * Redirige l'utilisateur si non connecté
 * @param string $redirect_url URL de redirection
 */
function requireLogin($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Vérifie si l'utilisateur est admin d'une commande
 * @param string $commande_id ID de la commande
 * @return bool
 */
function currentUserIsCommandeAdmin($commande_id) {
    $user_id = getCurrentUserId();
    if (!$user_id) {
        return false;
    }
    
    return isCommandeAdmin($commande_id, $user_id);
}

/**
 * Redirige l'utilisateur s'il n'est pas admin d'une commande
 * @param string $commande_id ID de la commande
 * @param string $redirect_url URL de redirection
 */
function requireCommandeAdmin($commande_id, $redirect_url = '/index.php') {
    if (!currentUserIsCommandeAdmin($commande_id)) {
        header('Location: ' . $redirect_url);
        exit;
    }
}

/**
 * Vérifie si l'utilisateur est participant d'une commande (ou admin)
 * @param string $commande_id ID de la commande
 * @return bool
 */
function currentUserCanAccessCommande($commande_id) {
    $user_id = getCurrentUserId();
    if (!$user_id) {
        return false;
    }
    
    return isCommandeAdmin($commande_id, $user_id) || isCommandeParticipant($commande_id, $user_id);
}

/**
 * Redirige l'utilisateur s'il n'a pas accès à une commande
 * @param string $commande_id ID de la commande
 * @param string $redirect_url URL de redirection
 */
function requireCommandeAccess($commande_id, $redirect_url = '/index.php') {
    if (!currentUserCanAccessCommande($commande_id)) {
        header('Location: ' . $redirect_url);
        exit;
    }
}
?>