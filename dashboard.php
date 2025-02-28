<?php
$page_title = "Tableau de bord";
require_once 'includes/header.php';

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

<h1 class="mb-4">Tableau de bord</h1>

<div class="row dashboard-stats">
    <div class="col-md-4 text-center">
        <h3><?php echo count($commandes_admin); ?></h3>
        <p>Commandes créées</p>
    </div>
    <div class="col-md-4 text-center">
        <h3><?php echo count($commandes_participant); ?></h3>
        <p>Commandes participées</p>
    </div>
    <div class="col-md-4 text-center">
        <h3><?php echo count($user_data['historique_commandes'] ?? []); ?></h3>
        <p>Commandes terminées</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
        <h2>Mes commandes créées</h2>
        <div>
            <a href="/import_commande.php" class="btn btn-outline-primary me-2">
                <i class="fas fa-file-import"></i> Importer une commande
            </a>
            <a href="/create_commande.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Créer une commande
            </a>
        </div>
    </div>
</div>

<?php if (empty($commandes_admin)): ?>
<div class="alert alert-info">
    Vous n'avez pas encore créé de commande. <a href="/create_commande.php">Créez-en une maintenant</a>.
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
                    <?php echo $is_closed ? 'Fermée' : 'Ouverte'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Date limite:</strong> 
                    <span class="<?php echo $is_closed ? '' : 'date-limite'; ?>">
                        <?php echo $date_limite->format('d/m/Y H:i'); ?>
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Récupération:</strong> 
                    <?php echo $date_recup->format('d/m/Y H:i'); ?>
                    <br>
                    <small><?php echo htmlspecialchars($commande['adresse_recuperation']); ?></small>
                </div>
                <div class="mb-3">
                    <strong>Participants:</strong> <?php echo $nb_participants; ?>
                </div>
                <div class="mb-3">
                    <strong>Montant total:</strong> <?php echo number_format($commande['montant_total'], 2, ',', ' '); ?> €
                </div>
                <div class="mb-3">
                    <strong>Frais de port:</strong> <?php echo number_format($commande['frais_port'], 2, ',', ' '); ?> €
                </div>
                
                <div class="d-grid gap-2">
                    <a href="/admin_commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-primary">Gérer</a>
                    <a href="/commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-outline-secondary">Voir</a>
                </div>
            </div>
            <div class="card-footer text-muted">
                Créée le <?php echo (new DateTime($commande['date_creation']))->format('d/m/Y'); ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<hr class="my-4">

<div class="row mb-4">
    <div class="col-md-12">
        <h2>Mes participations</h2>
    </div>
</div>

<?php if (empty($commandes_participant)): ?>
<div class="alert alert-info">
    Vous ne participez à aucune commande pour le moment.
</div>
<?php else: ?>
<div class="row">
    <?php foreach ($commandes_participant as $commande): ?>
    <?php
    $is_closed = isCommandeClosed($commande);
    $participant_data = getParticipantCommande($commande, $user_id);
    $date_limite = new DateTime($commande['date_limite']);
    $date_recup = new DateTime($commande['date_recuperation']);
    
    // Récupérer les infos de l'admin
    $admin_data = getUserData($commande['admin_id']);
    $admin_name = $admin_data ? $admin_data['prenom'] . ' ' . $admin_data['nom'] : 'Inconnu';
    ?>
    <div class="col-md-6 mb-4">
        <div class="card commande-card <?php echo $is_closed ? 'commande-closed' : ''; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($commande['titre']); ?></h5>
                <span class="badge bg-<?php echo $is_closed ? 'secondary' : 'success'; ?>">
                    <?php echo $is_closed ? 'Fermée' : 'Ouverte'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Organisateur:</strong> <?php echo htmlspecialchars($admin_name); ?>
                </div>
                <div class="mb-3">
                    <strong>Date limite:</strong> 
                    <span class="<?php echo $is_closed ? '' : 'date-limite'; ?>">
                        <?php echo $date_limite->format('d/m/Y H:i'); ?>
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Récupération:</strong> 
                    <?php echo $date_recup->format('d/m/Y H:i'); ?>
                    <br>
                    <small><?php echo htmlspecialchars($commande['adresse_recuperation']); ?></small>
                </div>
                
                <?php if ($participant_data): ?>
                <div class="mb-3">
                    <strong>Ma commande:</strong> <?php echo number_format($participant_data['montant_produits'], 2, ',', ' '); ?> €
                </div>
                <div class="mb-3">
                    <strong>Frais de port:</strong> <?php echo number_format($participant_data['part_frais_port'], 2, ',', ' '); ?> €
                </div>
                <div class="mb-3">
                    <strong>Total à payer:</strong> <span class="fw-bold"><?php echo number_format($participant_data['montant_total'], 2, ',', ' '); ?> €</span>
                </div>
                <div class="mb-3">
                    <strong>Statut paiement:</strong> 
                    <span class="<?php echo $participant_data['statut_paiement'] ? 'payment-status-paid' : 'payment-status-unpaid'; ?>">
                        <?php echo $participant_data['statut_paiement'] ? 'Payé' : 'Non payé'; ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="d-grid">
                    <a href="/commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-primary">Voir / Modifier</a>
                </div>
            </div>
            <div class="card-footer text-muted">
                Créée le <?php echo (new DateTime($commande['date_creation']))->format('d/m/Y'); ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Commandes publiques -->
<?php if (!empty($commandes_publiques)): ?>
<div class="row mb-4 mt-5">
    <div class="col-md-12">
        <h2>Commandes publiques</h2>
        <p class="text-muted">Ces commandes sont ouvertes à tous les utilisateurs.</p>
    </div>
</div>

<div class="row">
    <?php foreach ($commandes_publiques as $commande): ?>
    <?php
    $is_closed = isCommandeClosed($commande);
    $date_limite = new DateTime($commande['date_limite']);
    $date_recup = new DateTime($commande['date_recuperation']);
    ?>
    <div class="col-md-6 mb-4">
        <div class="card commande-card <?php echo $is_closed ? 'commande-closed' : ''; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($commande['titre']); ?></h5>
                <span class="badge bg-<?php echo $is_closed ? 'secondary' : 'success'; ?>">
                    <?php echo $is_closed ? 'Fermée' : 'Ouverte'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Date limite:</strong> 
                    <span class="<?php echo $is_closed ? '' : 'date-limite'; ?>">
                        <?php echo $date_limite->format('d/m/Y H:i'); ?>
                    </span>
                </div>
                <div class="mb-3">
                    <strong>Récupération:</strong> 
                    <?php echo $date_recup->format('d/m/Y H:i'); ?>
                    <br>
                    <small><?php echo htmlspecialchars($commande['adresse_recuperation']); ?></small>
                </div>
                <div class="mb-3">
                    <strong>Organisateur:</strong> 
                    <?php 
                    $admin_data = getUserData($commande['admin_id']);
                    echo $admin_data ? htmlspecialchars($admin_data['prenom'] . ' ' . $admin_data['nom']) : 'Administrateur';
                    ?>
                </div>
                <?php if (isset($commande['description']) && !empty($commande['description'])): ?>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <div class="mt-2 p-2 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars(substr($commande['description'], 0, 150))); ?>
                        <?php if (strlen($commande['description']) > 150): ?>...<?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="d-grid">
                    <a href="/commande.php?id=<?php echo $commande['id']; ?>" class="btn btn-primary">
                        Voir la commande
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>