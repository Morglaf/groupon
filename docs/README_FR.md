# Groupon - Application de Commandes Groupées

## 📝 Table des matières
- [Vue d'ensemble](#vue-densemble)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Configuration](#configuration)
- [Base de données MySQL](#base-de-données-mysql)
- [Tests et vérification](#tests-et-vérification)
- [Roadmap](#roadmap)
- [Contribution](#contribution)

## Vue d'ensemble
**Groupon** est une application web moderne pour organiser des commandes groupées entre amis, collègues ou voisins. Optimisez vos frais de livraison en regroupant vos achats ! L'application utilise MySQL comme base de données principale pour une performance et une fiabilité optimales.

## Fonctionnalités
- 🔐 Système d'authentification complet (connexion, inscription, déconnexion)
- 👤 Gestion des profils utilisateurs
- 📦 Création et gestion des commandes groupées
- 👥 Gestion des participants via des liens uniques
- 💰 Répartition automatique des frais de port
- 📊 Tableau de bord pour suivre les commandes
- 🛠️ Interface d'administration
- 🌍 Support multilingue (FR, EN, ES, DE)
- 🎨 Thème clair/sombre
- 📱 Interface responsive
- 🔒 Sécurité avec Cloudflare Turnstile

## Technologies utilisées
- **PHP 8.0+** - Backend principal
- **MySQL** - Base de données relationnelle
- **JavaScript** - Interactivité frontend
- **HTML5/CSS3** - Structure et style
- **Bootstrap 5** - Framework CSS
- **PDO** - Accès sécurisé à la base de données
- **Cloudflare Turnstile** - Protection anti-robot

## Installation
1. **Téléchargez** le projet
2. **Configurez** votre serveur web avec PHP 8.0+ et MySQL
3. **Accédez** à `install.php` pour l'installation automatique
4. **Configurez** votre base de données MySQL
5. **Commencez** à créer vos commandes !

## Configuration
### Prérequis
- **PHP 8.0 ou supérieur**
- **MySQL 5.7 ou supérieur**
- **Serveur web** (Apache/Nginx)
- **Extensions PHP** : PDO, PDO_MySQL, mbstring, openssl

### Installation automatique
L'application inclut un script d'installation automatique similaire à WordPress :

1. Accédez à `http://votre-site/install.php`
2. Configurez vos paramètres de base de données MySQL
3. L'installation créera automatiquement les tables nécessaires
4. Créez votre compte administrateur
5. Supprimez `install.php` après installation

Pour plus de détails sur la configuration, consultez le [guide de configuration détaillé](CONFIGURATION.md).

## Base de données MySQL
L'application utilise MySQL comme base de données principale avec :

### Structure des tables
- **`groupon_users`** - Utilisateurs et authentification
- **`groupon_commandes`** - Commandes groupées
- **`groupon_participants`** - Participants aux commandes

### Avantages
- **Performance** - Requêtes optimisées et indexation
- **Fiabilité** - Transactions et intégrité des données
- **Sauvegarde** - Outils standard de sauvegarde MySQL
- **Évolutivité** - Support de milliers d'utilisateurs
- **Sécurité** - Requêtes préparées et protection contre les injections SQL

## Tests et vérification

L'application inclut des scripts de test pour vérifier que les fonctionnalités critiques sont correctement configurées :

### Test de la base de données MySQL

Pour vérifier si la connexion à la base de données fonctionne :

1. Accédez à votre tableau de bord après connexion
2. Vérifiez que les commandes s'affichent correctement
3. Testez la création d'une nouvelle commande
4. Vérifiez que les participants peuvent rejoindre les commandes

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

### Phase 1 - Q1 2025 ✅
- [x] Système d'authentification de base
- [x] Gestion des commandes groupées
- [x] Interface d'administration
- [x] Support multilingue initial
- [x] Migration vers MySQL
- [x] Installation automatique

### Phase 2 - Q2 2025
- [ ] Amélioration de l'interface utilisateur
- [ ] Système de notifications par email
- [ ] Intégration de paiements sécurisés
- [ ] Système de recherche avancé
- [ ] Export des commandes en PDF

### Phase 3 - Q3 2025
- [ ] Application mobile
- [ ] API publique REST
- [ ] Système de recommandations
- [ ] Analytics et tableaux de bord avancés
- [ ] Système de sauvegarde automatique

## Contribution
Les contributions sont les bienvenues ! Voici comment vous pouvez contribuer :

1. Forkez le projet
2. Créez votre branche de fonctionnalité
3. Committez vos changements
4. Poussez vers la branche
5. Ouvrez une Pull Request

---

[🔙 Retour à la sélection des langues](../README.md) 