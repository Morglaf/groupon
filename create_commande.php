<?php
$page_title = "Créer une commande";
require_once 'includes/header.php';

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
        $errors[] = 'Le titre est requis.';
    }
    
    if (empty($type_commande) || !in_array($type_commande, ['poids', 'nombre', 'montant'])) {
        $errors[] = 'Le type de commande est invalide.';
    }
    
    if (empty($date_limite)) {
        $errors[] = 'La date limite est requise.';
    } else {
        try {
            $date_limite_obj = new DateTime($date_limite);
            $now = new DateTime();
            if ($date_limite_obj <= $now) {
                $errors[] = 'La date limite doit être ultérieure à maintenant.';
            }
        } catch (Exception $e) {
            $errors[] = 'Format de date limite invalide.';
        }
    }
    
    if (empty($date_recuperation)) {
        $errors[] = 'La date de récupération est requise.';
    } else {
        try {
            $date_recuperation_obj = new DateTime($date_recuperation);
            $date_limite_obj = new DateTime($date_limite);
            if ($date_recuperation_obj <= $date_limite_obj) {
                $errors[] = 'La date de récupération doit être ultérieure à la date limite.';
            }
        } catch (Exception $e) {
            $errors[] = 'Format de date de récupération invalide.';
        }
    }
    
    if (empty($adresse_recuperation)) {
        $errors[] = 'L\'adresse de récupération est requise.';
    }
    
    // Valider les paliers
    $paliers = [];
    if (isset($_POST['paliers'])) {
        foreach ($_POST['paliers'] as $palier) {
            if (isset($palier['min'], $palier['max'], $palier['frais']) 
                && is_numeric($palier['min']) 
                && is_numeric($palier['max']) 
                && is_numeric($palier['frais'])
                && $palier['min'] < $palier['max']) {
                $paliers[] = [
                    'min' => (float) $palier['min'],
                    'max' => (float) $palier['max'],
                    'frais' => (float) $palier['frais']
                ];
            }
        }
    }
    
    if (empty($paliers)) {
        $errors[] = 'Au moins un palier de frais est requis.';
    }
    
    // Création de la commande
    if (empty($errors)) {
        $commande_data = createCommande(
            $user_id,
            $titre,
            $type_commande,
            $paliers,
            $date_limite,
            $date_recuperation,
            $adresse_recuperation
        );
        
        if ($commande_data) {
            $_SESSION['flash_message'] = 'Commande créée avec succès.';
            $_SESSION['flash_type'] = 'success';
            
            header('Location: admin_commande.php?id=' . $commande_data['id']);
            exit;
        } else {
            $errors[] = 'Erreur lors de la création de la commande.';
        }
    }
}
?>

<h1 class="mb-4">Créer une nouvelle commande</h1>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="">
            <!-- Informations générales -->
            <h3 class="mb-3">Informations générales</h3>
            
            <div class="mb-3">
                <label for="titre" class="form-label required-field">Titre de la commande</label>
                <input type="text" class="form-control" id="titre" name="titre" required
                       value="<?php echo isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : ''; ?>">
                <small class="form-text text-muted">Ex: Commande fromage Juin 2023</small>
            </div>
            
            <div class="mb-3">
                <label for="type_commande" class="form-label required-field">Type de commande</label>
                <select class="form-select" id="type_commande" name="type_commande" required>
                    <option value="">-- Sélectionner --</option>
                    <option value="poids" <?php echo (isset($_POST['type_commande']) && $_POST['type_commande'] === 'poids') ? 'selected' : ''; ?>>
                        Basée sur le poids total
                    </option>
                    <option value="nombre" <?php echo (isset($_POST['type_commande']) && $_POST['type_commande'] === 'nombre') ? 'selected' : ''; ?>>
                        Basée sur le nombre de produits
                    </option>
                    <option value="montant" <?php echo (isset($_POST['type_commande']) && $_POST['type_commande'] === 'montant') ? 'selected' : ''; ?>>
                        Basée sur le coût total
                    </option>
                </select>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_limite" class="form-label required-field">Date limite de commande</label>
                    <input type="datetime-local" class="form-control" id="date_limite" name="date_limite" required
                           value="<?php echo isset($_POST['date_limite']) ? htmlspecialchars($_POST['date_limite']) : ''; ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="date_recuperation" class="form-label required-field">Date de récupération</label>
                    <input type="datetime-local" class="form-control" id="date_recuperation" name="date_recuperation" required
                           value="<?php echo isset($_POST['date_recuperation']) ? htmlspecialchars($_POST['date_recuperation']) : ''; ?>">
                </div>
            </div>
            
            <div class="mb-4">
                <label for="adresse_recuperation" class="form-label required-field">Adresse de récupération</label>
                <textarea class="form-control" id="adresse_recuperation" name="adresse_recuperation" rows="2" required><?php echo isset($_POST['adresse_recuperation']) ? htmlspecialchars($_POST['adresse_recuperation']) : ''; ?></textarea>
            </div>
            
            <!-- Paliers de frais de port -->
            <h3 class="mb-3" id="paliers-section-title">Paliers de frais de port</h3>
            
            <div id="paliers-container">
                <!-- Les paliers de frais de port seront ajoutés ici dynamiquement -->
                <div class="palier-row row mb-3">
                    <div class="col-md-3">
                        <input type="number" name="paliers[0][min]" class="form-control" placeholder="Min" step="0.01" min="0" required
                               value="<?php echo isset($_POST['paliers'][0]['min']) ? htmlspecialchars($_POST['paliers'][0]['min']) : '0'; ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="paliers[0][max]" class="form-control" placeholder="Max" step="0.01" min="0" required
                               value="<?php echo isset($_POST['paliers'][0]['max']) ? htmlspecialchars($_POST['paliers'][0]['max']) : '1.7'; ?>">
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="paliers[0][frais]" class="form-control" placeholder="Frais (€)" step="0.01" min="0" required
                               value="<?php echo isset($_POST['paliers'][0]['frais']) ? htmlspecialchars($_POST['paliers'][0]['frais']) : '24.00'; ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger w-100" onclick="removePalier(this)" disabled>Supprimer</button>
                    </div>
                </div>
                
                <?php 
                // Si des paliers supplémentaires ont été soumis, les afficher
                if (isset($_POST['paliers']) && count($_POST['paliers']) > 1) {
                    for ($i = 1; $i < count($_POST['paliers']); $i++) {
                        echo '<div class="palier-row row mb-3">';
                        echo '<div class="col-md-3">';
                        echo '<input type="number" name="paliers[' . $i . '][min]" class="form-control" placeholder="Min" step="0.01" min="0" required value="' . htmlspecialchars($_POST['paliers'][$i]['min']) . '">';
                        echo '</div>';
                        echo '<div class="col-md-3">';
                        echo '<input type="number" name="paliers[' . $i . '][max]" class="form-control" placeholder="Max" step="0.01" min="0" required value="' . htmlspecialchars($_POST['paliers'][$i]['max']) . '">';
                        echo '</div>';
                        echo '<div class="col-md-4">';
                        echo '<input type="number" name="paliers[' . $i . '][frais]" class="form-control" placeholder="Frais (€)" step="0.01" min="0" required value="' . htmlspecialchars($_POST['paliers'][$i]['frais']) . '">';
                        echo '</div>';
                        echo '<div class="col-md-2">';
                        echo '<button type="button" class="btn btn-danger w-100" onclick="removePalier(this)">Supprimer</button>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            
            <div class="mb-4">
                <button type="button" class="btn btn-secondary" onclick="addPalierField()">
                    Ajouter un palier
                </button>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Créer la commande</button>
                <a href="/dashboard.php" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>