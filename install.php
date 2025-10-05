<?php
/**
 * Script d'installation et de configuration MySQL
 * Interface web similaire à WordPress
 */

// Définir les constantes nécessaires pour l'installation
define('APP_NAME', 'Groupons');

// Vérifier si l'installation est déjà terminée
if (file_exists('includes/database.php')) {
    header('Location: index.php');
    exit;
}

$step = $_GET['step'] ?? 1;
$errors = [];
$success = false;

// Traitement du formulaire de configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_prefix = trim($_POST['db_prefix'] ?? 'groupon_');
    
    // Validation
    if (empty($db_host)) $errors[] = 'Le serveur de base de données est requis.';
    if (empty($db_name)) $errors[] = 'Le nom de la base de données est requis.';
    if (empty($db_user)) $errors[] = 'Le nom d\'utilisateur est requis.';
    
    if (empty($errors)) {
        // Tester la connexion
        try {
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la base de données si elle n'existe pas
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // Générer le fichier de configuration
            $config_content = generateDatabaseConfig($db_host, $db_name, $db_user, $db_pass, $db_prefix);
            
            if (file_put_contents('includes/database.php', $config_content)) {
                // Créer les tables
                if (createTables($pdo, $db_prefix)) {
                    $success = true;
                    $step = 3;
                } else {
                    $errors[] = 'Erreur lors de la création des tables.';
                }
            } else {
                $errors[] = 'Impossible de créer le fichier de configuration.';
            }
            
        } catch (PDOException $e) {
            $errors[] = 'Erreur de connexion : ' . $e->getMessage();
        }
    }
}

function generateDatabaseConfig($host, $name, $user, $pass, $prefix) {
    return "<?php
/**
 * Configuration de la base de données MySQL
 * Généré automatiquement par l'installateur
 */

// Configuration de la base de données
define('DB_HOST', '$host');
define('DB_NAME', '$name');
define('DB_USER', '$user');
define('DB_PASS', '$pass');
define('DB_PREFIX', '$prefix');
define('DB_CHARSET', 'utf8mb4');

// Connexion PDO
try {
    \$pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException \$e) {
    die('Erreur de connexion à la base de données : ' . \$e->getMessage());
}

// Fonction utilitaire pour obtenir la connexion PDO
function getDB() {
    global \$pdo;
    return \$pdo;
}
?>";
}

function createTables($pdo, $prefix) {
    try {
        // Table des utilisateurs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}users` (
                `id` varchar(32) NOT NULL,
                `email` varchar(255) NOT NULL,
                `nom` varchar(100) NOT NULL,
                `prenom` varchar(100) NOT NULL,
                `password_hash` varchar(255) NOT NULL,
                `date_inscription` datetime NOT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`),
                KEY `idx_date_inscription` (`date_inscription`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Table des commandes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}commandes` (
                `id` varchar(32) NOT NULL,
                `titre` varchar(255) NOT NULL,
                `admin_id` varchar(32) NOT NULL,
                `description` text,
                `type_commande` enum('poids','nombre','montant') NOT NULL,
                `date_creation` datetime NOT NULL,
                `date_limite` datetime NOT NULL,
                `date_recuperation` datetime NOT NULL,
                `adresse_recuperation` text NOT NULL,
                `montant_total` decimal(10,2) DEFAULT 0.00,
                `poids_total` decimal(8,3) DEFAULT 0.000,
                `frais_port` decimal(8,2) DEFAULT 0.00,
                `public` tinyint(1) DEFAULT 0,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_admin_id` (`admin_id`),
                KEY `idx_date_limite` (`date_limite`),
                KEY `idx_public` (`public`),
                KEY `idx_type_commande` (`type_commande`),
                FOREIGN KEY (`admin_id`) REFERENCES `{$prefix}users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Table des produits
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}produits` (
                `id` varchar(32) NOT NULL,
                `commande_id` varchar(32) NOT NULL,
                `nom` varchar(255) NOT NULL,
                `url` varchar(500),
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_commande_id` (`commande_id`),
                FOREIGN KEY (`commande_id`) REFERENCES `{$prefix}commandes`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Table des variations
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}variations` (
                `id` varchar(32) NOT NULL,
                `produit_id` varchar(32) NOT NULL,
                `nom` varchar(255) NOT NULL,
                `poids` decimal(8,3) NOT NULL,
                `prix` decimal(8,2) NOT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_produit_id` (`produit_id`),
                FOREIGN KEY (`produit_id`) REFERENCES `{$prefix}produits`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Table des participants
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}participants` (
                `id` varchar(32) NOT NULL,
                `commande_id` varchar(32) NOT NULL,
                `user_id` varchar(32) NOT NULL,
                `montant_produits` decimal(10,2) DEFAULT 0.00,
                `part_frais_port` decimal(8,2) DEFAULT 0.00,
                `montant_total` decimal(10,2) DEFAULT 0.00,
                `statut_paiement` tinyint(1) DEFAULT 0,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_participant` (`commande_id`, `user_id`),
                KEY `idx_user_id` (`user_id`),
                KEY `idx_statut_paiement` (`statut_paiement`),
                FOREIGN KEY (`commande_id`) REFERENCES `{$prefix}commandes`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Table des articles commandés
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}articles_commandes` (
                `id` varchar(32) NOT NULL,
                `participant_id` varchar(32) NOT NULL,
                `variation_id` varchar(32) NOT NULL,
                `quantite` int NOT NULL DEFAULT 1,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_article` (`participant_id`, `variation_id`),
                KEY `idx_variation_id` (`variation_id`),
                FOREIGN KEY (`participant_id`) REFERENCES `{$prefix}participants`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`variation_id`) REFERENCES `{$prefix}variations`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Table des paliers de frais
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}paliers_frais` (
                `id` varchar(32) NOT NULL,
                `commande_id` varchar(32) NOT NULL,
                `min_value` decimal(10,3) NOT NULL,
                `max_value` decimal(10,3),
                `frais` decimal(8,2) NOT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_commande_id` (`commande_id`),
                FOREIGN KEY (`commande_id`) REFERENCES `{$prefix}commandes`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
        
    } catch (PDOException $e) {
        error_log('Erreur création tables: ' . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - <?php echo APP_NAME ?? 'Groupons'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .install-container { max-width: 600px; margin: 50px auto; }
        .install-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 10px; font-weight: bold; }
        .step.active { background: #007bff; color: white; }
        .step.completed { background: #28a745; color: white; }
        .step.pending { background: #e9ecef; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <div class="install-card p-5">
                <div class="text-center mb-4">
                    <h1><i class="fas fa-shopping-cart text-primary"></i> <?php echo APP_NAME ?? 'Groupons'; ?></h1>
                    <p class="text-muted">Configuration de la base de données</p>
                </div>
                
                <!-- Indicateur d'étapes -->
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : 'pending'; ?>">1</div>
                    <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : 'pending'; ?>">2</div>
                    <div class="step <?php echo $step >= 3 ? 'active' : 'pending'; ?>">3</div>
                </div>
                
                <?php if ($step == 1): ?>
                <!-- Étape 1: Bienvenue -->
                <div class="text-center">
                    <h3>Bienvenue dans l'installation</h3>
                    <p class="text-muted mb-4">
                        Cette installation va configurer votre base de données MySQL pour <?php echo APP_NAME ?? 'Groupons'; ?>.
                        Vous aurez besoin des informations de connexion à votre base de données.
                    </p>
                    
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Informations nécessaires :</h5>
                        <ul class="text-start">
                            <li>Serveur de base de données (généralement <code>localhost</code>)</li>
                            <li>Nom de la base de données</li>
                            <li>Nom d'utilisateur MySQL</li>
                            <li>Mot de passe MySQL</li>
                        </ul>
                    </div>
                    
                    <a href="?step=2" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-right"></i> Commencer l'installation
                    </a>
                </div>
                
                <?php elseif ($step == 2): ?>
                <!-- Étape 2: Configuration -->
                <h3>Configuration de la base de données</h3>
                
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Erreurs :</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Serveur de base de données</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" 
                               value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                        <div class="form-text">Généralement <code>localhost</code> pour un serveur local</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Nom de la base de données</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" 
                               value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" required>
                        <div class="form-text">La base de données sera créée automatiquement si elle n'existe pas</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" 
                               value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>
                    
                    <div class="mb-3">
                        <label for="db_prefix" class="form-label">Préfixe des tables</label>
                        <input type="text" class="form-control" id="db_prefix" name="db_prefix" 
                               value="<?php echo htmlspecialchars($_POST['db_prefix'] ?? 'groupon_'); ?>">
                        <div class="form-text">Préfixe ajouté aux noms de tables (ex: <code>groupon_</code>)</div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="?step=1" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-database"></i> Tester la connexion et installer
                        </button>
                    </div>
                </form>
                
                <?php elseif ($step == 3 && $success): ?>
                <!-- Étape 3: Succès -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="text-success">Installation terminée !</h3>
                    <p class="text-muted mb-4">
                        La base de données MySQL a été configurée avec succès. 
                        Toutes les tables ont été créées et le système est prêt à être utilisé.
                    </p>
                    
                    <div class="alert alert-success">
                        <h5><i class="fas fa-info-circle"></i> Prochaines étapes :</h5>
                        <ul class="text-start">
                            <li>Supprimez le fichier <code>install.php</code> pour des raisons de sécurité</li>
                            <li>Créez votre premier compte administrateur</li>
                            <li>Commencez à utiliser l'application</li>
                        </ul>
                    </div>
                    
                    <a href="register.php" class="btn btn-success btn-lg">
                        <i class="fas fa-user-plus"></i> Créer un compte administrateur
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
