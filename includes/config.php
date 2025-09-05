<?php
/**
 * Configuration de l'application
 */

// Nom de l'application
define('APP_NAME', 'Groupon');

// URL de base de l'application (sans slash final)
define('APP_URL', 'http://localhost');

// Chemin vers le dossier de données (pour compatibilité, mais plus utilisé)
define('DATA_DIR', __DIR__ . '/../data');
define('USERS_DIR', DATA_DIR . '/users');
define('COMMANDES_DIR', DATA_DIR . '/commandes');
define('LANGS_DIR', __DIR__ . '/../langs');

// Langue par défaut
define('DEFAULT_LANG', 'fr');

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Vérifier si la base de données est configurée
if (!file_exists(__DIR__ . '/database.php')) {
    // Rediriger vers l'installateur si pas encore configuré
    header('Location: ../install.php');
    exit;
}

// Charger la configuration de la base de données
require_once __DIR__ . '/database.php';

// Charger les fonctions MySQL
require_once __DIR__ . '/functions_mysql.php';

// Configuration de Cloudflare Turnstile
define('USE_TURNSTILE', false); // Mettre à true pour activer Turnstile
define('TURNSTILE_SITE_KEY', ''); // Votre clé de site Turnstile
define('TURNSTILE_SECRET_KEY', ''); // Votre clé secrète Turnstile

// Configuration SMTP pour l'envoi d'emails
define('SMTP_HOST', 'localhost'); // Serveur SMTP
define('SMTP_PORT', 25); // Port SMTP
define('SMTP_SECURE', ''); // Sécurité: '', 'ssl' ou 'tls'
define('SMTP_AUTH', false); // Authentification SMTP
define('SMTP_USERNAME', ''); // Nom d'utilisateur SMTP
define('SMTP_PASSWORD', ''); // Mot de passe SMTP
define('SMTP_FROM_EMAIL', 'noreply@example.com'); // Email expéditeur
define('SMTP_FROM_NAME', 'Groupon App'); // Nom expéditeur

// Email de l'expéditeur (pour compatibilité)
define('EMAIL_FROM', SMTP_FROM_EMAIL);

// Timeout en secondes pour les verrous de fichiers
define('LOCK_TIMEOUT', 10);

/**
 * Ne pas modifier ci-dessous
 */

// Vérifier si le dossier de données existe, sinon le créer
if (!file_exists(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Sous-dossiers de données
$data_subdirs = ['exports', 'users', 'commandes'];
foreach ($data_subdirs as $subdir) {
    $path = DATA_DIR . '/' . $subdir;
    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }
}

// Démarrage de session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialisation du thème
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