<?php
// Configuration de l'application
define('APP_NAME', 'Groupon');
define('APP_URL', 'http://localhost'); // À modifier selon l'environnement
define('DATA_DIR', __DIR__ . '/../data');
define('USERS_DIR', DATA_DIR . '/users');
define('COMMANDES_DIR', DATA_DIR . '/commandes');
define('EMAIL_FROM', 'noreply@example.com'); // À modifier
define('LOCK_TIMEOUT', 10); // Timeout en secondes pour les verrous de fichiers
define('LANGS_DIR', __DIR__ . '/../langs');

// Démarrage de session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration du thème
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light'; // Thème par défaut
}

// Configuration de la langue
$available_languages = [
    'fr' => 'Français',
    'en' => 'English',
    'de' => 'Deutsch',
    'es' => 'Español'
];

// Définir la langue par défaut ou celle choisie par l'utilisateur
if (!isset($_SESSION['lang']) || !array_key_exists($_SESSION['lang'], $available_languages)) {
    // Détecter la langue du navigateur
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);
    $_SESSION['lang'] = array_key_exists($browser_lang, $available_languages) ? $browser_lang : 'fr';
}

// Charger les traductions
$lang = $_SESSION['lang'];
$translations = [];

// Essayer de charger le fichier de langue
$lang_file = LANGS_DIR . "/{$lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    // Charger le français par défaut
    include LANGS_DIR . "/fr.php";
}

/**
 * Fonction de traduction
 * @param string $key La clé de traduction
 * @return string La traduction ou la clé si non trouvée
 */
function __($key) {
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $key;
}
?>