<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
require_once 'includes/header.php';

// Vérifier si l'utilisateur est connecté
requireLogin();

// Vérifier si l'ID de commande est fourni
if (!isset($_GET['id'])) {
    $_SESSION['flash_message'] = __('missing_order_id');
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

$commande_id = $_GET['id'];
$commande_data = getCommandeData($commande_id);

// Vérifier si la commande existe
if (!$commande_data) {
    $_SESSION['flash_message'] = __('order_not_found');
    $_SESSION['flash_type'] = 'danger';
    header('Location: dashboard.php');
    exit;
}

// Vérifier si l'utilisateur est l'admin de la commande
requireCommandeAdmin($commande_id);

$user_id = getCurrentUserId();
$page_title = __('manage_order') . ": " . $commande_data['titre'];

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

// Action : Mettre à jour les dates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_dates') {
    $date_limite = trim($_POST['date_limite'] ?? '');
    $date_recuperation = trim($_POST['date_recuperation'] ?? '');
    
    if (empty($date_limite) || empty($date_recuperation)) {
        $errors[] = 'Les dates sont requises.';
    } else {
        if (updateCommandeDates($commande_id, $date_limite, $date_recuperation)) {
            $success_message = 'Dates mises à jour avec succès.';
            $commande_data = getCommandeData($commande_id); // Recharger les données
            $date_limite = new DateTime($commande_data['date_limite']);
            $date_recup = new DateTime($commande_data['date_recuperation']);
        } else {
            $errors[] = 'Erreur lors de la mise à jour des dates.';
        }
    }
}

// Action : Supprimer un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $produit_id = $_POST['produit_id'] ?? '';
    
    if (empty($produit_id)) {
        $errors[] = 'ID du produit manquant.';
    } else {
        if (deleteProduit($commande_id, $produit_id)) {
            $success_message = 'Produit supprimé avec succès.';
            $commande_data = getCommandeData($commande_id); // Recharger les données
        } else {
            $errors[] = 'Erreur lors de la suppression du produit.';
        }
    }
}

// Action : Supprimer une variation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_variation') {
    $produit_id = $_POST['produit_id'] ?? '';
    $variation_id = $_POST['variation_id'] ?? '';
    
    if (empty($produit_id) || empty($variation_id)) {
        $errors[] = 'ID du produit ou de la variation manquant.';
    } else {
        if (deleteVariation($commande_id, $produit_id, $variation_id)) {
            $success_message = 'Variation supprimée avec succès.';
            $commande_data = getCommandeData($commande_id); // Recharger les données
        } else {
            $errors[] = 'Erreur lors de la suppression de la variation.';
        }
    }
}

// Action : Dupliquer la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'duplicate_order') {
    $new_commande_id = duplicateCommande($commande_id, $user_id);
    if ($new_commande_id) {
        $_SESSION['flash_message'] = 'Commande dupliquée avec succès.';
        $_SESSION['flash_type'] = 'success';
        header('Location: admin_commande.php?id=' . $new_commande_id);
        exit;
    } else {
        $errors[] = 'Erreur lors de la duplication de la commande.';
    }
}

// Action : Exporter la commande en JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_json') {
    // Nettoyer le buffer de sortie
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    $json_data = exportCommandeJSON($commande_id);
    if ($json_data) {
        $filename = 'commande_' . $commande_id . '_' . date('Ymd_His') . '.json';
        
        // Headers pour le JSON
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json_data));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        // Envoyer le fichier JSON
        echo $json_data;
        exit;
    } else {
        // Erreur - rediriger avec message
        $_SESSION['flash_message'] = 'Erreur lors de la génération du JSON.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: admin_commande.php?id=' . $commande_id);
        exit;
    }
}

// Action : Générer un PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_pdf') {
    // Nettoyer complètement tous les buffers de sortie
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $pdf_path = generateCommandePDF($commande_id);
    if ($pdf_path && file_exists($pdf_path)) {
        $filename = basename($pdf_path);
        
        // Headers pour le PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Envoyer le fichier PDF
        readfile($pdf_path);
        
        // Supprimer le fichier temporaire
        unlink($pdf_path);
        
        exit;
    } else {
        // Erreur - rediriger avec message
        $_SESSION['flash_message'] = 'Erreur lors de la génération du PDF. Vérifiez que TCPDF est installé.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: admin_commande.php?id=' . $commande_id);
        exit;
    }
}

// Action : Générer un PDF de checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_checklist_pdf') {
    // Nettoyer complètement tous les buffers de sortie
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $pdf_path = generateCommandeChecklistPDF($commande_id);
    if ($pdf_path && file_exists($pdf_path)) {
        $filename = basename($pdf_path);
        
        // Headers pour le PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Envoyer le fichier PDF
        readfile($pdf_path);
        
        // Supprimer le fichier temporaire
        unlink($pdf_path);
        
        exit;
    } else {
        // Erreur - rediriger avec message
        $_SESSION['flash_message'] = 'Erreur lors de la génération du PDF de checklist. Vérifiez que TCPDF est installé.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: admin_commande.php?id=' . $commande_id);
        exit;
    }
}

// Action : Mettre à jour la description
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_description') {
    $description = trim($_POST['description'] ?? '');
    
    if (updateCommandeDescription($commande_id, $description)) {
        $success_message = 'Description mise à jour avec succès.';
        $commande_data = getCommandeData($commande_id); // Recharger les données
    } else {
        $errors[] = 'Erreur lors de la mise à jour de la description.';
    }
}

// Action : Mettre à jour le statut public
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_public_status') {
    $is_public = isset($_POST['is_public']) && $_POST['is_public'] === '1';
    
    if (updateCommandePublicStatus($commande_id, $is_public)) {
        $success_message = 'Statut public mis à jour avec succès.';
        $commande_data = getCommandeData($commande_id); // Recharger les données
    } else {
        $errors[] = 'Erreur lors de la mise à jour du statut public.';
    }
}

// Inclure le header après toute la logique de traitement
require_once 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($commande_data['titre']); ?></h1>
    <div>
        <a href="/commande.php?id=<?php echo $commande_id; ?>" class="btn btn-outline-secondary me-2">
            <?php echo __('view_order'); ?>
        </a>
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

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?php echo __('information'); ?></h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong><?php echo __('deadline'); ?>:</strong> 
                    <span class="<?php echo $is_closed ? '' : 'date-limite'; ?>">
                        <?php echo $date_limite->format('d/m/Y H:i'); ?>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#updateDatesModal">
                        <i class="fas fa-edit"></i> <?php echo __('modify'); ?>
                    </button>
                </div>
                <div class="mb-3">
                    <strong><?php echo __('pickup'); ?>:</strong> 
                    <?php echo $date_recup->format('d/m/Y H:i'); ?>
                    <br>
                    <small><?php echo htmlspecialchars($commande_data['adresse_recuperation']); ?></small>
                </div>
                <div class="mb-3">
                    <strong><?php echo __('order_type'); ?>:</strong> 
                    <?php 
                    switch ($commande_data['type_commande']) {
                        case 'poids':
                            echo __('based_on_weight');
                            break;
                        case 'nombre':
                            echo __('based_on_quantity');
                            break;
                        case 'montant':
                            echo __('based_on_amount');
                            break;
                        case 'sans_frais':
                            echo __('based_on_no_shipping');
                            break;
                    }
                    ?>
                </div>
                <div class="mb-3">
                    <strong><?php echo __('share_link'); ?>:</strong>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo APP_URL . '/commande.php?id=' . $commande_id; ?>" readonly id="share-link">
                        <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink()"><?php echo __('copy'); ?></button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong><?php echo __('description'); ?>:</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#updateDescriptionModal">
                        <i class="fas fa-edit"></i> <?php echo isset($commande_data['description']) && !empty($commande_data['description']) ? __('modify') : __('add'); ?>
                    </button>
                    <?php if (isset($commande_data['description']) && !empty($commande_data['description'])): ?>
                    <div class="mt-2 p-2 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars($commande_data['description'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($commande_data['type_commande'] !== 'sans_frais'): ?>
                <div class="mb-3">
                    <strong><?php echo __('shipping_tiers'); ?>:</strong>
                    <?php 
                    $paliers = getCommandePaliers($commande_id);
                    if (!empty($paliers)):
                    ?>
                    <div class="mt-2">
                        <?php foreach ($paliers as $palier): ?>
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span>
                                <?php 
                                if ($palier['max_value'] === null) {
                                    echo '≥ ' . number_format($palier['min_value'], 2, ',', ' ');
                                } else {
                                    echo number_format($palier['min_value'], 2, ',', ' ') . ' - ' . number_format($palier['max_value'], 2, ',', ' ');
                                }
                                
                                // Ajouter l'unité selon le type de commande
                                switch ($commande_data['type_commande']) {
                                    case 'poids':
                                        echo ' kg';
                                        break;
                                    case 'nombre':
                                        echo ' articles';
                                        break;
                                    case 'montant':
                                        echo ' €';
                                        break;
                                }
                                ?>
                            </span>
                            <span class="badge bg-primary"><?php echo number_format($palier['frais'], 2, ',', ' '); ?> €</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-muted mt-2"><?php echo __('no_shipping_tiers'); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong><?php echo __('visibility'); ?>:</strong>
                    <form method="post" action="" class="d-inline ms-2">
                        <input type="hidden" name="action" value="update_public_status">
                        <input type="hidden" name="is_public" value="<?php echo isset($commande_data['public']) && $commande_data['public'] ? '0' : '1'; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-<?php echo isset($commande_data['public']) && $commande_data['public'] ? 'warning' : 'success'; ?>">
                            <i class="fas fa-<?php echo isset($commande_data['public']) && $commande_data['public'] ? 'eye-slash' : 'eye'; ?>"></i>
                            <?php echo isset($commande_data['public']) && $commande_data['public'] ? __('make_private') : __('make_public'); ?>
                        </button>
                    </form>
                    <div class="mt-1">
                        <small class="text-muted">
                            <?php echo isset($commande_data['public']) && $commande_data['public'] 
                                ? __('public_order_description') 
                                : __('private_order_description'); ?>
                        </small>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-grid gap-2">
                    <form method="post" action="" class="mb-2">
                        <input type="hidden" name="action" value="send_reminder">
                        <button type="submit" class="btn btn-warning w-100" <?php echo $is_closed ? 'disabled' : ''; ?>>
                            <i class="fas fa-bell"></i> <?php echo __('send_reminder'); ?>
                        </button>
                    </form>
                    
                    <form method="post" action="" class="mb-2">
                        <input type="hidden" name="action" value="send_pickup">
                        <button type="submit" class="btn btn-info w-100">
                            <i class="fas fa-truck"></i> <?php echo __('send_pickup'); ?>
                        </button>
                    </form>
                    
                    <div class="mb-2">
                        <label class="form-label"><?php echo __('export_pdf'); ?>:</label>
                        <div class="d-grid gap-2">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="generate_pdf">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-file-pdf"></i> Résumé entreprise
                                </button>
                            </form>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="generate_checklist_pdf">
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-list-check"></i> <?php echo __('export_checklist'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <form method="post" action="" class="mb-2">
                        <input type="hidden" name="action" value="export_json">
                        <button type="submit" class="btn btn-info w-100">
                            <i class="fas fa-file-code"></i> <?php echo __('export_json'); ?>
                        </button>
                    </form>
                    
                    <form method="post" action="" class="mb-2">
                        <input type="hidden" name="action" value="duplicate_order">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-copy"></i> <?php echo __('duplicate_order'); ?>
                        </button>
                    </form>
                </div>
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
                    <div class="<?php echo $commande_data['type_commande'] !== 'sans_frais' ? 'col-md-3' : 'col-md-4'; ?> text-center">
                        <h3><?php echo $nb_participants; ?></h3>
                        <p><?php echo __('participants'); ?></p>
                    </div>
                    <div class="<?php echo $commande_data['type_commande'] !== 'sans_frais' ? 'col-md-3' : 'col-md-4'; ?> text-center">
                        <h3><?php echo count($commande_data['produits']); ?></h3>
                        <p><?php echo __('products'); ?></p>
                    </div>
                    <div class="<?php echo $commande_data['type_commande'] !== 'sans_frais' ? 'col-md-3' : 'col-md-4'; ?> text-center">
                        <h3><?php echo number_format($commande_data['montant_total'], 2, ',', ' '); ?> €</h3>
                        <p><?php echo __('total_amount'); ?></p>
                    </div>
                    <?php if ($commande_data['type_commande'] !== 'sans_frais'): ?>
                    <div class="col-md-3 text-center">
                        <h3><?php echo number_format($commande_data['frais_port'], 2, ',', ' '); ?> €</h3>
                        <p><?php echo __('shipping_fee'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-6">
                        <h5><?php echo __('total_weight'); ?></h5>
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
                                echo __('current_tier') . ': ' . $current_palier['min'] . ' - ' . $current_palier['max'] . ' kg';
                                
                                if ($next_palier) {
                                    $reste = $next_palier['min'] - $commande_data['poids_total'];
                                    if ($reste > 0) {
                                        echo ' (' . __('still_need') . ' ' . number_format($reste, 2, ',', ' ') . ' kg ' . __('for_next_tier') . ')';
                                    }
                                }
                            }
                            ?>
                        </small>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><?php echo __('payments'); ?></h5>
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
                            <?php echo __('paid_amount') . ': ' . number_format($total_paid, 2, ',', ' '); ?> € '<?php echo __('received_from'); ?> <?php echo number_format($total_amount, 2, ',', ' '); ?> €
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
                <h5 class="mb-0"><?php echo __('products'); ?></h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal" <?php echo $is_closed ? 'disabled' : ''; ?>>
                    <?php echo __('add_product'); ?>
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($commande_data['produits'])): ?>
                <div class="alert alert-info">
                    <?php echo __('no_products_added'); ?>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($commande_data['produits'] as $produit): ?>
                    <div class="col-md-6 mb-4">
                        <div class="product-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>
                                    <?php echo htmlspecialchars($produit['nom']); ?>
                                    <?php if (!empty($produit['url'])): ?>
                                    <a href="<?php echo htmlspecialchars($produit['url']); ?>" target="_blank" class="product-link ms-2" data-bs-toggle="tooltip" title="<?php echo __('more_info'); ?>">
                                        <small><i class="fas fa-info-circle"></i> <?php echo __('info'); ?></small>
                                    </a>
                                    <?php endif; ?>
                                </h5>
                                <form method="post" action="" onsubmit="return confirm('<?php echo __('are_you_sure_delete_product'); ?>');">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="produit_id" value="<?php echo $produit['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" <?php echo $is_closed ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('variation'); ?></th>
                                            <th><?php echo __('weight'); ?></th>
                                            <th><?php echo __('price'); ?></th>
                                            <th><?php echo __('actions'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($produit['variations'] as $variation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($variation['nom']); ?></td>
                                            <td><?php echo number_format($variation['poids'], 2, ',', ' '); ?> kg</td>
                                            <td><?php echo number_format($variation['prix'], 2, ',', ' '); ?> €</td>
                                            <td>
                                                <form method="post" action="" onsubmit="return confirm('<?php echo __('are_you_sure_delete_variation'); ?>');">
                                                    <input type="hidden" name="action" value="delete_variation">
                                                    <input type="hidden" name="produit_id" value="<?php echo $produit['id']; ?>">
                                                    <input type="hidden" name="variation_id" value="<?php echo $variation['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" <?php echo $is_closed ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
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
                <h5 class="mb-0"><?php echo __('participants'); ?></h5>
            </div>
            <div class="card-body">
                <?php if (empty($commande_data['participants'])): ?>
                <div class="alert alert-info">
                    <?php echo __('no_participants_joined'); ?>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php echo __('participant'); ?></th>
                                <th><?php echo __('products'); ?></th>
                                <th><?php echo __('product_amount'); ?></th>
                                <?php if ($commande_data['type_commande'] !== 'sans_frais'): ?>
                                <th><?php echo __('shipping_fee'); ?></th>
                                <?php endif; ?>
                                <th><?php echo __('total'); ?></th>
                                <th><?php echo __('payment_status'); ?></th>
                                <th><?php echo __('actions'); ?></th>
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
                                    <span class="text-muted"><?php echo __('no_products'); ?></span>
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
                                <?php if ($commande_data['type_commande'] !== 'sans_frais'): ?>
                                <td><?php echo number_format($participant['part_frais_port'], 2, ',', ' '); ?> €</td>
                                <?php endif; ?>
                                <td><?php echo number_format($participant['montant_total'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <span class="<?php echo $participant['statut_paiement'] ? 'payment-status-paid' : 'payment-status-unpaid'; ?>">
                                        <?php echo $participant['statut_paiement'] ? __('paid') : __('unpaid'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="" class="d-inline">
                                        <input type="hidden" name="action" value="update_payment">
                                        <input type="hidden" name="participant_id" value="<?php echo $participant['user_id']; ?>">
                                        <input type="hidden" name="statut" value="<?php echo $participant['statut_paiement'] ? '0' : '1'; ?>">
                                        <button type="submit" class="btn btn-sm btn-<?php echo $participant['statut_paiement'] ? 'warning' : 'success'; ?>">
                                            <?php echo $participant['statut_paiement'] ? __('mark_as_unpaid') : __('mark_as_paid'); ?>
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
                    <h5 class="modal-title" id="addProductModalLabel"><?php echo __('add_product'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nom_produit" class="form-label required-field"><?php echo __('product_name'); ?></label>
                        <input type="text" class="form-control" id="nom_produit" name="nom_produit" required>
                    </div>

                    <div class="mb-3">
                        <label for="url_produit" class="form-label"><?php echo __('product_info_url'); ?></label>
                        <input type="url" class="form-control" id="url_produit" name="url_produit" placeholder="https://...">
                        <small class="form-text text-muted"><?php echo __('product_info_url_description'); ?></small>
                    </div>
                    
                    <h5 class="mt-4 mb-3"><?php echo __('variations'); ?></h5>
                    
                    <div id="variations-container">
                        <div class="variation-row row mb-3">
                            <div class="col-md-4">
                                <input type="text" name="variations[0][nom]" class="form-control" placeholder="<?php echo __('variation_name'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="variations[0][poids]" class="form-control" placeholder="<?php echo __('weight'); ?>" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="variations[0][prix]" class="form-control" placeholder="<?php echo __('price'); ?>" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100" onclick="removeVariation(this)" disabled><?php echo __('delete'); ?></button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary mt-2" onclick="addVariationField()">
                        <?php echo __('add_variation'); ?>
                    </button>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('add'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour modifier les dates -->
<div class="modal fade" id="updateDatesModal" tabindex="-1" aria-labelledby="updateDatesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="update_dates">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="updateDatesModalLabel"><?php echo __('modify_dates'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="date_limite" class="form-label required-field"><?php echo __('deadline'); ?></label>
                        <input type="datetime-local" class="form-control" id="date_limite" name="date_limite" 
                               value="<?php echo $date_limite->format('Y-m-d\TH:i'); ?>" required>
                        <small class="form-text text-muted"><?php echo __('deadline_description'); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_recuperation" class="form-label required-field"><?php echo __('pickup'); ?></label>
                        <input type="datetime-local" class="form-control" id="date_recuperation" name="date_recuperation" 
                               value="<?php echo $date_recup->format('Y-m-d\TH:i'); ?>" required>
                        <small class="form-text text-muted"><?php echo __('pickup_description'); ?></small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('update'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour modifier la description -->
<div class="modal fade" id="updateDescriptionModal" tabindex="-1" aria-labelledby="updateDescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <input type="hidden" name="action" value="update_description">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="updateDescriptionModalLabel"><?php echo __('order_description'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="description" class="form-label"><?php echo __('description'); ?></label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php echo isset($commande_data['description']) ? htmlspecialchars($commande_data['description']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('save'); ?></button>
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
    
    alert('<?php echo __('link_copied'); ?>');
}
</script>

<?php require_once 'includes/footer.php'; ?>