<?php 
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(APP_NAME); ?> <?php echo isset($page_title) ? ' - ' . htmlspecialchars($page_title) : ''; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/dark-theme.css">
</head>
<body class="<?php echo $_SESSION['theme'] === 'dark' ? 'dark-theme' : ''; ?>">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/"><?php echo htmlspecialchars(APP_NAME); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/"><?php echo __('home'); ?></a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php"><?php echo __('dashboard'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/create_commande.php"><?php echo __('create_order'); ?></a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item me-3 d-flex align-items-center">
                        <label class="theme-switch mb-0">
                            <input type="checkbox" id="theme-switch" <?php echo $_SESSION['theme'] === 'dark' ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo strtoupper($_SESSION['lang']); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="langDropdown">
                            <?php foreach ($available_languages as $code => $name): ?>
                            <li><a class="dropdown-item <?php echo $_SESSION['lang'] === $code ? 'active' : ''; ?>" href="/change_lang.php?lang=<?php echo $code; ?>&redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>"><?php echo $name; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php if (isLoggedIn()): ?>
                    <span class="nav-item nav-link text-light">
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a class="nav-link" href="/logout.php"><?php echo __('logout'); ?></a>
                    <?php else: ?>
                    <a class="nav-link" href="/login.php"><?php echo __('login'); ?></a>
                    <a class="nav-link" href="/register.php"><?php echo __('register'); ?></a>
                    <?php endif; ?>
                </div>
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