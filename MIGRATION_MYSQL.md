# Migration vers MySQL

Cette branche contient la migration complète du système de stockage JSON vers MySQL.

## 🚀 Installation

### 1. Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)

### 2. Installation automatique
1. Clonez cette branche
2. Accédez à `http://votre-site/install.php`
3. Suivez l'assistant de configuration
4. Supprimez `install.php` après installation

### 3. Configuration manuelle
Si vous préférez configurer manuellement :

1. Créez une base de données MySQL
2. Copiez `includes/database.php.example` vers `includes/database.php`
3. Modifiez les paramètres de connexion
4. Exécutez le script SQL dans `database/schema.sql`

## 🏗️ Structure de la base de données

### Tables principales
- `groupon_users` - Utilisateurs
- `groupon_commandes` - Commandes
- `groupon_produits` - Produits des commandes
- `groupon_variations` - Variations des produits
- `groupon_participants` - Participants aux commandes
- `groupon_articles_commandes` - Articles commandés
- `groupon_paliers_frais` - Paliers de frais de port

### Avantages de MySQL vs JSON
- ✅ **Performance** : Requêtes optimisées avec index
- ✅ **Sécurité** : Protection contre les injections SQL
- ✅ **Scalabilité** : Support de milliers d'utilisateurs
- ✅ **Intégrité** : Contraintes et relations
- ✅ **Backup** : Sauvegarde facile
- ✅ **Concurrence** : Gestion des accès simultanés

## 🔧 Configuration

### Paramètres de connexion
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'groupon_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PREFIX', 'groupon_');
```

### Préfixe des tables
Le préfixe permet d'installer plusieurs instances sur la même base :
- `groupon_` (par défaut)
- `groupon_dev_`
- `groupon_test_`

## 📊 Migration des données

### Script de migration JSON → MySQL
Un script de migration est disponible pour transférer les données existantes :

```bash
php migrate_json_to_mysql.php
```

### Sauvegarde
Avant migration, sauvegardez vos données JSON :
```bash
cp -r data/ data_backup/
```

## 🚨 Changements importants

### Abandon du système JSON
- ❌ Plus de fichiers JSON dans `data/`
- ❌ Plus de verrous de fichiers
- ❌ Plus de `readJsonFile()` / `writeJsonFile()`

### Nouvelles fonctions MySQL
- ✅ `getUserData()` - Optimisé avec requêtes préparées
- ✅ `saveUserData()` - Transactions automatiques
- ✅ `getCommandeData()` - Jointures optimisées
- ✅ `saveCommandeData()` - Intégrité référentielle

### Performance
- **Avant** : Lecture/écriture de fichiers JSON
- **Après** : Requêtes SQL optimisées avec index

## 🔍 Tests et validation

### Tests de performance
```bash
# Test de charge
php test_performance.php

# Test de concurrence
php test_concurrency.php
```

### Validation des données
```bash
# Vérification de l'intégrité
php validate_data.php
```

## 🛠️ Développement

### Structure des requêtes
Toutes les requêtes utilisent des requêtes préparées :
```php
$stmt = $pdo->prepare("SELECT * FROM " . DB_PREFIX . "users WHERE id = ?");
$stmt->execute([$user_id]);
```

### Gestion des erreurs
```php
try {
    // Opération MySQL
} catch (PDOException $e) {
    error_log('Erreur: ' . $e->getMessage());
    return false;
}
```

### Transactions
```php
$pdo->beginTransaction();
try {
    // Opérations multiples
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

## 📈 Monitoring

### Logs MySQL
- Erreurs dans `error_log`
- Requêtes lentes dans `slow_query_log`
- Connexions dans `general_log`

### Métriques importantes
- Temps de réponse des requêtes
- Nombre de connexions simultanées
- Utilisation de la mémoire

## 🔒 Sécurité

### Protection SQL Injection
- Requêtes préparées obligatoires
- Validation des entrées
- Échappement automatique

### Contraintes de base
- Clés étrangères activées
- Contraintes d'unicité
- Validation des types

## 🚀 Déploiement

### Production
1. Configurez MySQL avec les bonnes limites
2. Activez les logs de requêtes lentes
3. Configurez les sauvegardes automatiques
4. Testez la charge avant mise en production

### Variables d'environnement
```bash
export DB_HOST=localhost
export DB_NAME=groupon_prod
export DB_USER=groupon_user
export DB_PASS=secure_password
```

## 📚 Documentation

- [Guide d'installation](docs/INSTALLATION.md)
- [Configuration avancée](docs/CONFIGURATION.md)
- [API de base de données](docs/DATABASE_API.md)
- [Optimisation des performances](docs/PERFORMANCE.md)

## 🐛 Dépannage

### Erreurs courantes
1. **Connexion refusée** : Vérifiez les paramètres MySQL
2. **Tables manquantes** : Relancez l'installateur
3. **Permissions insuffisantes** : Vérifiez les droits MySQL
4. **Charset incorrect** : Utilisez utf8mb4

### Support
- Issues GitHub
- Documentation complète
- Tests automatisés

---

**Note** : Cette migration améliore significativement les performances et la scalabilité de l'application tout en maintenant la compatibilité avec l'interface utilisateur existante.
