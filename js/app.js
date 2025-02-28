// Fonctions utilitaires pour l'application Groupon

// Fonction pour ajouter un champ de variation
function addVariationField() {
    const variationsContainer = document.getElementById('variations-container');
    if (!variationsContainer) return;
    
    const variationIndex = document.querySelectorAll('.variation-row').length;
    
    const variationHtml = `
        <div class="variation-row row mb-3">
            <div class="col-md-4">
                <input type="text" name="variations[${variationIndex}][nom]" class="form-control" placeholder="Nom de la variation" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="variations[${variationIndex}][poids]" class="form-control" placeholder="Poids (kg)" step="0.01" min="0" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="variations[${variationIndex}][prix]" class="form-control" placeholder="Prix (€)" step="0.01" min="0" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger w-100" onclick="removeVariation(this)">Supprimer</button>
            </div>
        </div>
    `;
    
    variationsContainer.insertAdjacentHTML('beforeend', variationHtml);
}

// Fonction pour supprimer un champ de variation
function removeVariation(button) {
    const variationRow = button.closest('.variation-row');
    variationRow.remove();
}

// Fonction pour ajouter un champ de palier de frais
function addPalierField() {
    const paliersContainer = document.getElementById('paliers-container');
    if (!paliersContainer) return;
    
    const palierIndex = document.querySelectorAll('.palier-row').length;
    
    const palierHtml = `
        <div class="palier-row row mb-3">
            <div class="col-md-3">
                <input type="number" name="paliers[${palierIndex}][min]" class="form-control" placeholder="Min" step="0.01" min="0" required>
            </div>
            <div class="col-md-3">
                <input type="number" name="paliers[${palierIndex}][max]" class="form-control" placeholder="Max" step="0.01" min="0" required>
            </div>
            <div class="col-md-4">
                <input type="number" name="paliers[${palierIndex}][frais]" class="form-control" placeholder="Frais (€)" step="0.01" min="0" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger w-100" onclick="removePalier(this)">Supprimer</button>
            </div>
        </div>
    `;
    
    paliersContainer.insertAdjacentHTML('beforeend', palierHtml);
}

// Fonction pour supprimer un champ de palier
function removePalier(button) {
    const palierRow = button.closest('.palier-row');
    palierRow.remove();
}

// Fonction pour mettre à jour les champs affichés selon le type de commande
function updateCommandeTypeFields() {
    const commandeType = document.getElementById('type_commande');
    const paliersSectionTitle = document.getElementById('paliers-section-title');
    
    if (!commandeType || !paliersSectionTitle) return;
    
    const selectedType = commandeType.value;
    
    switch (selectedType) {
        case 'poids':
            paliersSectionTitle.textContent = 'Paliers de frais de port par poids';
            break;
        case 'nombre':
            paliersSectionTitle.textContent = 'Paliers de frais de port par nombre d\'articles';
            break;
        case 'montant':
            paliersSectionTitle.textContent = 'Paliers de frais de port par montant';
            break;
    }
}

// Fonction pour mettre à jour la quantité dans la commande
function updateQuantity(variationId, increment) {
    const inputElement = document.getElementById('quantity-' + variationId);
    if (!inputElement) return;
    
    let value = parseInt(inputElement.value, 10) || 0;
    value += increment;
    
    // Minimum value is 0
    if (value < 0) value = 0;
    
    inputElement.value = value;
}

// Fonction pour confirmer la suppression
function confirmDelete(message, formId) {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
    return false;
}

// Fonction pour basculer le thème
function toggleTheme() {
    const body = document.body;
    
    if (body.classList.contains('dark-theme')) {
        // Passer au thème clair
        body.classList.remove('dark-theme');
        localStorage.setItem('theme', 'light');
        
        // Mettre à jour l'état du switch si présent
        const themeSwitch = document.getElementById('theme-switch');
        if (themeSwitch) {
            themeSwitch.checked = false;
        }
        
        // Envoyer la préférence au serveur
        fetch('toggle_theme.php?theme=light', { method: 'GET' });
    } else {
        // Passer au thème sombre
        body.classList.add('dark-theme');
        localStorage.setItem('theme', 'dark');
        
        // Mettre à jour l'état du switch si présent
        const themeSwitch = document.getElementById('theme-switch');
        if (themeSwitch) {
            themeSwitch.checked = true;
        }
        
        // Envoyer la préférence au serveur
        fetch('toggle_theme.php?theme=dark', { method: 'GET' });
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser le type de commande
    updateCommandeTypeFields();
    
    // Ajouter un écouteur d'événement pour le changement de type de commande
    const commandeType = document.getElementById('type_commande');
    if (commandeType) {
        commandeType.addEventListener('change', updateCommandeTypeFields);
    }
    
    // Initialiser le thème
    const savedTheme = localStorage.getItem('theme');
    const themeSwitch = document.getElementById('theme-switch');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        if (themeSwitch) {
            themeSwitch.checked = true;
        }
    }
    
    // Ajouter l'écouteur d'événement pour le switch de thème
    if (themeSwitch) {
        themeSwitch.addEventListener('change', toggleTheme);
    }
    
    // Écouteur pour les liens de produits (ouvrir dans une nouvelle fenêtre)
    const productLinks = document.querySelectorAll('.product-link');
    productLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.open(this.getAttribute('href'), '_blank');
        });
    });
});