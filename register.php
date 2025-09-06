<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
require_once 'includes/header.php';
$page_title = __('register');

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$form_data = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
];

// Récupérer le paramètre de redirection s'il existe
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $form_data = [
        'nom' => trim($_POST['nom'] ?? ''),
        'prenom' => trim($_POST['prenom'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
    ];
    
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Récupérer la redirection du formulaire si présente
    if (isset($_POST['redirect'])) {
        $redirect = $_POST['redirect'];
    }
    
    // Validation
    if (empty($form_data['nom'])) {
        $errors[] = __('required_field');
    }
    
    if (empty($form_data['prenom'])) {
        $errors[] = __('required_field');
    }
    
    if (empty($form_data['email'])) {
        $errors[] = __('required_field');
    } elseif (!isValidEmail($form_data['email'])) {
        $errors[] = __('invalid_email');
    } elseif (emailExists($form_data['email'])) {
        $errors[] = __('email_exists');
    }
    
    if (empty($password)) {
        $errors[] = __('required_field');
    } elseif (strlen($password) < 8) {
        $errors[] = __('min_8_chars');
    }
    
    if ($password !== $password_confirm) {
        $errors[] = __('passwords_not_match');
    }
    
    // Vérification Turnstile si activé
    if (USE_TURNSTILE) {
        $turnstile_token = $_POST['cf-turnstile-response'] ?? '';
        if (!verifyTurnstile($turnstile_token)) {
            $errors[] = __('turnstile_failed');
        }
    }
    
    // Créer l'utilisateur si pas d'erreurs
    if (empty($errors)) {
        $user_id = createUser($form_data['prenom'], $form_data['nom'], $form_data['email'], $password);
        
        if ($user_id) {
            // Connexion automatique
            $user_data = getUserData($user_id);
            loginUser($user_data);
            
            $_SESSION['flash_message'] = __('account_created');
            $_SESSION['flash_type'] = 'success';
            
            // Redirection
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = __('error_occurred');
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h1 class="h3 mb-0"><?php echo __('register'); ?></h1>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prenom" class="form-label"><?php echo __('first_name'); ?></label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($form_data['prenom']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label"><?php echo __('last_name'); ?></label>
                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($form_data['nom']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo __('email'); ?></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label"><?php echo __('password'); ?></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text"><?php echo __('min_8_chars'); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label"><?php echo __('confirm_password'); ?></label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
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
                        <button type="submit" class="btn btn-primary"><?php echo __('register'); ?></button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <?php echo __('already_registered'); ?> <a href="/login.php"><?php echo __('login'); ?></a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>