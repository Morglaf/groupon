<?php
require_once 'includes/config.php';

// Vérifier si une langue est fournie
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Rediriger vers la page d'origine
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '/';
header('Location: ' . $redirect);
exit;
?>