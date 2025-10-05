// Fonctions utilitaires pour l'application Groupon

// Fonction pour copier le lien de partage
function copyShareLink() {
    const shareLink = document.getElementById('share-link');
    if (!shareLink) return;
    
    shareLink.select();
    shareLink.setSelectionRange(0, 99999); // Pour mobile
    
    try {
        document.execCommand('copy');
        alert('Lien copié dans le presse-papier !');
    } catch (err) {
        console.error('Erreur lors de la copie du lien:', err);
        alert('Impossible de copier le lien. Veuillez le sélectionner et le copier manuellement.');
    }
}

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
    const commandeType = document.getElementById('type_commande');
    const isRequired = commandeType && commandeType.value !== 'sans_frais';
    const requiredAttr = isRequired ? 'required' : '';
    
    const palierHtml = `
        <div class="palier-row row mb-3">
            <div class="col-md-3">
                <input type="number" name="paliers[${palierIndex}][min]" class="form-control palier-min" placeholder="Min" step="0.01" min="0" ${requiredAttr}>
            </div>
            <div class="col-md-3">
                <input type="number" name="paliers[${palierIndex}][max]" class="form-control palier-max" placeholder="Max" step="0.01" min="0" ${requiredAttr}>
            </div>
            <div class="col-md-4">
                <input type="number" name="paliers[${palierIndex}][frais]" class="form-control palier-frais" placeholder="Frais (€)" step="0.01" min="0" ${requiredAttr}>
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
    const paliersContainer = document.getElementById('paliers-container');
    const paliersCard = document.querySelector('#paliers-container')?.closest('.card');
    
    if (!commandeType) return;
    
    const selectedType = commandeType.value;
    
    // Gérer l'affichage des paliers selon le type de commande
    if (selectedType === 'sans_frais') {
        // Masquer la section des paliers
        if (paliersCard) {
            paliersCard.style.display = 'none';
        }
        
        // Enlever l'attribut required des champs de paliers
        const palierInputs = document.querySelectorAll('.palier-min, .palier-max, .palier-frais');
        palierInputs.forEach(input => {
            input.removeAttribute('required');
        });
    } else {
        // Afficher la section des paliers
        if (paliersCard) {
            paliersCard.style.display = 'block';
        }
        
        // Remettre l'attribut required aux champs de paliers
        const palierInputs = document.querySelectorAll('.palier-min, .palier-max, .palier-frais');
        palierInputs.forEach(input => {
            input.setAttribute('required', 'required');
        });
        
        // Mettre à jour le titre selon le type
        if (paliersSectionTitle) {
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
    
    // Déclencher l'événement change pour s'assurer que la valeur est bien prise en compte
    inputElement.dispatchEvent(new Event('change', { bubbles: true }));
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
    
    if (body.classList.contains('dark-mode')) {
        // Passer au thème clair
        body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
        
        // Changer la navbar
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.className = navbar.className.replace('navbar-dark bg-dark', 'navbar-light bg-light');
        }
        
        // Mettre à jour l'état des switches si présents
        const themeSwitch = document.getElementById('theme-switch');
        const themeSwitchMobile = document.getElementById('theme-switch-mobile');
        if (themeSwitch) {
            themeSwitch.checked = false;
        }
        if (themeSwitchMobile) {
            themeSwitchMobile.checked = false;
        }
        
        // Supprimer la feuille de style du thème sombre si elle existe
        const darkThemeLink = document.querySelector('link[href="css/dark-theme.css"]');
        if (darkThemeLink) {
            darkThemeLink.disabled = true;
            darkThemeLink.parentNode.removeChild(darkThemeLink);
        }
        
        // Envoyer la préférence au serveur
        fetch('toggle_theme.php?theme=light', { method: 'GET' })
            .then(response => response.json())
            .then(data => {
                // Thème enregistré côté serveur
            })
            .catch(error => {
                console.error('Erreur lors de l\'enregistrement du thème:', error);
            });
    } else {
        // Passer au thème sombre
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
        
        // Changer la navbar
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.className = navbar.className.replace('navbar-light bg-light', 'navbar-dark bg-dark');
        }
        
        // Mettre à jour l'état des switches si présents
        const themeSwitch = document.getElementById('theme-switch');
        const themeSwitchMobile = document.getElementById('theme-switch-mobile');
        if (themeSwitch) {
            themeSwitch.checked = true;
        }
        if (themeSwitchMobile) {
            themeSwitchMobile.checked = true;
        }
        
        // Ajouter la feuille de style du thème sombre si elle n'existe pas
        if (!document.querySelector('link[href="css/dark-theme.css"]')) {
            const darkThemeLink = document.createElement('link');
            darkThemeLink.rel = 'stylesheet';
            darkThemeLink.href = 'css/dark-theme.css';
            document.head.appendChild(darkThemeLink);
        }
        
        // Envoyer la préférence au serveur
        fetch('toggle_theme.php?theme=dark', { method: 'GET' })
            .then(response => response.json())
            .then(data => {
                // Thème enregistré côté serveur
            })
            .catch(error => {
                console.error('Erreur lors de l\'enregistrement du thème:', error);
            });
    }
}

// Initialisation du thème au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer le thème depuis le localStorage ou utiliser le thème clair par défaut
    const savedTheme = localStorage.getItem('theme') || 'light';
    
    // Appliquer le thème sauvegardé
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        
        // Ajouter la feuille de style du thème sombre
        if (!document.querySelector('link[href="css/dark-theme.css"]')) {
            const darkThemeLink = document.createElement('link');
            darkThemeLink.rel = 'stylesheet';
            darkThemeLink.href = 'css/dark-theme.css';
            document.head.appendChild(darkThemeLink);
        }
        
        // Mettre à jour la navbar
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.className = navbar.className.replace('navbar-light bg-light', 'navbar-dark bg-dark');
        }
        
        // Mettre à jour l'état des switches si présents
        const themeSwitch = document.getElementById('theme-switch');
        const themeSwitchMobile = document.getElementById('theme-switch-mobile');
        if (themeSwitch) {
            themeSwitch.checked = true;
        }
        if (themeSwitchMobile) {
            themeSwitchMobile.checked = true;
        }
    } else {
        document.body.classList.remove('dark-mode');
        
        // Supprimer la feuille de style du thème sombre si elle existe
        const darkThemeLink = document.querySelector('link[href="css/dark-theme.css"]');
        if (darkThemeLink) {
            darkThemeLink.disabled = true;
            darkThemeLink.parentNode.removeChild(darkThemeLink);
        }
        
        // Mettre à jour la navbar
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.className = navbar.className.replace('navbar-dark bg-dark', 'navbar-light bg-light');
        }
        
        // Mettre à jour l'état des switches si présents
        const themeSwitch = document.getElementById('theme-switch');
        const themeSwitchMobile = document.getElementById('theme-switch-mobile');
        if (themeSwitch) {
            themeSwitch.checked = false;
        }
        if (themeSwitchMobile) {
            themeSwitchMobile.checked = false;
        }
    }
    
    // Ajouter l'écouteur d'événement pour les switches de thème
    const themeSwitch = document.getElementById('theme-switch');
    const themeSwitchMobile = document.getElementById('theme-switch-mobile');
    
    if (themeSwitch) {
        themeSwitch.addEventListener('change', toggleTheme);
    }
    if (themeSwitchMobile) {
        themeSwitchMobile.addEventListener('change', toggleTheme);
    }
    
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
    
    // Écouteur pour les liens de produits (ouvrir dans une nouvelle fenêtre)
    const productLinks = document.querySelectorAll('.product-link');
    productLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            window.open(this.getAttribute('href'), '_blank');
        });
    });
    
    // Initialiser les dropdowns Bootstrap
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Ajouter des écouteurs d'événements pour les dropdowns
    document.querySelectorAll('.dropdown').forEach(function(dropdown) {
        dropdown.addEventListener('mouseenter', function() {
            const dropdownMenu = this.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.classList.add('show');
            }
        });
        
        dropdown.addEventListener('mouseleave', function() {
            const dropdownMenu = this.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.classList.remove('show');
            }
        });
    });
    
    // Ajouter des écouteurs pour les champs de quantité
    document.querySelectorAll('input[name^="quantity["]').forEach(function(input) {
        input.addEventListener('change', function() {
            // S'assurer que la valeur est un entier positif
            let value = parseInt(this.value, 10);
            if (isNaN(value) || value < 0) {
                value = 0;
            }
            this.value = value;
        });
        
        input.addEventListener('input', function() {
            // Empêcher la saisie de valeurs négatives
            if (this.value < 0) {
                this.value = 0;
            }
        });
    });
});

// S'assurer que le thème est initialisé même si le DOM est déjà chargé
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initTheme();
}