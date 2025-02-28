<?php
require_once 'includes/config.php';

// Vérifier si un thème est fourni
if (isset($_GET['theme']) && in_array($_GET['theme'], ['light', 'dark'])) {
    $_SESSION['theme'] = $_GET['theme'];
}

// Répondre avec le thème actuel
header('Content-Type: application/json');
echo json_encode(['theme' => $_SESSION['theme']]);
?>