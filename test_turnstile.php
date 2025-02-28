<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Vérifier si Turnstile est activé
$turnstile_active = USE_TURNSTILE;
$site_key = TURNSTILE_SITE_KEY;
$secret_key = TURNSTILE_SECRET_KEY;

// Traitement du formulaire
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification Turnstile
    $token = $_POST['cf-turnstile-response'] ?? '';
    $verification_result = verifyTurnstile($token);
    
    if ($verification_result) {
        $success = true;
        $message = 'Vérification Turnstile réussie ! La configuration fonctionne correctement.';
    } else {
        $message = 'Échec de la vérification Turnstile. Vérifiez votre configuration.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Turnstile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .result { padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test de configuration Turnstile</h1>
        
        <?php if (!$turnstile_active): ?>
            <div class="alert alert-warning">
                <strong>Attention :</strong> Turnstile n'est pas activé dans votre configuration.
                <p>Pour l'activer, modifiez le fichier <code>includes/config.php</code> :</p>
                <pre>define('USE_TURNSTILE', true);
define('TURNSTILE_SITE_KEY', 'votre-clé-de-site');
define('TURNSTILE_SECRET_KEY', 'votre-clé-secrète');</pre>
            </div>
        <?php elseif (empty($site_key) || empty($secret_key)): ?>
            <div class="alert alert-danger">
                <strong>Erreur :</strong> Turnstile est activé mais les clés ne sont pas configurées.
                <p>Configurez vos clés dans le fichier <code>includes/config.php</code>.</p>
            </div>
        <?php else: ?>
            <?php if ($message): ?>
                <div class="result <?php echo $success ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Formulaire de test</h5>
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Votre nom</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <!-- Widget Turnstile -->
                        <div class="mb-3">
                            <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($site_key); ?>"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Tester Turnstile</button>
                    </form>
                </div>
            </div>
            
            <!-- Script Turnstile -->
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php endif; ?>
        
        <div class="mt-4">
            <h3>Configuration actuelle :</h3>
            <pre>
USE_TURNSTILE: <?php echo $turnstile_active ? 'true' : 'false'; ?>

TURNSTILE_SITE_KEY: <?php echo !empty($site_key) ? htmlspecialchars($site_key) : 'Non configuré'; ?>

TURNSTILE_SECRET_KEY: <?php echo !empty($secret_key) ? '********' : 'Non configuré'; ?>
            </pre>
        </div>
        
        <p class="mt-4"><a href="dashboard.php" class="btn btn-secondary">Retour au tableau de bord</a></p>
    </div>
</body>
</html> 