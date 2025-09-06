<?php
/**
 * Fichier de compatibilité - Les fonctions sont maintenant dans functions_mysql.php
 * Ce fichier est conservé pour éviter les erreurs d'inclusion
 */

// Redirection vers les fonctions MySQL
if (!file_exists(__DIR__ . '/functions_mysql.php')) {
    die('Erreur: functions_mysql.php non trouvé. Veuillez exécuter install.php');
}

// Les fonctions sont maintenant dans functions_mysql.php
// Ce fichier est conservé pour la compatibilité mais ne contient plus de fonctions
