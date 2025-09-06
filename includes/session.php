<?php
/**
 * Gestion des sessions utilisateur
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Obtenir l'ID de l'utilisateur connecté
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtenir les données de l'utilisateur connecté
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return getUserById(getCurrentUserId());
}

/**
 * Connecter un utilisateur
 */
function loginUser($userId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
}

/**
 * Déconnecter l'utilisateur
 */
function logoutUser() {
    session_destroy();
    session_start();
}

/**
 * Exiger que l'utilisateur soit connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Vérifier si l'utilisateur actuel est admin d'une commande
 */
function currentUserIsCommandeAdmin($commandeId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $commande = getCommandeData($commandeId);
    return $commande && $commande['admin_user_id'] === getCurrentUserId();
}

/**
 * Exiger que l'utilisateur soit admin d'une commande
 */
function requireCommandeAdmin($commandeId) {
    if (!currentUserIsCommandeAdmin($commandeId)) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Vérifier si l'utilisateur peut accéder à une commande
 */
function currentUserCanAccessCommande($commandeId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userId = getCurrentUserId();
    $commande = getCommandeData($commandeId);
    
    if (!$commande) {
        return false;
    }
    
    // Admin peut accéder
    if ($commande['admin_user_id'] === $userId) {
        return true;
    }
    
    // Participant peut accéder
    $participants = getCommandeParticipants($commandeId);
    foreach ($participants as $participant) {
        if ($participant['user_id'] === $userId) {
            return true;
        }
    }
    
    return false;
}

/**
 * Exiger que l'utilisateur puisse accéder à une commande
 */
function requireCommandeAccess($commandeId) {
    if (!currentUserCanAccessCommande($commandeId)) {
        header('Location: index.php');
        exit;
    }
}
?>
