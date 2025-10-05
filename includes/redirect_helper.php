<?php
/**
 * Helper pour les redirections avec fallback
 */

/**
 * Effectue une redirection avec fallback JavaScript
 * @param string $url URL de destination
 * @param string $message Message à afficher pendant la redirection
 */
function safeRedirect($url, $message = 'Redirection en cours...') {
    // Debug
    error_log("Safe redirect to: " . $url);
    
    // Nettoyer l'URL
    $url = ltrim($url, '/');
    
    // Redirection avec fallback JavaScript
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '">
    <title>Redirection</title>
</head>
<body>
    <script>window.location.href = "' . htmlspecialchars($url) . '";</script>
    <p>' . htmlspecialchars($message) . '</p>
    <p><a href="' . htmlspecialchars($url) . '">Cliquez ici si la redirection ne fonctionne pas</a></p>
</body>
</html>';
    exit;
}
?>
