# Configuration de l'application

Ce document explique comment configurer l'application pour différents environnements et fonctionnalités.

## Configuration de base

La configuration de l'application se trouve dans le fichier `includes/config.php`. Vous devez modifier ce fichier pour adapter l'application à votre environnement.

### Paramètres principaux

```php
// Nom de l'application
define('APP_NAME', 'Groupon');

// URL de base de l'application (sans slash final)
define('APP_URL', 'http://localhost');

// Fuseau horaire
date_default_timezone_set('Europe/Paris');
```

- `APP_NAME` : Nom de l'application affiché dans l'interface
- `APP_URL` : URL de base de l'application, utilisée pour générer les liens absolus
- `date_default_timezone_set` : Fuseau horaire utilisé pour les dates et heures

## Configuration de Cloudflare Turnstile

[Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) est une alternative à reCAPTCHA qui permet de protéger les formulaires contre les robots. Pour l'activer :

1. Créez un compte sur [Cloudflare](https://www.cloudflare.com/) si vous n'en avez pas déjà un
2. Accédez à la section Turnstile dans votre tableau de bord Cloudflare
3. Créez un nouveau site et choisissez le mode "Invisible" ou "Managed"
4. Notez la clé de site et la clé secrète
5. Modifiez le fichier `includes/config.php` :

```php
// Configuration de Cloudflare Turnstile
define('USE_TURNSTILE', true); // Activer Turnstile
define('TURNSTILE_SITE_KEY', 'votre-cle-de-site'); // Votre clé de site Turnstile
define('TURNSTILE_SECRET_KEY', 'votre-cle-secrete'); // Votre clé secrète Turnstile
```

Une fois configuré, Turnstile sera automatiquement ajouté aux formulaires d'inscription et de connexion.

## Configuration SMTP pour l'envoi d'emails

L'application utilise SMTP pour envoyer des emails (notifications, rappels, etc.). Vous devez configurer un serveur SMTP pour que cette fonctionnalité soit opérationnelle.

### Configuration avec un serveur SMTP local

Si vous disposez d'un serveur SMTP local (comme Postfix), vous pouvez utiliser cette configuration :

```php
// Configuration SMTP pour l'envoi d'emails
define('SMTP_HOST', 'localhost'); // Serveur SMTP
define('SMTP_PORT', 25); // Port SMTP
define('SMTP_SECURE', ''); // Pas de sécurité
define('SMTP_AUTH', false); // Pas d'authentification
define('SMTP_USERNAME', ''); // Pas de nom d'utilisateur
define('SMTP_PASSWORD', ''); // Pas de mot de passe
define('SMTP_FROM_EMAIL', 'noreply@votredomaine.com'); // Email expéditeur
define('SMTP_FROM_NAME', 'Groupon App'); // Nom expéditeur
```

### Configuration avec Gmail

Pour utiliser Gmail comme serveur SMTP :

```php
// Configuration SMTP pour l'envoi d'emails
define('SMTP_HOST', 'smtp.gmail.com'); // Serveur SMTP de Gmail
define('SMTP_PORT', 587); // Port SMTP
define('SMTP_SECURE', 'tls'); // Sécurité TLS
define('SMTP_AUTH', true); // Authentification requise
define('SMTP_USERNAME', 'votre-email@gmail.com'); // Votre adresse Gmail
define('SMTP_PASSWORD', 'votre-mot-de-passe-d-application'); // Mot de passe d'application
define('SMTP_FROM_EMAIL', 'votre-email@gmail.com'); // Email expéditeur
define('SMTP_FROM_NAME', 'Groupon App'); // Nom expéditeur
```

**Note importante** : Pour Gmail, vous devez utiliser un "mot de passe d'application" et non votre mot de passe principal. Pour créer un mot de passe d'application :
1. Activez l'authentification à deux facteurs sur votre compte Google
2. Accédez à [la gestion des mots de passe d'application](https://myaccount.google.com/apppasswords)
3. Créez un nouveau mot de passe d'application pour "Autre (nom personnalisé)"
4. Utilisez ce mot de passe dans la configuration SMTP

### Configuration avec d'autres fournisseurs

Pour d'autres fournisseurs de services SMTP (OVH, Amazon SES, SendGrid, etc.), consultez leur documentation pour obtenir les paramètres SMTP appropriés.

## Dossiers de données

Par défaut, l'application stocke ses données dans le dossier `data` à la racine du projet. Ce dossier contient :

- `data/users` : Données des utilisateurs
- `data/commandes` : Données des commandes
- `data/exports` : Fichiers exportés (PDF, JSON)

Vous pouvez modifier ces chemins dans le fichier de configuration si nécessaire.

## Langues disponibles

L'application prend en charge plusieurs langues. Les langues disponibles sont définies dans le fichier `includes/lang.php` :

```php
$available_languages = [
    'fr' => 'Français',
    'en' => 'English',
    'de' => 'Deutsch',
    'es' => 'Español'
];
```

Les fichiers de traduction se trouvent dans le dossier `langs/`. Pour ajouter une nouvelle langue, créez un fichier correspondant (par exemple `langs/it.php` pour l'italien) et ajoutez-le à la liste des langues disponibles. 