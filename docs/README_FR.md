# Documentation du Projet Groupon

## 📝 Table des matières
- [Vue d'ensemble](#vue-densemble)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Configuration](#configuration)
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
- MySQL
- JavaScript
- HTML5/CSS3
- API REST
- Bootstrap

## Installation
1. Clonez le dépôt
2. Configurez votre serveur web (Apache/Nginx)
3. Importez la base de données
4. Configurez les paramètres de connexion dans `includes/config.php`
5. Lancez l'application

## Configuration
### Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)

### Configuration de la base de données
1. Créez une base de données MySQL
2. Importez le schéma depuis `data/schema.sql`
3. Configurez les accès dans le fichier de configuration

## Roadmap

### Phase 1 - Q1 2025
- [x] Système d'authentification de base
- [x] Gestion des commandes basique
- [x] Interface d'administration
- [x] Support multilingue initial

- fix mode sombre : tableau en blanc
- possibilité d'annuler /reporter  Date limite et/ou Récupération:
- verifier fonctionnement mail
- integrer turnstile !
- possibilité de supprimer/produit et/ou variations
- possibilité de dupliquer/exporter en json une commande
- possibilité de faire un export pdf de la commande pour la distribution (qui a commandé quoi et doit quoi)
- possibilité de faire une description de la commande
- possibilité de mettre la commande en public sur les dashboard de tout les inscrits
- traduire la paga d'accueil


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