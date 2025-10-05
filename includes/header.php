<?php 
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// S'assurer que les variables de session essentielles sont initialisées
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = DEFAULT_LANG;
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_SESSION['lang']) ? $_SESSION['lang'] : 'fr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Groupons'; ?></title>
    <link rel="icon" type="image/png" href="logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <?php if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark'): ?>
    <link rel="stylesheet" href="css/dark-theme.css">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</head>
<body class="<?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'dark-mode' : ''; ?>">
    <nav class="navbar navbar-expand-lg <?php echo (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') ? 'navbar-dark bg-dark' : 'navbar-light bg-light'; ?>">
        <div class="container">
            <a class="navbar-brand" href="<?php echo isLoggedIn() ? 'dashboard.php' : 'index.php'; ?>">
                <div class="logo-container">
                    <img src="logo.png" alt="<?php echo htmlspecialchars(APP_NAME); ?>" height="40">
                </div>
            </a>
            
            <!-- Menu desktop -->
            <div class="d-none d-lg-flex navbar-nav">
                <a class="nav-link" href="<?php echo isLoggedIn() ? 'dashboard.php' : 'index.php'; ?>"><?php echo __('home'); ?></a>
                <?php if (isLoggedIn()): ?>
                <a class="nav-link" href="aide.php"><?php echo __('help'); ?></a>
                <?php endif; ?>
            </div>
            
            <!-- Contrôles desktop -->
            <div class="d-none d-lg-flex navbar-nav ms-auto">
                <?php if (isLoggedIn()): ?>
                <div class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="create_commande.php"><i class="fas fa-plus"></i> <?php echo __('create_order'); ?></a></li>
                        <li><a class="dropdown-item" href="import_commande.php"><i class="fas fa-file-import"></i> <?php echo __('import_order'); ?></a></li>
                    </ul>
                </div>
                <?php endif; ?>
                <div class="nav-item me-3 d-flex align-items-center">
                    <label class="theme-switch mb-0">
                        <input type="checkbox" id="theme-switch" <?php echo isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo isset($_SESSION['lang']) ? strtoupper($_SESSION['lang']) : strtoupper(DEFAULT_LANG); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                        <?php foreach ($available_languages as $code => $name): ?>
                        <li><a class="dropdown-item <?php echo (isset($_SESSION['lang']) && $_SESSION['lang'] === $code) ? 'active' : ''; ?>" href="change_lang.php?lang=<?php echo $code; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"><?php echo $name; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if (isLoggedIn()): ?>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> <?php echo __('profile'); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a class="nav-link" href="login.php"><?php echo __('login'); ?></a>
                <a class="nav-link" href="register.php"><?php echo __('register'); ?></a>
                <?php endif; ?>
            </div>
            
            <!-- Menu mobile compact -->
            <div class="d-lg-none d-flex align-items-center">
                <a class="nav-link me-2" href="<?php echo isLoggedIn() ? 'dashboard.php' : 'index.php'; ?>" title="<?php echo __('home'); ?>">
                    <i class="fas fa-home"></i>
                </a>
                <?php if (isLoggedIn()): ?>
                <a class="nav-link me-2" href="aide.php" title="<?php echo __('help'); ?>">
                    <i class="fas fa-question-circle"></i>
                </a>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                <div class="nav-item dropdown me-2">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdownMobile" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo __('administration'); ?>">
                        <i class="fas fa-cog"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdownMobile">
                        <li><a class="dropdown-item" href="create_commande.php"><i class="fas fa-plus"></i> <?php echo __('create_order'); ?></a></li>
                        <li><a class="dropdown-item" href="import_commande.php"><i class="fas fa-file-import"></i> <?php echo __('import_order'); ?></a></li>
                    </ul>
                </div>
                <?php endif; ?>
                <div class="nav-item me-2">
                    <label class="theme-switch mb-0">
                        <input type="checkbox" id="theme-switch-mobile" <?php echo isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="nav-item dropdown me-2">
                    <a class="nav-link dropdown-toggle" href="#" id="langDropdownMobile" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo isset($_SESSION['lang']) ? strtoupper($_SESSION['lang']) : strtoupper(DEFAULT_LANG); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdownMobile">
                        <?php foreach ($available_languages as $code => $name): ?>
                        <li><a class="dropdown-item <?php echo (isset($_SESSION['lang']) && $_SESSION['lang'] === $code) ? 'active' : ''; ?>" href="change_lang.php?lang=<?php echo $code; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"><?php echo $name; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if (isLoggedIn()): ?>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdownMobile" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> <?php echo __('profile'); ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a class="nav-link me-2" href="login.php" title="<?php echo __('login'); ?>">
                    <i class="fas fa-sign-in-alt"></i>
                </a>
                <a class="nav-link" href="register.php" title="<?php echo __('register'); ?>">
                    <i class="fas fa-user-plus"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
        <?php 
        // Supprimer le message flash après affichage
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        endif; 
        ?>