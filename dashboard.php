<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
$page_title = __('dashboard');

// Rediriger si non connecté
requireLogin();

$user_id = getCurrentUserId();
$user_data = getCurrentUser();

// Traitement de la suppression de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_commande') {
    $commande_id = $_POST['commande_id'] ?? '';
    
    if (!empty($commande_id)) {
        if (deleteCommande($commande_id, $user_id)) {
            $_SESSION['flash_message'] = __('order_deleted_successfully');
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = __('error_deleting_order');
            $_SESSION['flash_type'] = 'danger';
        }
        
        // Rediriger pour éviter la resoumission du formulaire
        header('Location: dashboard.php');
        exit;
    }
}

// Récupérer les commandes admin
$commandes_admin_raw = getUserCommandesAdmin($user_id);
$commandes_admin = [];
foreach ($commandes_admin_raw as $commande_raw) {
    $commande_data = getCommandeData($commande_raw['id']);
    if ($commande_data) {
        $commandes_admin[] = $commande_data;
    }
}

// Récupérer les commandes participant
$commandes_participant_raw = getUserCommandesParticipant($user_id);
$commandes_participant = [];
foreach ($commandes_participant_raw as $commande_raw) {
    $commande_data = getCommandeData($commande_raw['id']);
    if ($commande_data) {
        $commandes_participant[] = $commande_data;
    }
}

// Récupérer les commandes publiques
$commandes_publiques = getPublicCommandes();
// Filtrer pour ne pas afficher les commandes où l'utilisateur est déjà admin ou participant
$commandes_publiques = array_filter($commandes_publiques, function($commande) use ($user_id) {
    return $commande['admin_id'] !== $user_id && 
           (!isset($commande['participants'][$user_id]) || empty($commande['participants'][$user_id]));
});

// Trier les commandes par date de création (plus récente en premier)
usort($commandes_admin, function($a, $b) {
    return strtotime($b['date_creation']) - strtotime($a['date_creation']);
});

usort($commandes_participant, function($a, $b) {
    return strtotime($b['date_creation']) - strtotime($a['date_creation']);
});
?>

<h1 class="mb-4"><?php echo __('dashboard'); ?></h1>

<?php if (isset($_SESSION['flash_message'])): ?>
<div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
    <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php 
unset($_SESSION['flash_message']);
unset($_SESSION['flash_type']);
endif; ?>

<div class="row dashboard-stats">
    <div class="col-md-4 text-center">
        <h3><?php echo count($commandes_admin); ?></h3>
        <p><?php echo __('orders_created'); ?></p>
    </div>
    <div class="col-md-4 text-center">
        <h3><?php echo count($commandes_participant); ?></h3>
        <p><?php echo __('orders_joined'); ?></p>
    </div>
    <div class="col-md-4 text-center">
        <h3>0</h3>
        <p><?php echo __('orders_completed'); ?></p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h2><?php echo __('my_created_orders'); ?></h2>
        <div>
            <a href="/import_commande.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-file-import"></i> <?php echo __('import_order'); ?>
            </a>
            <a href="/create_commande.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo __('create_order'); ?>
            </a>
        </div>
    </div>
</div>

<?php if (empty($commandes_admin)): ?>
<div class="alert alert-info">
    <?php echo __('no_orders_created'); ?> <a href="/create_commande.php"><?php echo __('create_one_now'); ?></a>.
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($commandes_admin as $commande): ?>
    <?php
    $is_closed = isCommandeClosed($commande);
    $nb_participants = count($commande['participants']);
    $date_limite = new DateTime($commande['date_limite']);
    $date_recup = new DateTime($commande['date_recuperation']);
    ?>
    <div class="col-md-6 mb-4">
        <div class="card commande-card <?php echo $is_closed ? 'commande-closed' : ''; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($commande['titre']); ?></h5>
                <span class="badge bg-<?php echo $is_closed ? 'secondary' : 'success'; ?>">
                    <?php echo $is_closed ? __('closed') : __('open'); ?>
                </span>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong><?php echo __('created_on'); ?>:</strong> <?php echo formatDate($commande['date_creation']); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('deadline'); ?>:</strong> <?php echo formatDate($commande['date_limite']); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('pickup_date'); ?>:</strong> <?php echo formatDate($commande['date_recuperation']); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('participants'); ?>:</strong> <?php echo $nb_participants; ?>
                </p>
                <div class="mt-3">
                    <a href="/admin_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-cog"></i> <?php echo __('manage'); ?>
                    </a>
                    <a href="/commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-eye"></i> <?php echo __('view'); ?>
                    </a>
                    <button type="button" class="btn btn-outline-danger btn-sm" 
                            onclick="confirmDeleteCommande('<?php echo $commande['id']; ?>', '<?php echo htmlspecialchars($commande['titre']); ?>')">
                        <i class="fas fa-trash"></i> <?php echo __('delete'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row mb-4 mt-5">
    <div class="col-md-12">
        <h2><?php echo __('my_participations'); ?></h2>
    </div>
</div>

<?php if (empty($commandes_participant)): ?>
<div class="alert alert-info">
    <?php echo __('no_participations'); ?>
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($commandes_participant as $commande): ?>
    <?php
    $is_closed = isCommandeClosed($commande);
    $admin_data = getUserData($commande['admin_id']);
    $admin_name = $admin_data ? $admin_data['prenom'] . ' ' . $admin_data['nom'] : __('organizer');
    $date_limite = new DateTime($commande['date_limite']);
    $date_recup = new DateTime($commande['date_recuperation']);
    ?>
    <div class="col-md-6 mb-4">
        <div class="card commande-card <?php echo $is_closed ? 'commande-closed' : ''; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($commande['titre']); ?></h5>
                <span class="badge bg-<?php echo $is_closed ? 'secondary' : 'success'; ?>">
                    <?php echo $is_closed ? __('closed') : __('open'); ?>
                </span>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong><?php echo __('organizer'); ?>:</strong> <?php echo htmlspecialchars($admin_name); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('deadline'); ?>:</strong> <?php echo formatDate($commande['date_limite']); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('pickup_date'); ?>:</strong> <?php echo formatDate($commande['date_recuperation']); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('pickup_address'); ?>:</strong> <?php echo htmlspecialchars($commande['adresse_recuperation']); ?>
                </p>
                <div class="mt-3">
                    <a href="/commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-shopping-cart"></i> <?php echo __('my_order'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($commandes_publiques)): ?>
<div class="row mb-4 mt-5">
    <div class="col-md-12">
        <h2><?php echo __('public_orders'); ?></h2>
    </div>
</div>

<div class="row">
    <?php foreach ($commandes_publiques as $commande): ?>
    <?php
    $is_closed = isCommandeClosed($commande);
    if ($is_closed) continue; // Ne pas afficher les commandes fermées
    
    $admin_data = getUserData($commande['admin_id']);
    $admin_name = $admin_data ? $admin_data['prenom'] . ' ' . $admin_data['nom'] : __('organizer');
    $date_limite = new DateTime($commande['date_limite']);
    $date_recup = new DateTime($commande['date_recuperation']);
    ?>
    <div class="col-md-6 mb-4">
        <div class="card commande-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($commande['titre']); ?></h5>
                <span class="badge bg-success"><?php echo __('open'); ?></span>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong><?php echo __('organizer'); ?>:</strong> <?php echo htmlspecialchars($admin_name); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('deadline'); ?>:</strong> <?php echo formatDate($commande['date_limite']); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('pickup_date'); ?>:</strong> <?php echo formatDate($commande['date_recuperation']); ?>
                </p>
                <p class="mb-1">
                    <strong><?php echo __('pickup_address'); ?>:</strong> <?php echo htmlspecialchars($commande['adresse_recuperation']); ?>
                </p>
                <div class="mt-3">
                    <a href="/commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> <?php echo __('view'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Formulaire caché pour la suppression -->
<form id="delete-form" method="post" action="" style="display: none;">
    <input type="hidden" name="action" value="delete_commande">
    <input type="hidden" name="commande_id" id="delete-commande-id">
</form>

<script>
function confirmDeleteCommande(commandeId, commandeTitre) {
    if (confirm('Êtes-vous sûr de vouloir supprimer la commande "' + commandeTitre + '" ?\n\nCette action est irréversible et supprimera :\n- Tous les produits et variations\n- Tous les participants et leurs commandes\n- Tous les paliers de frais\n- La commande elle-même')) {
        document.getElementById('delete-commande-id').value = commandeId;
        document.getElementById('delete-form').submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>