<?php
require_once 'includes/config.php';
require_once 'includes/functions_mysql.php';

// Adresse email de test (remplacez par votre adresse)
$test_email = 'testemail';

// Envoi d'un email de test
$subject = 'Test de configuration SMTP';
$message = '<h1>Test de configuration SMTP</h1>';
$message .= '<p>Si vous recevez cet email, la configuration SMTP fonctionne correctement.</p>';
$message .= '<p>Date et heure du test : ' . date('Y-m-d H:i:s') . '</p>';

$result = sendEmail($test_email, $subject, $message);

// Affichage du résultat
echo '<h1>Test de configuration SMTP</h1>';
if ($result) {
    echo '<div style="color: green; padding: 10px; border: 1px solid green; margin: 10px 0;">
        Email envoyé avec succès à ' . htmlspecialchars($test_email) . '.
        Vérifiez votre boîte de réception (et éventuellement le dossier spam).
    </div>';
} else {
    echo '<div style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;">
        Échec de l\'envoi de l\'email. Vérifiez la configuration SMTP dans includes/config.php.
    </div>';
    
    // Afficher les informations de configuration (sans les mots de passe)
    echo '<h2>Configuration actuelle :</h2>';
    echo '<pre>';
    echo 'SMTP_HOST: ' . SMTP_HOST . "\n";
    echo 'SMTP_PORT: ' . SMTP_PORT . "\n";
    echo 'SMTP_SECURE: ' . SMTP_SECURE . "\n";
    echo 'SMTP_AUTH: ' . (SMTP_AUTH ? 'true' : 'false') . "\n";
    echo 'SMTP_USERNAME: ' . SMTP_USERNAME . "\n";
    echo 'SMTP_FROM_EMAIL: ' . SMTP_FROM_EMAIL . "\n";
    echo 'SMTP_FROM_NAME: ' . SMTP_FROM_NAME . "\n";
    echo '</pre>';
}
?>

<p><a href="dashboard.php">Retour au tableau de bord</a></p> 