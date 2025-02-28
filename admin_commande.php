<?php
require_once 'includes/header.php';

// Vérifier si l'ID de commande est fourni
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = 'ID de commande manquant.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

$commande_id = $_GET['id'];
$commande_data = getCommandeData($commande_id);

// Vérifier si la commande existe
if (!$commande_data) {
    $_SESSION['flash_message'] = 'Commande introuvable.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

// Vérifier si l'utilisateur est l'admin de la commande
requireCommandeAdmin($commande_id);

$user_id = getCurrentUserId();
$page_title = "Gestion de commande: " . $commande_data['titre'];

// Récupérer les données
$is_closed = isCommandeClosed($commande_data);
$nb_participants = count($commande_data['participants']);
$date_limite = new DateTime($commande_data['date_limite']);
$date_recup = new DateTime($commande_data['date_recuperation']);

// Traitement des actions
$errors = [];
$success_message = null;

// Action : Ajouter un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $nom_produit = trim($_POST['nom_produit'] ?? '');
    $url_produit = trim($_POST['url_produit'] ?? '');
    $variations = [];
    
    if (empty($nom_produit)) {
        $errors[] = 'Le nom du produit est requis.';
    }
    
    // Valider l'URL si fournie
    if (!empty($url_produit) && !filter_var($url_produit, FILTER_VALIDATE_URL)) {
        $errors[] = 'L\'URL du produit n\'est pas valide.';
    }
    
    if (isset($_POST['variations'])) {
        foreach ($_POST['variations'] as $variation) {
            if (!empty($variation['nom']) && isset($variation['poids'], $variation['prix']) 
                && is_numeric($variation['poids']) && is_numeric($variation['prix'])
                && $variation['poids'] > 0 && $variation['prix'] > 0) {
                
                $variations[] = [
                    'id' => generateUniqueId(),
                    'nom' => trim($variation['nom']),
                    'poids' => (float) $variation['poids'],
                    'prix' => (float) $variation['prix']
                ];
            }
        }
    }
    
    if (empty($variations)) {
        $errors[] = 'Au moins une variation valide est requise.';
    }
    
    if (empty($errors)) {
        if (addProduit($commande_id, $nom_produit, $variations, $url_produit)) {
            $success_message = 'Produit ajouté avec succès.';
            $commande_data = getCommandeData($commande_id); // Recharger les données
        } else {
            $errors[] = 'Erreur lors de l\'ajout du produit.';
        }
    }
}

// Action : Envoyer un rappel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_reminder') {
    if (envoyerRappel($commande_id)) {
        $success_message = 'Rappel envoyé avec succès.';
    } else {
        $errors[] = 'Erreur lors de l\'envoi du rappel.';
    }
}

// Action : Envoyer les infos de récupération
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_pickup') {
    if (envoyerInfosRecuperation($commande_id)) {
        $success_message = 'Informations de récupération envoyées avec succès.';
    } else {
        $errors[] = 'Erreur lors de l\'envoi des informations.';
    }
}

// Action : Mettre à jour le statut de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment') {
    $participant_id = $_POST['participant_id'] ?? '';
    $statut = isset($_POST['statut']) && $_POST['statut'] === '1';
    
    if (empty($participant_id)) {
        $errors[] = 'ID du participant manquant.';
    } else {
        if (updatePaiementStatus($commande_id, $participant_id, $statut)) {
            $success_message = 'Statut de paiement mis à jour.';
            $commande_data = getCommandeData($commande_id); // Recharger les données
        } else {
            $errors[] = 'Erreur lors de la mise à jour du statut de paiement.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($commande_data['titre']); ?></h1>
    <div>
        <a href="/commande.php?id=<?php echo $commande_id; ?>" class="btn btn-outline-secondary me-2">
            Voir la commande
        </a>
        <a href="/dashboard.php" class="btn btn-outline-primary">
            Retour au tableau de bord
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
            <div class="card-header">
                <h5 class="mb-0">Informations</h5>
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
                <div class="mb-3">
                    <strong>Lien de partage:</strong>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo APP_URL . '/commande.php?id=' . $commande_id; ?>" readonly id="share-link">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink()">Copier</button>
                    </div>
                </div>
                <hr>
                <form method="post" action="" class="mb-2">
                    <input type="hidden" name="action" value="send_reminder">
                    <button type="submit" class="btn btn-warning w-100" <?php echo $is_closed ? 'disabled' : ''; ?>>
                        Envoyer un rappel
                    </button>
                </form>
                <form method="post" action="">
                    <input type="hidden" name="action" value="send_pickup">
                    <button type="submit" class="btn btn-info w-100">
                        Envoyer les infos de récupération
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statistiques</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h3><?php echo $nb_participants; ?></h3>
                        <p>Participants</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3><?php echo count($commande_data['produits']); ?></h3>
                        <p>Produits</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3><?php echo number_format($commande_data['montant_total'], 2, ',', ' '); ?> €</h3>
                        <p>Montant total</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3><?php echo number_format($commande_data['frais_port'], 2, ',', ' '); ?> €</h3>
                        <p>Frais de port</p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Poids total</h5>
                        <div class="progress mb-3" style="height: 25px;">
                            <?php
                            $paliers = $commande_data['paliers_frais'];
                            $current_palier = null;
                            $next_palier = null;
                            
                            foreach ($paliers as $palier) {
                                if ($commande_data['poids_total'] >= $palier['min'] && $commande_data['poids_total'] < $palier['max']) {
                                    $current_palier = $palier;
                                    break;
                                }
                            }
                            
                            // Trouver le prochain palier
                            $found_current = false;
                            foreach ($paliers as $palier) {
                                if ($found_current) {
                                    $next_palier = $palier;
                                    break;
                                }
                                if ($palier === $current_palier) {
                                    $found_current = true;
                                }
                            }
                            
                            // Calculer le pourcentage de progression
                            $percentage = 0;
                            if ($current_palier) {
                                $min = $current_palier['min'];
                                $max = $current_palier['max'];
                                $percentage = (($commande_data['poids_total'] - $min) / ($max - $min)) * 100;
                            }
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                 aria-valuenow="<?php echo $commande_data['poids_total']; ?>" aria-valuemin="0" 
                                 aria-valuemax="<?php echo $current_palier ? $current_palier['max'] : 0; ?>">
                                <?php echo number_format($commande_data['poids_total'], 2, ',', ' '); ?> kg
                            </div>
                        </div>
                        <small>
                            <?php 
                            if ($current_palier) {
                                echo 'Palier actuel: ' . $current_palier['min'] . ' - ' . $current_palier['max'] . ' kg';
                                
                                if ($next_palier) {
                                    $reste = $next_palier['min'] - $commande_data['poids_total'];
                                    if ($reste > 0) {
                                        echo ' (encore ' . number_format($reste, 2, ',', ' ') . ' kg pour le prochain palier)';
                                    }
                                }
                            }
                            ?>
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Paiements</h5>
                        <?php
                        $total_paid = 0;
                        $total_amount = 0;
                        
                        foreach ($commande_data['participants'] as $participant) {
                            $total_amount += $participant['montant_total'];
                            if ($participant['statut_paiement']) {
                                $total_paid += $participant['montant_total'];
                            }
                        }
                        
                        $percentage_paid = $total_amount > 0 ? ($total_paid / $total_amount) * 100 : 0;
                        ?>
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $percentage_paid; ?>%" 
                                 aria-valuenow="<?php echo $total_paid; ?>" aria-valuemin="0" 
                                 aria-valuemax="<?php echo $total_amount; ?>">
                                <?php echo number_format($percentage_paid, 0) . '%'; ?>
                            </div>
                        </div>
                        <small>
                            <?php echo number_format($total_paid, 2, ',', ' '); ?> € reçus sur <?php echo number_format($total_amount, 2, ',', ' '); ?> €
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Produits</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal" <?php echo $is_closed ? 'disabled' : ''; ?>>
                    Ajouter un produit
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($commande_data['produits'])): ?>
                <div class="alert alert-info">
                    Aucun produit n'a été ajouté à cette commande.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($commande_data['produits'] as $produit): ?>
                    <div class="col-md-6 mb-4">
                        <div class="product-item">
                            <h5>
                                <?php echo htmlspecialchars($produit['nom']); ?>
                                <?php if (!empty($produit['url'])): ?>
                                <a href="<?php echo htmlspecialchars($produit['url']); ?>" target="_blank" class="product-link ms-2" data-bs-toggle="tooltip" title="Plus d'informations">
                                    <small><i class="fas fa-info-circle"></i> Info</small>
                                </a>
                                <?php endif; ?>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Variation</th>
                                            <th>Poids</th>
                                            <th>Prix</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($produit['variations'] as $variation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($variation['nom']); ?></td>
                                            <td><?php echo number_format($variation['poids'], 2, ',', ' '); ?> kg</td>
                                            <td><?php echo number_format($variation['prix'], 2, ',', ' '); ?> €</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Participants</h5>
            </div>
            <div class="card-body">
                <?php if (empty($commande_data['participants'])): ?>
                <div class="alert alert-info">
                    Aucun participant n'a rejoint cette commande.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Participant</th>
                                <th>Produits</th>
                                <th>Montant produits</th>
                                <th>Frais de port</th>
                                <th>Total</th>
                                <th>Statut paiement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commande_data['participants'] as $participant): ?>
                            <?php
                            $user_data = getUserData($participant['user_id']);
                            $user_name = $user_data ? $user_data['prenom'] . ' ' . $user_data['nom'] : 'Utilisateur #' . $participant['user_id'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user_name); ?></td>
                                <td>
                                    <?php if (empty($participant['commandes'])): ?>
                                    <span class="text-muted">Aucun produit</span>
                                    <?php else: ?>
                                    <ul class="mb-0">
                                        <?php foreach ($participant['commandes'] as $article): ?>
                                        <?php
                                        $variation_info = getVariationInfo($commande_data, $article['variation_id']);
                                        if ($variation_info):
                                        ?>
                                        <li>
                                            <?php echo htmlspecialchars($variation_info['produit_nom'] . ' - ' . $variation_info['variation_nom']); ?> 
                                            x <?php echo $article['quantite']; ?>
                                        </li>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($participant['montant_produits'], 2, ',', ' '); ?> €</td>
                                <td><?php echo number_format($participant['part_frais_port'], 2, ',', ' '); ?> €</td>
                                <td><?php echo number_format($participant['montant_total'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <span class="<?php echo $participant['statut_paiement'] ? 'payment-status-paid' : 'payment-status-unpaid'; ?>">
                                        <?php echo $participant['statut_paiement'] ? 'Payé' : 'Non payé'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="" class="d-inline">
                                        <input type="hidden" name="action" value="update_payment">
                                        <input type="hidden" name="participant_id" value="<?php echo $participant['user_id']; ?>">
                                        <input type="hidden" name="statut" value="<?php echo $participant['statut_paiement'] ? '0' : '1'; ?>">
                                        <button type="submit" class="btn btn-sm btn-<?php echo $participant['statut_paiement'] ? 'warning' : 'success'; ?>">
                                            <?php echo $participant['statut_paiement'] ? 'Marquer comme non payé' : 'Marquer comme payé'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un produit -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="add_product">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Ajouter un produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nom_produit" class="form-label required-field">Nom du produit</label>
                        <input type="text" class="form-control" id="nom_produit" name="nom_produit" required>
                    </div>

                    <div class="mb-3">
                        <label for="url_produit" class="form-label">URL d'information (facultatif)</label>
                        <input type="url" class="form-control" id="url_produit" name="url_produit" placeholder="https://...">
                        <small class="form-text text-muted">Lien vers une page d'information sur le produit</small>
                    </div>
                    
                    <h5 class="mt-4 mb-3">Variations</h5>
                    
                    <div id="variations-container">
                        <div class="variation-row row mb-3">
                            <div class="col-md-4">
                                <input type="text" name="variations[0][nom]" class="form-control" placeholder="Nom de la variation" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="variations[0][poids]" class="form-control" placeholder="Poids (kg)" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="variations[0][prix]" class="form-control" placeholder="Prix (€)" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100" onclick="removeVariation(this)" disabled>Supprimer</button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary mt-2" onclick="addVariationField()">
                        Ajouter une variation
                    </button>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyShareLink() {
    const shareLink = document.getElementById('share-link');
    shareLink.select();
    document.execCommand('copy');
    
    alert('Lien copié dans le presse-papier !');
}
</script>

<?php require_once 'includes/footer.php'; ?>