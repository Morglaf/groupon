# Documentation du Projet Groupon

## 📝 Table des matières
- [Vue d'ensemble](#vue-densemble)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Configuration](#configuration)
- [Tests et vérification](#tests-et-vérification)
- [Roadmap](#roadmap)
- [Contribution](#contribution)

## Vue d'ensemble
Ce projet est une plateforme de gestion de commandes inspirée de Groupon, permettant aux utilisateurs de créer, gérer et suivre leurs commandes. Le système inclut une interface d'administration complète et prend en charge plusieurs langues.

## Fonctionnalités
- 🔐 Système d'authentification complet (connexion, inscription, déconnexion)
- 👤 Gestion des profils utilisateurs
- 📦 Création et gestion des commandes
- 🛠️ Interface d'administration
- 🌍 Support multilingue
- 🎨 Thème clair/sombre
- 📱 Interface responsive

## Technologies utilisées
- PHP
- JavaScript
- HTML5/CSS3
- API REST
- Bootstrap
- Stockage de données en JSON

## Installation
1. Clonez le dépôt
2. Configurez votre serveur web (Apache/Nginx)
3. Configurez les paramètres dans `includes/config.php`
4. Lancez l'application

## Configuration
### Prérequis
- PHP 7.4 ou supérieur
- Serveur web (Apache/Nginx)

### Configuration du stockage de données
1. Assurez-vous que les dossiers `data/users`, `data/commandes` et `data/exports` existent et sont accessibles en écriture
2. Aucune base de données SQL n'est nécessaire, toutes les données sont stockées dans des fichiers JSON

Pour plus de détails sur la configuration, consultez le [guide de configuration détaillé](CONFIGURATION.md).

## Tests et vérification

L'application inclut des scripts de test pour vérifier que les fonctionnalités critiques sont correctement configurées :

### Test de Cloudflare Turnstile

Pour vérifier si la protection anti-robot Turnstile est correctement configurée :

1. Configurez Turnstile dans `includes/config.php` selon les instructions du [guide de configuration](CONFIGURATION.md#configuration-de-cloudflare-turnstile)
2. Accédez à `http://votre-site/test_turnstile.php`
3. Suivez les instructions à l'écran pour tester la fonctionnalité

### Test de la configuration SMTP

Pour vérifier si l'envoi d'emails est correctement configuré :

1. Configurez les paramètres SMTP dans `includes/config.php` selon les instructions du [guide de configuration](CONFIGURATION.md#configuration-smtp-pour-lenvoi-demails)
2. Modifiez `test_email.php` pour y indiquer votre adresse email
3. Accédez à `http://votre-site/test_email.php`
4. Le script tentera d'envoyer un email de test et affichera le résultat

> **Note de sécurité** : Une fois les tests effectués, il est recommandé de supprimer ou de restreindre l'accès à ces fichiers de test.

## Roadmap

### Phase 1 - Q1 2025
- [x] Système d'authentification de base
- [x] Gestion des commandes basique
- [x] Interface d'administration
- [x] Support multilingue initial

### Phase 2 - Q2 2025
- [ ] Amélioration de l'interface utilisateur
- [ ] Système de notifications
- [ ] Intégration de paiements sécurisés
- [ ] Système de recherche avancé

### Phase 3 - Q3 2025
- [ ] Application mobile
- [ ] API publique
- [ ] Système de recommandations
- [ ] Analytics et tableaux de bord

## Contribution
Les contributions sont les bienvenues ! Voici comment vous pouvez contribuer :

1. Forkez le projet
2. Créez votre branche de fonctionnalité
3. Committez vos changements
4. Poussez vers la branche
5. Ouvrez une Pull Request

---

[🔙 Retour à la sélection des langues](../README.md) 