<?php
require_once 'includes/header.php';
$page_title = __('dashboard');

// Rediriger si non connecté
requireLogin();

$user_id = getCurrentUserId();
$user_data = getCurrentUser();

// Récupérer les commandes admin
$commandes_admin = [];
if (!empty($user_data['commandes_admin'])) {
    foreach ($user_data['commandes_admin'] as $commande_id) {
        $commande_data = getCommandeData($commande_id);
        if ($commande_data) {
            $commandes_admin[] = $commande_data;
        }
    }
}

// Récupérer les commandes participant
$commandes_participant = [];
if (!empty($user_data['commandes_participant'])) {
    foreach ($user_data['commandes_participant'] as $commande_id) {
        $commande_data = getCommandeData($commande_id);
        if ($commande_data) {
            $commandes_participant[] = $commande_data;
        }
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
        <h3><?php echo count($user_data['historique_commandes'] ?? []); ?></h3>
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

<?php require_once 'includes/footer.php'; ?>