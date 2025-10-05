<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/lang.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_data = getCurrentUserData();
$errors = [];
$success_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Mise à jour du profil
        $form_data = [
            'prenom' => trim($_POST['prenom'] ?? ''),
            'nom' => trim($_POST['nom'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'telephone' => trim($_POST['telephone'] ?? ''),
        ];
        
        // Validation
        if (empty($form_data['prenom'])) {
            $errors[] = __('required_field');
        }
        
        if (empty($form_data['nom'])) {
            $errors[] = __('required_field');
        }
        
        if (empty($form_data['email'])) {
            $errors[] = __('required_field');
        } elseif (!isValidEmail($form_data['email'])) {
            $errors[] = __('invalid_email');
        } elseif ($form_data['email'] !== $user_data['email'] && emailExists($form_data['email'])) {
            $errors[] = __('email_exists');
        }
        
        // Mettre à jour si pas d'erreurs
        if (empty($errors)) {
            if (updateUserProfile($user_data['id'], $form_data)) {
                $success_message = __('profile_updated');
                $user_data = getCurrentUserData(); // Recharger les données
            } else {
                $errors[] = __('error_occurred');
            }
        }
        
    } elseif ($action === 'change_password') {
        // Changement de mot de passe
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password)) {
            $errors[] = __('current_password_required');
        } elseif (!password_verify($current_password, $user_data['password_hash'])) {
            $errors[] = __('current_password_incorrect');
        }
        
        if (empty($new_password)) {
            $errors[] = __('required_field');
        } elseif (strlen($new_password) < 8) {
            $errors[] = __('min_8_chars');
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = __('passwords_not_match');
        }
        
        // Changer le mot de passe si pas d'erreurs
        if (empty($errors)) {
            if (updateUserPassword($user_data['id'], $new_password)) {
                $success_message = __('password_changed');
            } else {
                $errors[] = __('error_occurred');
            }
        }
    }
}

$page_title = __('profile');
require_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0"><?php echo __('profile'); ?></h1>
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
                    
                    <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Informations générales -->
                    <div class="mb-4">
                        <h4><?php echo __('personal_information'); ?></h4>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label"><?php echo __('first_name'); ?></label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" 
                                           value="<?php echo htmlspecialchars($user_data['prenom']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label"><?php echo __('last_name'); ?></label>
                                    <input type="text" class="form-control" id="nom" name="nom" 
                                           value="<?php echo htmlspecialchars($user_data['nom']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label"><?php echo __('email'); ?></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                            </div>
                            
                            <?php
                            // Vérifier si la colonne telephone existe
                            $telephone_exists = false;
                            try {
                                $pdo = getDB();
                                $stmt = $pdo->query("SHOW COLUMNS FROM " . DB_PREFIX . "users LIKE 'telephone'");
                                $telephone_exists = $stmt->fetch() !== false;
                            } catch (Exception $e) {
                                // Ignorer l'erreur
                            }
                            ?>
                            
                            <?php if ($telephone_exists): ?>
                            <div class="mb-3">
                                <label for="telephone" class="form-label"><?php echo __('phone'); ?> <small class="text-muted">(<?php echo __('optional'); ?>)</small></label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       value="<?php echo htmlspecialchars($user_data['telephone'] ?? ''); ?>" 
                                       placeholder="<?php echo __('phone_placeholder'); ?>">
                                <div class="form-text"><?php echo __('phone_help'); ?></div>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    Le champ téléphone sera disponible après la migration de la base de données.
                                    <br><a href="migrate_add_telephone.php" class="btn btn-sm btn-outline-primary mt-2">Exécuter la migration</a>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo __('save_changes'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <!-- Changement de mot de passe -->
                    <div class="mb-4">
                        <h4><?php echo __('change_password'); ?></h4>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label"><?php echo __('current_password'); ?></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label"><?php echo __('new_password'); ?></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="form-text"><?php echo __('min_8_chars'); ?></div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label"><?php echo __('confirm_password'); ?></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> <?php echo __('change_password'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <!-- Informations du compte -->
                    <div class="mb-4">
                        <h4><?php echo __('account_information'); ?></h4>
                        <div class="row">
                            <div class="col-md-6">
                                <strong><?php echo __('member_since'); ?>:</strong><br>
                                <?php echo date('d/m/Y', strtotime($user_data['date_inscription'])); ?>
                            </div>
                            <div class="col-md-6">
                                <strong><?php echo __('last_update'); ?>:</strong><br>
                                <?php echo date('d/m/Y H:i', strtotime($user_data['updated_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?php echo __('back_to_dashboard'); ?>
                        </a>
                        <a href="logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
