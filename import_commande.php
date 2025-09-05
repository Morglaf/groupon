<?php
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = __('must_login_to_import');
    $_SESSION['flash_type'] = 'warning';
    header('Location: login.php?redirect=' . urlencode('import_commande.php'));
    exit;
}

$user_id = getCurrentUserId();
$page_title = __('import_order');

$errors = [];
$success_message = null;

// Traitement de l'importation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = __('file_upload_error');
    } else {
        $file_tmp = $_FILES['json_file']['tmp_name'];
        $file_content = file_get_contents($file_tmp);
        
        if (!$file_content) {
            $errors[] = __('file_read_error');
        } else {
            // Importer la commande
            $commande_id = importCommandeJSON($file_content, $user_id);
            
            if ($commande_id) {
                $_SESSION['flash_message'] = __('order_imported');
                $_SESSION['flash_type'] = 'success';
                header('Location: admin_commande.php?id=' . $commande_id);
                exit;
            } else {
                $errors[] = __('import_error_check_format');
            }
        }
    }
}

// Traitement de l'importation depuis le texte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_text') {
    $json_content = trim($_POST['json_content'] ?? '');
    
    if (empty($json_content)) {
        $errors[] = __('json_content_empty');
    } else {
        // Importer la commande
        $commande_id = importCommandeJSON($json_content, $user_id);
        
        if ($commande_id) {
            $_SESSION['flash_message'] = __('order_imported');
            $_SESSION['flash_type'] = 'success';
            header('Location: admin_commande.php?id=' . $commande_id);
            exit;
        } else {
            $errors[] = __('import_error_check_format');
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo __('import_order'); ?></h1>
    <div>
        <a href="/dashboard.php" class="btn btn-outline-primary">
            <?php echo __('back_to_dashboard'); ?>
        </a>
    </div>
</div>

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

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo __('import_from_file'); ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import">
                    
                    <div class="mb-3">
                        <label for="json_file" class="form-label required-field"><?php echo __('json_file'); ?></label>
                        <input type="file" class="form-control" id="json_file" name="json_file" accept=".json" required>
                        <small class="form-text text-muted"><?php echo __('select_json_file'); ?></small>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?php echo __('import'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo __('import_from_text'); ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="action" value="import_text">
                    
                    <div class="mb-3">
                        <label for="json_content" class="form-label required-field"><?php echo __('json_content'); ?></label>
                        <textarea class="form-control" id="json_content" name="json_content" rows="10" required></textarea>
                        <small class="form-text text-muted"><?php echo __('paste_json_content'); ?></small>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><?php echo __('import'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><?php echo __('expected_json_format'); ?></h5>
    </div>
    <div class="card-body">
        <p><?php echo __('json_format_description'); ?></p>
        <pre class="bg-light p-3 rounded">
{
  "titre": "Nom de la commande",
  "type_commande": "poids", // ou "nombre" ou "montant"
  "date_limite": "2023-12-31T23:59:59",
  "date_recuperation": "2024-01-05T18:00:00",
  "adresse_recuperation": "123 Rue Example, 75000 Paris",
  "description": "Description de la commande (optionnel)",
  "public": false, // ou true pour rendre la commande publique
  "produits": [
    {
      "nom": "Nom du produit",
      "url": "https://example.com/produit", // optionnel
      "variations": [
        {
          "nom": "Variation 1",
          "poids": 1.5,
          "prix": 12.99
        },
        {
          "nom": "Variation 2",
          "poids": 2.0,
          "prix": 15.99
        }
      ]
    }
  ],
  "paliers_frais": [ // optionnel, utilise les paliers par défaut si non spécifié
    {
      "min": 0,
      "max": 10,
      "frais": 15
    },
    {
      "min": 10,
      "max": 20,
      "frais": 10
    }
  ]
}
</pre>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 