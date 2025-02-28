<?php
require_once 'includes/header.php';

// Vérifier si l'ID de commande est fourni
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de commande manquant.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$commande_id = $_GET['id'];
$commande_data = getCommandeData($commande_id);

// Vérifier si la commande existe
if (!$commande_data) {
    $_SESSION['flash_message'] = 'Commande introuvable.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: index.php');
    exit;
}

$page_title = "Commande: " . $commande_data['titre'];
$user_id = getCurrentUserId();
$is_admin = isCommandeAdmin($commande_id, $user_id);
$is_participant = isCommandeParticipant($commande_id, $user_id);
$is_closed = isCommandeClosed($commande_data);

$admin_data = getUserData($commande_data['admin_id']);
$admin_name = $admin_data ? $admin_data['prenom'] . ' ' . $admin_data['nom'] : 'Administrateur';

$date_limite = new DateTime($commande_data['date_limite']);
$date_recup = new DateTime($commande_data['date_recuperation']);

$participant_data = null;
if ($is_participant) {
    $participant_data = getParticipantCommande($commande_data, $user_id);
}

$errors = [];
$success_message = null;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si l'utilisateur est connecté
    if (!isLoggedIn()) {
        $_SESSION['flash_message'] = __('must_login_to_join');
        $_SESSION['flash_type'] = 'warning';
        header('Location: login.php?redirect=' . urlencode('commande.php?id=' . $commande_id));
        exit;
    }
    
    // Vérifier si la commande est fermée
    if ($is_closed) {
        $errors[] = __('order_closed');
    } else {
        // Action : Rejoindre la commande
        if (isset($_POST['action']) && $_POST['action'] === 'join') {
            if (addParticipant($commande_id, $user_id)) {
                $_SESSION['flash_message'] = __('joined_order');
                $_SESSION['flash_type'] = 'success';
                header('Location: commande.php?id=' . $commande_id);
                exit;
            } else {
                $errors[] = __('error_occurred');
            }
        }
        
        // Action : Modifier la commande
        if (isset($_POST['action']) && $_POST['action'] === 'update_order') {
            $success = true;
            
            // Parcourir tous les produits et variations
            foreach ($commande_data['produits'] as $produit) {
                foreach ($produit['variations'] as $variation) {
                    $variation_id = $variation['id'];
                    $quantite = isset($_POST['quantity'][$variation_id]) ? intval($_POST['quantity'][$variation_id]) : 0;
                    
                    // Mettre à jour la quantité si elle a changé
                    if ($quantite > 0) {
                        $result = addArticle($commande_id, $user_id, $variation_id, $quantite);
                        if (!$result) {
                            $success = false;
                        }
                    }
                }
            }
            
            if ($success) {
                $success_message = __('order_updated');
                // Recharger les données de la commande et du participant
                $commande_data = getCommandeData($commande_id);
                $participant_data = getParticipantCommande($commande_data, $user_id);
            } else {
                $errors[] = __('error_occurred');
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($commande_data['titre']); ?></h1>
    <div>
        <?php if ($is_admin): ?>
        <a href="/admin_commande.php?id=<?php echo $commande_id; ?>" class="btn btn-primary">
            Mode administration
        </a>
        <?php endif; ?>
        <a href="/dashboard.php" class="btn btn-outline-secondary ms-2">
            Tableau de bord
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

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Informations</h5>
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
                    <small><?php echo htmlspecialchars($commande_data['adresse_recuperation']); ?></small>
                </div>
                <div class="mb-3">
                    <strong>Type de commande:</strong> 
                    <?php 
                    switch ($commande_data['type_commande']) {
                        case 'poids':
                            echo 'Basée sur le poids total';
                            break;
                        case 'nombre':
                            echo 'Basée sur le nombre de produits';
                            break;
                        case 'montant':
                            echo 'Basée sur le coût total';
                            break;
                    }
                    ?>
                </div>
                
                <?php if (!isLoggedIn()): ?>
                <hr>
                <div class="alert alert-info">
                    <p>Vous devez être connecté pour participer à cette commande.</p>
                    <div class="d-grid gap-2">
                        <a href="/login.php?redirect=<?php echo urlencode('commande.php?id=' . $commande_id); ?>" class="btn btn-primary">Se connecter</a>
                        <a href="/register.php" class="btn btn-outline-secondary">S'inscrire</a>
                    </div>
                </div>
                <?php elseif (!$is_participant && !$is_admin): ?>
                <hr>
                <form method="post" action="">
                    <input type="hidden" name="action" value="join">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" <?php echo $is_closed ? 'disabled' : ''; ?>>
                            Participer à cette commande
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <?php if ($is_participant && $participant_data): ?>
                <hr>
                <div class="mb-3">
                    <strong>Ma commande:</strong> <?php echo number_format($participant_data['montant_produits'], 2, ',', ' '); ?> €
                </div>
                <div class="mb-3">
                    <strong>Frais de port:</strong> <?php echo number_format($participant_data['part_frais_port'], 2, ',', ' '); ?> €
                </div>
                <div class="mb-3">
                    <strong>Total à payer:</strong> 
                    <span class="fw-bold"><?php echo number_format($participant_data['montant_total'], 2, ',', ' '); ?> €</span>
                </div>
                <div class="mb-3">
                    <strong>Statut paiement:</strong> 
                    <span class="<?php echo $participant_data['statut_paiement'] ? 'payment-status-paid' : 'payment-status-unpaid'; ?>">
                        <?php echo $participant_data['statut_paiement'] ? 'Payé' : 'Non payé'; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Produits disponibles</h5>
            </div>
            <div class="card-body">
                <?php if (empty($commande_data['produits'])): ?>
                <div class="alert alert-info">
                    Aucun produit n'a encore été ajouté à cette commande.
                </div>
                <?php else: ?>
                
                <?php if ($is_participant || $is_admin): ?>
                <form method="post" action="" id="order-form">
                    <input type="hidden" name="action" value="update_order">
                <?php endif; ?>
                    
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Poids</th>
                                <th>Prix</th>
                                <?php if (($is_participant || $is_admin) && !$is_closed): ?>
                                <th>Quantité</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commande_data['produits'] as $produit): ?>
                            <tr class="table-light">
                                <td colspan="<?php echo ($is_participant || $is_admin) && !$is_closed ? '4' : '3'; ?>" class="fw-bold">
                                    <?php echo htmlspecialchars($produit['nom']); ?>
                                    <?php if (!empty($produit['url'])): ?>
                                    <a href="<?php echo htmlspecialchars($produit['url']); ?>" target="_blank" class="product-link ms-2" data-bs-toggle="tooltip" title="Plus d'informations">
                                        <small><i class="fas fa-info-circle"></i> Info</small>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php foreach ($produit['variations'] as $variation): ?>
                            <?php
                            // Récupérer la quantité du participant pour cette variation
                            $quantite = 0;
                            if ($participant_data) {
                                foreach ($participant_data['commandes'] as $article) {
                                    if ($article['variation_id'] === $variation['id']) {
                                        $quantite = $article['quantite'];
                                        break;
                                    }
                                }
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($variation['nom']); ?></td>
                                <td><?php echo number_format($variation['poids'], 2, ',', ' '); ?> kg</td>
                                <td><?php echo number_format($variation['prix'], 2, ',', ' '); ?> €</td>
                                <?php if (($is_participant || $is_admin) && !$is_closed): ?>
                                <td>
                                    <div class="input-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="updateQuantity('<?php echo $variation['id']; ?>', -1)">-</button>
                                        <input type="number" id="quantity-<?php echo $variation['id']; ?>" 
                                               name="quantity[<?php echo $variation['id']; ?>]" 
                                               class="form-control form-control-sm text-center" 
                                               value="<?php echo $quantite; ?>" min="0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="updateQuantity('<?php echo $variation['id']; ?>', 1)">+</button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (($is_participant || $is_admin) && !$is_closed): ?>
                <div class="d-grid gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">Mettre à jour ma commande</button>
                </div>
                </form>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($is_closed && count($commande_data['participants']) > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Récapitulatif des commandes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Variation</th>
                                <th>Quantité</th>
                                <th>Poids</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Récapitulation par produit/variation
                            $recap = [];
                            foreach ($commande_data['participants'] as $participant) {
                                foreach ($participant['commandes'] as $article) {
                                    if (!isset($recap[$article['variation_id']])) {
                                        $recap[$article['variation_id']] = [
                                            'quantite' => 0,
                                            'info' => null
                                        ];
                                    }
                                    $recap[$article['variation_id']]['quantite'] += $article['quantite'];
                                    
                                    if (!$recap[$article['variation_id']]['info']) {
                                        $recap[$article['variation_id']]['info'] = getVariationInfo($commande_data, $article['variation_id']);
                                    }
                                }
                            }
                            
                            // Trier par produit/variation
                            uasort($recap, function($a, $b) {
                                if (!$a['info'] || !$b['info']) return 0;
                                $cmp = strcmp($a['info']['produit_nom'], $b['info']['produit_nom']);
                                if ($cmp === 0) {
                                    return strcmp($a['info']['variation_nom'], $b['info']['variation_nom']);
                                }
                                return $cmp;
                            });
                            
                            // Totaux
                            $total_quantite = 0;
                            $total_poids = 0;
                            $total_montant = 0;
                            
                            foreach ($recap as $variation_id => $data):
                                if (!$data['info']) continue;
                                
                                $poids_total = $data['info']['poids'] * $data['quantite'];
                                $montant_total = $data['info']['prix'] * $data['quantite'];
                                
                                $total_quantite += $data['quantite'];
                                $total_poids += $poids_total;
                                $total_montant += $montant_total;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['info']['produit_nom']); ?></td>
                                <td><?php echo htmlspecialchars($data['info']['variation_nom']); ?></td>
                                <td><?php echo $data['quantite']; ?></td>
                                <td><?php echo number_format($poids_total, 2, ',', ' '); ?> kg</td>
                                <td><?php echo number_format($montant_total, 2, ',', ' '); ?> €</td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <tr class="table-light">
                                <td colspan="2" class="fw-bold">TOTAL</td>
                                <td class="fw-bold"><?php echo $total_quantite; ?></td>
                                <td class="fw-bold"><?php echo number_format($total_poids, 2, ',', ' '); ?> kg</td>
                                <td class="fw-bold"><?php echo number_format($total_montant, 2, ',', ' '); ?> €</td>
                            </tr>
                            <tr class="table-light">
                                <td colspan="4" class="fw-bold">Frais de port</td>
                                <td class="fw-bold"><?php echo number_format($commande_data['frais_port'], 2, ',', ' '); ?> €</td>
                            </tr>
                            <tr class="table-primary">
                                <td colspan="4" class="fw-bold">TOTAL GÉNÉRAL</td>
                                <td class="fw-bold"><?php echo number_format($total_montant + $commande_data['frais_port'], 2, ',', ' '); ?> €</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>