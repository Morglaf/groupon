<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

$page_title = __('import_order');
$user_id = getCurrentUserId();
$success_message = '';
$error_message = '';

// Traitement de l'upload de fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['json_file'])) {
    if ($_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
        $file_content = file_get_contents($_FILES['json_file']['tmp_name']);
        
        if ($file_content) {
            $new_commande_id = importCommandeJSON($file_content, $user_id);
            
            if ($new_commande_id) {
                $success_message = __('import_success') . ' <a href="admin_commande.php?id=' . $new_commande_id . '">' . __('view_order') . '</a>';
            } else {
                $error_message = __('import_error');
            }
        } else {
            $error_message = __('import_error_read_file');
        }
    } else {
        $error_message = __('import_error_upload');
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo __('import_order'); ?></h1>
    <div>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <?php echo __('back_to_dashboard'); ?>
        </a>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-upload"></i> <?php echo __('import_json_file'); ?></h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="json_file" class="form-label"><?php echo __('select_json_file'); ?></label>
                        <input type="file" class="form-control" id="json_file" name="json_file" accept=".json" required>
                        <div class="form-text"><?php echo __('json_file_format_info'); ?></div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> <?php echo __('import_file'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle"></i> <?php echo __('import_info'); ?></h5>
            </div>
            <div class="card-body">
                <h6><?php echo __('supported_format'); ?></h6>
                <p><?php echo __('json_format_description'); ?></p>
                
                <h6><?php echo __('import_notes'); ?></h6>
                <ul>
                    <li><?php echo __('import_note_1'); ?></li>
                    <li><?php echo __('import_note_2'); ?></li>
                    <li><?php echo __('import_note_3'); ?></li>
                </ul>
                
                <div class="mt-3">
                    <a href="create_commande.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-plus"></i> <?php echo __('create_new_order'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>