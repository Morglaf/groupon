<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
require_once 'includes/header.php';
$page_title = __('login');

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email)) {
        $errors[] = __('required_field');
    }
    
    if (empty($password)) {
        $errors[] = __('required_field');
    }
    
    // Vérification Turnstile si activé
    if (USE_TURNSTILE) {
        $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
        if (!verifyTurnstile($turnstile_token)) {
            $errors[] = __('turnstile_failed');
        }
    }
    
    // Authentification
    if (empty($errors)) {
        $user_data = authenticateUser($email, $password);
        
        if ($user_data) {
            // Connexion réussie
            loginUser($user_data);
            
            $_SESSION['flash_message'] = __('login_success');
            $_SESSION['flash_type'] = 'success';
            
            // Redirection
            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = __('wrong_credentials');
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h1 class="h3 mb-0"><?php echo __('login'); ?></h1>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo __('email'); ?></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo __('password'); ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <?php if (USE_TURNSTILE): ?>
                    <div class="mb-3">
                        <div class="cf-turnstile" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="light"></div>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                    <?php endif; ?>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?php echo __('login'); ?></button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <?php echo __('not_registered'); ?> <a href="/register.php"><?php echo __('sign_up'); ?></a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>