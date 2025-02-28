<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Déconnexion
logoutUser();

// Message flash
$_SESSION['flash_message'] = 'Vous avez été déconnecté.';
$_SESSION['flash_type'] = 'info';

// Redirection
header('Location: index.php');
exit;
?>