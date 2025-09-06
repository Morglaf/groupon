<?php
require_once 'includes/config.php';
require_once 'includes/lang.php';
require_once 'includes/header.php';
$page_title = __('create_order');

// Rediriger si non connecté
requireLogin();

$user_id = getCurrentUserId();
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données
    $titre = trim($_POST['titre'] ?? '');
    $type_commande = $_POST['type_commande'] ?? '';
    $date_limite = $_POST['date_limite'] ?? '';
    $date_recuperation = $_POST['date_recuperation'] ?? '';
    $adresse_recuperation = trim($_POST['adresse_recuperation'] ?? '');
    
    // Valider les données
    if (empty($titre)) {
        $errors[] = __('required_field');
    }
    
    if (empty($type_commande) || !in_array($type_commande, ['poids', 'nombre', 'montant'])) {
        $errors[] = __('invalid_order_type');
    }
    
    if (empty($date_limite)) {
        $errors[] = __('required_field');
    } else {
        try {
            $date_limite_obj = new DateTime($date_limite);
            $now = new DateTime();
            if ($date_limite_obj <= $now) {
                $errors[] = __('future_date');
            }
        } catch (Exception $e) {
            $errors[] = __('invalid_date');
        }
    }
    
    if (empty($date_recuperation)) {
        $errors[] = __('required_field');
    } else {
        try {
            $date_recuperation_obj = new DateTime($date_recuperation);
            $date_limite_obj = new DateTime($date_limite);
            if ($date_recuperation_obj <= $date_limite_obj) {
                $errors[] = __('pickup_after_deadline');
            }
        } catch (Exception $e) {
            $errors[] = __('invalid_date');
        }
    }
    
    if (empty($adresse_recuperation)) {
        $errors[] = __('required_field');
    }
    
    // Valider les paliers
    $paliers = [];
    if (isset($_POST['palier_min']) && is_array($_POST['palier_min'])) {
        for ($i = 0; $i < count($_POST['palier_min']); $i++) {
            $min = floatval($_POST['palier_min'][$i]);
            $max = !empty($_POST['palier_max'][$i]) ? floatval($_POST['palier_max'][$i]) : null;
            $frais = floatval($_POST['palier_frais'][$i]);
            
            if ($min >= 0 && $frais >= 0) {
                $paliers[] = [
                    'min' => $min,
                    'max' => $max,
                    'frais' => $frais
                ];
            }
        }
    }
    
    if (empty($paliers)) {
        $errors[] = __('at_least_one_tier');
    }
    
    // Valider les produits
    $produits = [];
    if (isset($_POST['produit_nom']) && is_array($_POST['produit_nom'])) {
        for ($i = 0; $i < count($_POST['produit_nom']); $i++) {
            $nom = trim($_POST['produit_nom'][$i]);
            $url = trim($_POST['produit_url'][$i] ?? '');
            $variations = [];
            
            // Récupérer les variations pour ce produit
            if (isset($_POST['variation_nom'][$i]) && is_array($_POST['variation_nom'][$i])) {
                for ($j = 0; $j < count($_POST['variation_nom'][$i]); $j++) {
                    $var_nom = trim($_POST['variation_nom'][$i][$j]);
                    $var_poids = floatval($_POST['variation_poids'][$i][$j]);
                    $var_prix = floatval($_POST['variation_prix'][$i][$j]);
                    
                    if (!empty($var_nom) && $var_prix > 0) {
                        $variations[] = [
                            'nom' => $var_nom,
                            'poids' => $var_poids,
                            'prix' => $var_prix
                        ];
                    }
                }
            }
            
            if (!empty($nom) && !empty($variations)) {
                // Valider l'URL si présente
                if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = __('invalid_url');
                    continue;
                }
                
                $produits[] = [
                    'nom' => $nom,
                    'url' => $url,
                    'variations' => $variations
                ];
            } elseif (!empty($nom) && empty($variations)) {
                $errors[] = __('at_least_one_variation');
            }
        }
    }
    
    // Créer la commande si pas d'erreurs
    if (empty($errors)) {
        $commande_id = createCommande(
            $user_id,
            $titre,
            $type_commande,
            $date_limite,
            $date_recuperation,
            $adresse_recuperation,
            $paliers,
            $produits
        );
        
        if ($commande_id) {
            $_SESSION['flash_message'] = __('order_created');
            $_SESSION['flash_type'] = 'success';
            header('Location: admin_commande.php?id=' . $commande_id);
            exit;
        } else {
            $errors[] = __('error_occurred');
        }
    }
}
?>

<h1 class="mb-4"><?php echo __('create_order'); ?></h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" action="" id="create-commande-form">
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?php echo __('information'); ?></h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="titre" class="form-label"><?php echo __('order_title'); ?></label>
                <input type="text" class="form-control" id="titre" name="titre" required>
            </div>
            
            <div class="mb-3">
                <label for="type_commande" class="form-label"><?php echo __('order_type'); ?></label>
                <select class="form-select" id="type_commande" name="type_commande" required>
                    <option value="poids"><?php echo __('weight_based'); ?></option>
                    <option value="nombre"><?php echo __('quantity_based'); ?></option>
                    <option value="montant"><?php echo __('amount_based'); ?></option>
                </select>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_limite" class="form-label"><?php echo __('deadline'); ?></label>
                    <input type="datetime-local" class="form-control" id="date_limite" name="date_limite" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="date_recuperation" class="form-label"><?php echo __('pickup_date'); ?></label>
                    <input type="datetime-local" class="form-control" id="date_recuperation" name="date_recuperation" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="adresse_recuperation" class="form-label"><?php echo __('pickup_address'); ?></label>
                <textarea class="form-control" id="adresse_recuperation" name="adresse_recuperation" rows="2" required></textarea>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo __('tiers'); ?></h5>
        </div>
        <div class="card-body">
            <div id="paliers-container">
                <!-- Les paliers seront ajoutés ici dynamiquement -->
            </div>
            
            <button type="button" class="btn btn-outline-primary mt-3" id="add-palier-btn">
                <i class="fas fa-plus"></i> <?php echo __('add_tier'); ?>
            </button>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo __('products'); ?></h5>
        </div>
        <div class="card-body">
            <div id="produits-container">
                <!-- Les produits seront ajoutés ici dynamiquement -->
            </div>
            
            <button type="button" class="btn btn-outline-primary mt-3" id="add-produit-btn">
                <i class="fas fa-plus"></i> <?php echo __('add_product'); ?>
            </button>
        </div>
    </div>
    
    <div class="d-flex justify-content-between">
        <a href="dashboard.php" class="btn btn-secondary"><?php echo __('cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><?php echo __('create_order_btn'); ?></button>
    </div>
</form>

<!-- Template pour un palier -->
<template id="palier-template">
    <div class="palier-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label"><?php echo __('min'); ?></label>
                <input type="number" class="form-control palier-min" name="palier_min[]" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label"><?php echo __('max'); ?></label>
                <input type="number" class="form-control palier-max" name="palier_max[]" min="0" step="0.01">
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label"><?php echo __('shipping_cost'); ?></label>
                <input type="number" class="form-control palier-frais" name="palier_frais[]" min="0" step="0.01" required>
            </div>
            <div class="col-md-1 d-flex align-items-end mb-2">
                <button type="button" class="btn btn-outline-danger remove-palier-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<!-- Template pour un produit -->
<template id="produit-template">
    <div class="produit-item border rounded p-3 mb-3">
        <div class="row mb-3">
            <div class="col-md-6 mb-2">
                <label class="form-label"><?php echo __('product_name'); ?></label>
                <input type="text" class="form-control produit-nom" name="produit_nom[]" required>
            </div>
            <div class="col-md-5 mb-2">
                <label class="form-label"><?php echo __('product_url'); ?></label>
                <input type="url" class="form-control produit-url" name="produit_url[]" placeholder="https://">
                <div class="form-text"><?php echo __('product_url_help'); ?></div>
            </div>
            <div class="col-md-1 d-flex align-items-end mb-2">
                <button type="button" class="btn btn-outline-danger remove-produit-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <h6><?php echo __('product_variation'); ?></h6>
        <div class="variations-container">
            <!-- Les variations seront ajoutées ici dynamiquement -->
        </div>
        
        <button type="button" class="btn btn-outline-secondary mt-3 add-variation-btn">
            <i class="fas fa-plus"></i> <?php echo __('add_variation'); ?>
        </button>
    </div>
</template>

<!-- Template pour une variation -->
<template id="variation-template">
    <div class="variation-item border-top pt-3 pb-2 mb-2">
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label"><?php echo __('variation_name'); ?></label>
                <input type="text" class="form-control variation-nom" name="variation_nom[0][]" required>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label"><?php echo __('product_weight'); ?> (kg)</label>
                <input type="number" class="form-control variation-poids" name="variation_poids[0][]" min="0" step="0.001" required>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label"><?php echo __('product_price'); ?> (€)</label>
                <input type="number" class="form-control variation-prix" name="variation_prix[0][]" min="0" step="0.01" required>
            </div>
            <div class="col-md-2 d-flex align-items-end mb-2">
                <button type="button" class="btn btn-outline-danger remove-variation-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser avec un palier par défaut
    addPalier();
    
    // Initialiser avec un produit par défaut
    addProduit();
    
    // Gestionnaire pour ajouter un palier
    document.getElementById('add-palier-btn').addEventListener('click', addPalier);
    
    // Gestionnaire pour ajouter un produit
    document.getElementById('add-produit-btn').addEventListener('click', addProduit);
    
    // Délégation d'événements pour les boutons de suppression
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-palier-btn')) {
            const palierItem = e.target.closest('.palier-item');
            if (document.querySelectorAll('.palier-item').length > 1) {
                palierItem.remove();
            }
        }
        
        if (e.target.closest('.remove-produit-btn')) {
            const produitItem = e.target.closest('.produit-item');
            if (document.querySelectorAll('.produit-item').length > 1) {
                produitItem.remove();
            }
        }
        
        if (e.target.closest('.remove-variation-btn')) {
            const variationItem = e.target.closest('.variation-item');
            const variationsContainer = variationItem.closest('.variations-container');
            if (variationsContainer.querySelectorAll('.variation-item').length > 1) {
                variationItem.remove();
            }
        }
        
        if (e.target.closest('.add-variation-btn')) {
            const produitItem = e.target.closest('.produit-item');
            const produitIndex = Array.from(document.querySelectorAll('.produit-item')).indexOf(produitItem);
            addVariation(produitItem, produitIndex);
        }
    });
});

function addPalier() {
    const template = document.getElementById('palier-template');
    const container = document.getElementById('paliers-container');
    const clone = document.importNode(template.content, true);
    container.appendChild(clone);
}

function addProduit() {
    const template = document.getElementById('produit-template');
    const container = document.getElementById('produits-container');
    const clone = document.importNode(template.content, true);
    
    // Ajouter le produit au conteneur
    container.appendChild(clone);
    
    // Récupérer l'élément produit ajouté
    const produitItem = container.lastElementChild;
    const produitIndex = Array.from(container.querySelectorAll('.produit-item')).indexOf(produitItem);
    
    // Mettre à jour les noms des champs pour ce produit
    updateProduitIndexes();
    
    // Ajouter une variation par défaut
    addVariation(produitItem, produitIndex);
}

function addVariation(produitItem, produitIndex) {
    const template = document.getElementById('variation-template');
    const container = produitItem.querySelector('.variations-container');
    const clone = document.importNode(template.content, true);
    
    // Mettre à jour les noms des champs pour cette variation
    clone.querySelectorAll('input[name^="variation_"]').forEach(input => {
        const name = input.name.replace(/\[\d+\]/, '[' + produitIndex + ']');
        input.name = name;
    });
    
    container.appendChild(clone);
}

function updateProduitIndexes() {
    const produits = document.querySelectorAll('.produit-item');
    produits.forEach((produit, produitIndex) => {
        produit.querySelectorAll('input[name^="variation_"]').forEach(input => {
            const baseName = input.name.split('[')[0];
            const variationIndex = input.name.match(/\[\d+\]\[(\d+)\]/)[1];
            input.name = baseName + '[' + produitIndex + '][' + variationIndex + ']';
        });
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>