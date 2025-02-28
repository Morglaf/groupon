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

- `data/users` : Données des utilisateurs (fichiers JSON)
- `data/commandes` : Données des commandes (fichiers JSON)
- `data/exports` : Fichiers exportés (PDF, JSON)

Vous pouvez modifier ces chemins dans le fichier de configuration si nécessaire.

**Note importante** : L'application n'utilise pas de base de données SQL. Toutes les données sont stockées dans des fichiers JSON, ce qui simplifie l'installation et la configuration.

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

---

# Application Configuration (English)

This document explains how to configure the application for different environments and features.

## Basic Configuration

The application configuration is located in the `includes/config.php` file. You need to modify this file to adapt the application to your environment.

### Main Parameters

```php
// Application name
define('APP_NAME', 'Groupon');

// Base URL of the application (without trailing slash)
define('APP_URL', 'http://localhost');

// Timezone
date_default_timezone_set('Europe/Paris');
```

- `APP_NAME`: Application name displayed in the interface
- `APP_URL`: Base URL of the application, used to generate absolute links
- `date_default_timezone_set`: Timezone used for dates and times

## Cloudflare Turnstile Configuration

[Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) is an alternative to reCAPTCHA that protects forms against bots. To activate it:

1. Create an account on [Cloudflare](https://www.cloudflare.com/) if you don't already have one
2. Access the Turnstile section in your Cloudflare dashboard
3. Create a new site and choose "Invisible" or "Managed" mode
4. Note the site key and secret key
5. Modify the `includes/config.php` file:

```php
// Cloudflare Turnstile configuration
define('USE_TURNSTILE', true); // Enable Turnstile
define('TURNSTILE_SITE_KEY', 'your-site-key'); // Your Turnstile site key
define('TURNSTILE_SECRET_KEY', 'your-secret-key'); // Your Turnstile secret key
```

Once configured, Turnstile will be automatically added to the registration and login forms.

## SMTP Configuration for Sending Emails

The application uses SMTP to send emails (notifications, reminders, etc.). You need to configure an SMTP server for this feature to work.

### Configuration with a Local SMTP Server

If you have a local SMTP server (like Postfix), you can use this configuration:

```php
// SMTP configuration for sending emails
define('SMTP_HOST', 'localhost'); // SMTP server
define('SMTP_PORT', 25); // SMTP port
define('SMTP_SECURE', ''); // No security
define('SMTP_AUTH', false); // No authentication
define('SMTP_USERNAME', ''); // No username
define('SMTP_PASSWORD', ''); // No password
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com'); // Sender email
define('SMTP_FROM_NAME', 'Groupon App'); // Sender name
```

### Configuration with Gmail

To use Gmail as an SMTP server:

```php
// SMTP configuration for sending emails
define('SMTP_HOST', 'smtp.gmail.com'); // Gmail SMTP server
define('SMTP_PORT', 587); // SMTP port
define('SMTP_SECURE', 'tls'); // TLS security
define('SMTP_AUTH', true); // Authentication required
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your Gmail address
define('SMTP_PASSWORD', 'your-app-password'); // App password
define('SMTP_FROM_EMAIL', 'your-email@gmail.com'); // Sender email
define('SMTP_FROM_NAME', 'Groupon App'); // Sender name
```

**Important note**: For Gmail, you must use an "app password" and not your main password. To create an app password:
1. Enable two-factor authentication on your Google account
2. Access [app password management](https://myaccount.google.com/apppasswords)
3. Create a new app password for "Other (custom name)"
4. Use this password in the SMTP configuration

### Configuration with Other Providers

For other SMTP service providers (OVH, Amazon SES, SendGrid, etc.), consult their documentation to obtain the appropriate SMTP parameters.

## Data Folders

By default, the application stores its data in the `data` folder at the root of the project. This folder contains:

- `data/users`: User data (JSON files)
- `data/commandes`: Order data (JSON files)
- `data/exports`: Exported files (PDF, JSON)

You can modify these paths in the configuration file if necessary.

**Important note**: The application does not use an SQL database. All data is stored in JSON files, which simplifies installation and configuration.

## Available Languages

The application supports multiple languages. Available languages are defined in the `includes/lang.php` file:

```php
$available_languages = [
    'fr' => 'Français',
    'en' => 'English',
    'de' => 'Deutsch',
    'es' => 'Español'
];
```

Translation files are located in the `langs/` folder. To add a new language, create a corresponding file (for example `langs/it.php` for Italian) and add it to the list of available languages.

---

# Configuración de la Aplicación (Español)

Este documento explica cómo configurar la aplicación para diferentes entornos y funcionalidades.

## Configuración Básica

La configuración de la aplicación se encuentra en el archivo `includes/config.php`. Debe modificar este archivo para adaptar la aplicación a su entorno.

### Parámetros Principales

```php
// Nombre de la aplicación
define('APP_NAME', 'Groupon');

// URL base de la aplicación (sin barra final)
define('APP_URL', 'http://localhost');

// Zona horaria
date_default_timezone_set('Europe/Paris');
```

- `APP_NAME`: Nombre de la aplicación mostrado en la interfaz
- `APP_URL`: URL base de la aplicación, utilizada para generar enlaces absolutos
- `date_default_timezone_set`: Zona horaria utilizada para fechas y horas

## Configuración de Cloudflare Turnstile

[Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) es una alternativa a reCAPTCHA que permite proteger los formularios contra robots. Para activarlo:

1. Cree una cuenta en [Cloudflare](https://www.cloudflare.com/) si aún no tiene una
2. Acceda a la sección Turnstile en su panel de control de Cloudflare
3. Cree un nuevo sitio y elija el modo "Invisible" o "Managed"
4. Anote la clave del sitio y la clave secreta
5. Modifique el archivo `includes/config.php`:

```php
// Configuración de Cloudflare Turnstile
define('USE_TURNSTILE', true); // Activar Turnstile
define('TURNSTILE_SITE_KEY', 'su-clave-de-sitio'); // Su clave de sitio Turnstile
define('TURNSTILE_SECRET_KEY', 'su-clave-secreta'); // Su clave secreta Turnstile
```

Una vez configurado, Turnstile se añadirá automáticamente a los formularios de registro e inicio de sesión.

## Configuración SMTP para el Envío de Correos Electrónicos

La aplicación utiliza SMTP para enviar correos electrónicos (notificaciones, recordatorios, etc.). Debe configurar un servidor SMTP para que esta funcionalidad sea operativa.

### Configuración con un Servidor SMTP Local

Si dispone de un servidor SMTP local (como Postfix), puede utilizar esta configuración:

```php
// Configuración SMTP para el envío de correos electrónicos
define('SMTP_HOST', 'localhost'); // Servidor SMTP
define('SMTP_PORT', 25); // Puerto SMTP
define('SMTP_SECURE', ''); // Sin seguridad
define('SMTP_AUTH', false); // Sin autenticación
define('SMTP_USERNAME', ''); // Sin nombre de usuario
define('SMTP_PASSWORD', ''); // Sin contraseña
define('SMTP_FROM_EMAIL', 'noreply@sudominio.com'); // Correo electrónico del remitente
define('SMTP_FROM_NAME', 'Groupon App'); // Nombre del remitente
```

### Configuración con Gmail

Para utilizar Gmail como servidor SMTP:

```php
// Configuración SMTP para el envío de correos electrónicos
define('SMTP_HOST', 'smtp.gmail.com'); // Servidor SMTP de Gmail
define('SMTP_PORT', 587); // Puerto SMTP
define('SMTP_SECURE', 'tls'); // Seguridad TLS
define('SMTP_AUTH', true); // Autenticación requerida
define('SMTP_USERNAME', 'su-correo@gmail.com'); // Su dirección de Gmail
define('SMTP_PASSWORD', 'su-contraseña-de-aplicación'); // Contraseña de aplicación
define('SMTP_FROM_EMAIL', 'su-correo@gmail.com'); // Correo electrónico del remitente
define('SMTP_FROM_NAME', 'Groupon App'); // Nombre del remitente
```

**Nota importante**: Para Gmail, debe utilizar una "contraseña de aplicación" y no su contraseña principal. Para crear una contraseña de aplicación:
1. Active la autenticación de dos factores en su cuenta de Google
2. Acceda a [la gestión de contraseñas de aplicación](https://myaccount.google.com/apppasswords)
3. Cree una nueva contraseña de aplicación para "Otro (nombre personalizado)"
4. Utilice esta contraseña en la configuración SMTP

### Configuración con Otros Proveedores

Para otros proveedores de servicios SMTP (OVH, Amazon SES, SendGrid, etc.), consulte su documentación para obtener los parámetros SMTP apropiados.

## Carpetas de Datos

Por defecto, la aplicación almacena sus datos en la carpeta `data` en la raíz del proyecto. Esta carpeta contiene:

- `data/users`: Datos de usuarios (archivos JSON)
- `data/commandes`: Datos de pedidos (archivos JSON)
- `data/exports`: Archivos exportados (PDF, JSON)

Puede modificar estas rutas en el archivo de configuración si es necesario.

**Nota importante**: La aplicación no utiliza una base de datos SQL. Todos los datos se almacenan en archivos JSON, lo que simplifica la instalación y configuración.

## Idiomas Disponibles

La aplicación admite varios idiomas. Los idiomas disponibles se definen en el archivo `includes/lang.php`:

```php
$available_languages = [
    'fr' => 'Français',
    'en' => 'English',
    'de' => 'Deutsch',
    'es' => 'Español'
];
```

Los archivos de traducción se encuentran en la carpeta `langs/`. Para añadir un nuevo idioma, cree un archivo correspondiente (por ejemplo `langs/it.php` para italiano) y añádalo a la lista de idiomas disponibles.

---

# Anwendungskonfiguration (Deutsch)

Dieses Dokument erklärt, wie Sie die Anwendung für verschiedene Umgebungen und Funktionen konfigurieren können.

## Grundkonfiguration

Die Anwendungskonfiguration befindet sich in der Datei `includes/config.php`. Sie müssen diese Datei ändern, um die Anwendung an Ihre Umgebung anzupassen.

### Hauptparameter

```php
// Anwendungsname
define('APP_NAME', 'Groupon');

// Basis-URL der Anwendung (ohne abschließenden Schrägstrich)
define('APP_URL', 'http://localhost');

// Zeitzone
date_default_timezone_set('Europe/Paris');
```

- `APP_NAME`: Anwendungsname, der in der Benutzeroberfläche angezeigt wird
- `APP_URL`: Basis-URL der Anwendung, die zur Generierung absoluter Links verwendet wird
- `date_default_timezone_set`: Zeitzone, die für Datums- und Zeitangaben verwendet wird

## Cloudflare Turnstile Konfiguration

[Cloudflare Turnstile](https://www.cloudflare.com/products/turnstile/) ist eine Alternative zu reCAPTCHA, die Formulare vor Bots schützt. Um es zu aktivieren:

1. Erstellen Sie ein Konto bei [Cloudflare](https://www.cloudflare.com/), falls Sie noch keines haben
2. Greifen Sie auf den Turnstile-Bereich in Ihrem Cloudflare-Dashboard zu
3. Erstellen Sie eine neue Website und wählen Sie den Modus "Invisible" oder "Managed"
4. Notieren Sie sich den Site-Schlüssel und den geheimen Schlüssel
5. Ändern Sie die Datei `includes/config.php`:

```php
// Cloudflare Turnstile Konfiguration
define('USE_TURNSTILE', true); // Turnstile aktivieren
define('TURNSTILE_SITE_KEY', 'ihr-site-schlüssel'); // Ihr Turnstile Site-Schlüssel
define('TURNSTILE_SECRET_KEY', 'ihr-geheimer-schlüssel'); // Ihr Turnstile geheimer Schlüssel
```

Nach der Konfiguration wird Turnstile automatisch zu den Registrierungs- und Anmeldeformularen hinzugefügt.

## SMTP-Konfiguration für den E-Mail-Versand

Die Anwendung verwendet SMTP zum Versenden von E-Mails (Benachrichtigungen, Erinnerungen usw.). Sie müssen einen SMTP-Server konfigurieren, damit diese Funktion betriebsbereit ist.

### Konfiguration mit einem lokalen SMTP-Server

Wenn Sie über einen lokalen SMTP-Server (wie Postfix) verfügen, können Sie diese Konfiguration verwenden:

```php
// SMTP-Konfiguration für den E-Mail-Versand
define('SMTP_HOST', 'localhost'); // SMTP-Server
define('SMTP_PORT', 25); // SMTP-Port
define('SMTP_SECURE', ''); // Keine Sicherheit
define('SMTP_AUTH', false); // Keine Authentifizierung
define('SMTP_USERNAME', ''); // Kein Benutzername
define('SMTP_PASSWORD', ''); // Kein Passwort
define('SMTP_FROM_EMAIL', 'noreply@ihredomain.com'); // Absender-E-Mail
define('SMTP_FROM_NAME', 'Groupon App'); // Absendername
```

### Konfiguration mit Gmail

Um Gmail als SMTP-Server zu verwenden:

```php
// SMTP-Konfiguration für den E-Mail-Versand
define('SMTP_HOST', 'smtp.gmail.com'); // Gmail SMTP-Server
define('SMTP_PORT', 587); // SMTP-Port
define('SMTP_SECURE', 'tls'); // TLS-Sicherheit
define('SMTP_AUTH', true); // Authentifizierung erforderlich
define('SMTP_USERNAME', 'ihre-email@gmail.com'); // Ihre Gmail-Adresse
define('SMTP_PASSWORD', 'ihr-app-passwort'); // App-Passwort
define('SMTP_FROM_EMAIL', 'ihre-email@gmail.com'); // Absender-E-Mail
define('SMTP_FROM_NAME', 'Groupon App'); // Absendername
```

**Wichtiger Hinweis**: Für Gmail müssen Sie ein "App-Passwort" und nicht Ihr Hauptpasswort verwenden. Um ein App-Passwort zu erstellen:
1. Aktivieren Sie die Zwei-Faktor-Authentifizierung für Ihr Google-Konto
2. Greifen Sie auf die [App-Passwort-Verwaltung](https://myaccount.google.com/apppasswords) zu
3. Erstellen Sie ein neues App-Passwort für "Andere (benutzerdefinierter Name)"
4. Verwenden Sie dieses Passwort in der SMTP-Konfiguration

### Konfiguration mit anderen Anbietern

Für andere SMTP-Dienstanbieter (OVH, Amazon SES, SendGrid usw.) konsultieren Sie deren Dokumentation, um die entsprechenden SMTP-Parameter zu erhalten.

## Datenordner

Standardmäßig speichert die Anwendung ihre Daten im Ordner `data` im Stammverzeichnis des Projekts. Dieser Ordner enthält:

- `data/users`: Benutzerdaten (JSON-Dateien)
- `data/commandes`: Bestelldaten (JSON-Dateien)
- `data/exports`: Exportierte Dateien (PDF, JSON)

Sie können diese Pfade in der Konfigurationsdatei ändern, falls erforderlich.

**Wichtiger Hinweis**: Die Anwendung verwendet keine SQL-Datenbank. Alle Daten werden in JSON-Dateien gespeichert, was die Installation und Konfiguration vereinfacht.

## Verfügbare Sprachen

Die Anwendung unterstützt mehrere Sprachen. Verfügbare Sprachen werden in der Datei `includes/lang.php` definiert:

```php
$available_languages = [
    'fr' => 'Français',
    'en' => 'English',
    'de' => 'Deutsch',
    'es' => 'Español'
];
```

Übersetzungsdateien befinden sich im Ordner `langs/`. Um eine neue Sprache hinzuzufügen, erstellen Sie eine entsprechende Datei (z.B. `langs/it.php` für Italienisch) und fügen Sie sie zur Liste der verfügbaren Sprachen hinzu. 