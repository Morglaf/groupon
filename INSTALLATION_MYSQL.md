# Installation MySQL - Groupon

Guide d'installation complète pour la version MySQL de Groupon.

## 🚀 Installation rapide

### 1. Prérequis
- **PHP** 7.4 ou supérieur
- **MySQL** 5.7 ou supérieur (ou MariaDB 10.3+)
- **Serveur web** (Apache/Nginx)
- **Extensions PHP** : PDO, PDO_MySQL, mbstring

### 2. Installation automatique (recommandée)

1. **Téléchargez** cette branche
2. **Uploadez** les fichiers sur votre serveur
3. **Accédez** à `http://votre-site/install.php`
4. **Suivez** l'assistant de configuration
5. **Supprimez** `install.php` après installation

### 3. Installation manuelle

#### Étape 1 : Configuration de la base de données
```bash
# Créer la base de données
mysql -u root -p
CREATE DATABASE groupon_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON groupon_db.* TO 'groupon_user'@'localhost' IDENTIFIED BY 'mot_de_passe_securise';
FLUSH PRIVILEGES;
EXIT;
```

#### Étape 2 : Configuration de l'application
```bash
# Copier le fichier de configuration
cp includes/database.php.example includes/database.php

# Modifier les paramètres
nano includes/database.php
```

#### Étape 3 : Création des tables
```bash
# Importer le schéma
mysql -u groupon_user -p groupon_db < database/schema.sql
```

## 🔧 Configuration avancée

### Variables d'environnement
```bash
# .env (optionnel)
DB_HOST=localhost
DB_NAME=groupon_db
DB_USER=groupon_user
DB_PASS=mot_de_passe_securise
DB_PREFIX=groupon_
```

### Configuration Apache (.htaccess)
Le fichier `.htaccess` est inclus et configure :
- ✅ Protection des fichiers sensibles
- ✅ Compression GZIP
- ✅ Cache des assets
- ✅ Headers de sécurité
- ✅ Protection contre les attaques

### Configuration Nginx
```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/groupon;
    index index.php;

    # Sécurité
    location ~ /(includes|config|database|functions).*\.php$ {
        deny all;
    }

    # PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }

    # Cache
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }
}
```

## 📊 Migration des données existantes

### Depuis JSON
Si vous avez des données JSON existantes :

1. **Sauvegardez** vos données :
```bash
cp -r data/ data_backup_$(date +%Y%m%d)/
```

2. **Lancez** la migration :
```bash
php migrate_json_to_mysql.php
```

3. **Validez** la migration
4. **Supprimez** les données JSON

### Depuis une autre base MySQL
```sql
-- Exporter depuis l'ancienne base
mysqldump -u user -p old_database > backup.sql

-- Importer dans la nouvelle base
mysql -u groupon_user -p groupon_db < backup.sql
```

## 🧪 Tests et validation

### Test automatique
```bash
# Lancer les tests
php test_mysql_migration.php
```

### Tests manuels
1. **Créer** un compte utilisateur
2. **Créer** une commande
3. **Ajouter** des produits
4. **Inviter** des participants
5. **Tester** les fonctionnalités

### Validation des performances
```bash
# Test de charge (optionnel)
php test_performance.php
```

## 🔒 Sécurité

### Configuration MySQL sécurisée
```sql
-- Utilisateur dédié avec permissions limitées
CREATE USER 'groupon_user'@'localhost' IDENTIFIED BY 'mot_de_passe_complexe';
GRANT SELECT, INSERT, UPDATE, DELETE ON groupon_db.* TO 'groupon_user'@'localhost';
FLUSH PRIVILEGES;
```

### Configuration PHP sécurisée
```ini
; php.ini
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
```

### Permissions de fichiers
```bash
# Permissions sécurisées
chmod 755 /var/www/groupon
chmod 644 /var/www/groupon/*.php
chmod 600 /var/www/groupon/includes/database.php
chown -R www-data:www-data /var/www/groupon
```

## 📈 Optimisation des performances

### Configuration MySQL
```sql
-- my.cnf
[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
query_cache_size = 32M
query_cache_type = 1
```

### Index optimisés
Les index suivants sont créés automatiquement :
- `idx_admin_id` sur `commandes`
- `idx_date_limite` sur `commandes`
- `idx_public` sur `commandes`
- `idx_statut_paiement` sur `participants`

### Cache PHP (optionnel)
```php
// OpCache recommandé
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

## 🚨 Dépannage

### Erreurs courantes

#### "Connexion refusée"
```bash
# Vérifier MySQL
systemctl status mysql
mysql -u root -p -e "SHOW DATABASES;"
```

#### "Tables manquantes"
```bash
# Recréer les tables
mysql -u groupon_user -p groupon_db < database/schema.sql
```

#### "Permissions insuffisantes"
```sql
-- Vérifier les permissions
SHOW GRANTS FOR 'groupon_user'@'localhost';
```

#### "Charset incorrect"
```sql
-- Vérifier le charset
SHOW VARIABLES LIKE 'character_set%';
ALTER DATABASE groupon_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Logs utiles
```bash
# Logs MySQL
tail -f /var/log/mysql/error.log

# Logs PHP
tail -f /var/log/php_errors.log

# Logs Apache
tail -f /var/log/apache2/error.log
```

## 📚 Ressources supplémentaires

- [Documentation MySQL](https://dev.mysql.com/doc/)
- [Guide PHP PDO](https://www.php.net/manual/en/book.pdo.php)
- [Sécurité PHP](https://www.php.net/manual/en/security.php)
- [Optimisation MySQL](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)

## 🆘 Support

En cas de problème :
1. Vérifiez les logs d'erreur
2. Consultez la documentation
3. Testez avec les scripts fournis
4. Ouvrez une issue sur GitHub

---

**Note** : Cette installation remplace complètement le système JSON par MySQL pour de meilleures performances et scalabilité.
