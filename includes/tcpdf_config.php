<?php
/**
 * Configuration TCPDF pour l'export PDF
 */

// Vérifier si TCPDF est disponible
if (!class_exists('TCPDF')) {
    // Charger TCPDF depuis le dossier vendor local
    $tcpdf_path = __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    if (file_exists($tcpdf_path)) {
        require_once $tcpdf_path;
    } else {
        error_log('TCPDF non trouvé dans ' . $tcpdf_path);
    }
}

// Configuration TCPDF si disponible
if (class_exists('TCPDF')) {
    // Constantes TCPDF
    define('PDF_PAGE_ORIENTATION', 'P'); // Portrait
    define('PDF_UNIT', 'mm');
    define('PDF_PAGE_FORMAT', 'A4');
    define('PDF_CREATOR', defined('APP_NAME') ? APP_NAME : 'Groupon');
    define('PDF_AUTHOR', defined('APP_NAME') ? APP_NAME : 'Groupon');
    define('PDF_TITLE', 'Export de commande');
    define('PDF_SUBJECT', 'Commande groupée');
    define('PDF_KEYWORDS', 'commande, groupée, export');
    
    // Configuration des polices
    define('PDF_FONT_NAME_MAIN', 'helvetica');
    define('PDF_FONT_SIZE_MAIN', 10);
    define('PDF_FONT_NAME_DATA', 'helvetica');
    define('PDF_FONT_SIZE_DATA', 8);
    
    // Configuration des marges
    define('PDF_MARGIN_LEFT', 15);
    define('PDF_MARGIN_TOP', 20);
    define('PDF_MARGIN_RIGHT', 15);
    define('PDF_MARGIN_BOTTOM', 20);
    define('PDF_MARGIN_HEADER', 10);
    define('PDF_MARGIN_FOOTER', 10);
    
    // Configuration de l'en-tête et pied de page
    define('PDF_HEADER_LOGO', '');
    define('PDF_HEADER_LOGO_WIDTH', 0);
    define('PDF_HEADER_TITLE', defined('APP_NAME') ? APP_NAME : 'Groupon');
    define('PDF_HEADER_STRING', 'Export de commande groupée');
    
    // Configuration des couleurs
    define('PDF_COLOR_BLACK', '0,0,0');
    define('PDF_COLOR_WHITE', '255,255,255');
    define('PDF_COLOR_GRAY', '128,128,128');
    
    // Configuration de l'encodage
    define('PDF_ENCODING', 'UTF-8');
    
} else {
    // TCPDF non disponible
    error_log('TCPDF non disponible - fonctionnalité d\'export PDF désactivée');
}
?>
