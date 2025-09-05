<?php
/**
 * Gestion des langues
 */

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
    $_SESSION['lang'] = array_key_exists($browser_lang, $available_languages) ? $browser_lang : DEFAULT_LANG;
}

// Charger les traductions
$lang = $_SESSION['lang'];
$translations = [];

// Essayer de charger le fichier de langue
$lang_file = LANGS_DIR . "/{$lang}.php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    // Charger la langue par défaut
    include LANGS_DIR . "/" . DEFAULT_LANG . ".php";
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