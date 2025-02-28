<?php
$page_title = "Connexion";
require_once 'includes/header.php';

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
        $errors[] = 'L\'email est requis.';
    }
    
    if (empty($password)) {
        $errors[] = 'Le mot de passe est requis.';
    }
    
    // Authentification
    if (empty($errors)) {
        $user_data = authenticateUser($email, $password);
        
        if ($user_data) {
            // Connexion réussie
            loginUser($user_data);
            
            $_SESSION['flash_message'] = 'Connexion réussie.';
            $_SESSION['flash_type'] = 'success';
            
            // Redirection
            $redirect = $_GET['redirect'] ?? 'dashboard.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $errors[] = 'Email ou mot de passe incorrect.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h1 class="h3 mb-0">Connexion</h1>
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
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <?php if (isset($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                    <?php endif; ?>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Se connecter</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                Pas encore de compte ? <a href="/register.php">Inscrivez-vous</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>