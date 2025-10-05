-- Schéma de base de données MySQL pour Groupon
-- Version: 1.0
-- Compatible: MySQL 5.7+

-- Créer la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `groupon_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `groupon_db`;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS `groupon_users` (
    `id` varchar(32) NOT NULL,
    `email` varchar(255) NOT NULL,
    `nom` varchar(100) NOT NULL,
    `prenom` varchar(100) NOT NULL,
    `telephone` varchar(20) DEFAULT NULL,
    `password_hash` varchar(255) NOT NULL,
    `date_inscription` datetime NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_date_inscription` (`date_inscription`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des commandes
CREATE TABLE IF NOT EXISTS `groupon_commandes` (
    `id` varchar(32) NOT NULL,
    `titre` varchar(255) NOT NULL,
    `admin_id` varchar(32) NOT NULL,
    `description` text,
    `type_commande` enum('poids','nombre','montant','sans_frais') NOT NULL,
    `date_creation` datetime NOT NULL,
    `date_limite` datetime NOT NULL,
    `date_recuperation` datetime NOT NULL,
    `adresse_recuperation` text NOT NULL,
    `montant_total` decimal(10,2) DEFAULT 0.00,
    `poids_total` decimal(8,3) DEFAULT 0.000,
    `frais_port` decimal(8,2) DEFAULT 0.00,
    `public` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_date_limite` (`date_limite`),
    KEY `idx_public` (`public`),
    KEY `idx_type_commande` (`type_commande`),
    KEY `idx_date_creation` (`date_creation`),
    FOREIGN KEY (`admin_id`) REFERENCES `groupon_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des produits
CREATE TABLE IF NOT EXISTS `groupon_produits` (
    `id` varchar(32) NOT NULL,
    `commande_id` varchar(32) NOT NULL,
    `nom` varchar(255) NOT NULL,
    `url` varchar(500),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_commande_id` (`commande_id`),
    FOREIGN KEY (`commande_id`) REFERENCES `groupon_commandes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des variations
CREATE TABLE IF NOT EXISTS `groupon_variations` (
    `id` varchar(32) NOT NULL,
    `produit_id` varchar(32) NOT NULL,
    `nom` varchar(255) NOT NULL,
    `poids` decimal(8,3) NOT NULL,
    `prix` decimal(8,2) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_produit_id` (`produit_id`),
    KEY `idx_prix` (`prix`),
    FOREIGN KEY (`produit_id`) REFERENCES `groupon_produits`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des participants
CREATE TABLE IF NOT EXISTS `groupon_participants` (
    `id` varchar(32) NOT NULL,
    `commande_id` varchar(32) NOT NULL,
    `user_id` varchar(32) NOT NULL,
    `montant_produits` decimal(10,2) DEFAULT 0.00,
    `part_frais_port` decimal(8,2) DEFAULT 0.00,
    `montant_total` decimal(10,2) DEFAULT 0.00,
    `statut_paiement` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_participant` (`commande_id`, `user_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_statut_paiement` (`statut_paiement`),
    KEY `idx_montant_total` (`montant_total`),
    FOREIGN KEY (`commande_id`) REFERENCES `groupon_commandes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `groupon_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des articles commandés
CREATE TABLE IF NOT EXISTS `groupon_articles_commandes` (
    `id` varchar(32) NOT NULL,
    `participant_id` varchar(32) NOT NULL,
    `variation_id` varchar(32) NOT NULL,
    `quantite` int NOT NULL DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_article` (`participant_id`, `variation_id`),
    KEY `idx_variation_id` (`variation_id`),
    KEY `idx_quantite` (`quantite`),
    FOREIGN KEY (`participant_id`) REFERENCES `groupon_participants`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`variation_id`) REFERENCES `groupon_variations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des paliers de frais
CREATE TABLE IF NOT EXISTS `groupon_paliers_frais` (
    `id` varchar(32) NOT NULL,
    `commande_id` varchar(32) NOT NULL,
    `min_value` decimal(10,3) NOT NULL,
    `max_value` decimal(10,3),
    `frais` decimal(8,2) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_commande_id` (`commande_id`),
    KEY `idx_min_value` (`min_value`),
    FOREIGN KEY (`commande_id`) REFERENCES `groupon_commandes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index supplémentaires pour optimiser les performances
CREATE INDEX `idx_commandes_admin_public` ON `groupon_commandes` (`admin_id`, `public`);
CREATE INDEX `idx_commandes_dates` ON `groupon_commandes` (`date_limite`, `date_recuperation`);
CREATE INDEX `idx_participants_commande_statut` ON `groupon_participants` (`commande_id`, `statut_paiement`);

-- Vues utiles pour les requêtes complexes
CREATE VIEW `groupon_vue_commandes_completes` AS
SELECT 
    c.*,
    u.prenom as admin_prenom,
    u.nom as admin_nom,
    u.email as admin_email,
    COUNT(DISTINCT p.id) as nb_participants,
    COUNT(DISTINCT pr.id) as nb_produits
FROM `groupon_commandes` c
LEFT JOIN `groupon_users` u ON c.admin_id = u.id
LEFT JOIN `groupon_participants` p ON c.id = p.commande_id
LEFT JOIN `groupon_produits` pr ON c.id = pr.commande_id
GROUP BY c.id;

CREATE VIEW `groupon_vue_participants_details` AS
SELECT 
    p.*,
    u.prenom,
    u.nom,
    u.email,
    c.titre as commande_titre,
    c.date_limite,
    c.date_recuperation
FROM `groupon_participants` p
JOIN `groupon_users` u ON p.user_id = u.id
JOIN `groupon_commandes` c ON p.commande_id = c.id;

-- Procédures stockées utiles
DELIMITER //

-- Procédure pour calculer les frais de port
CREATE PROCEDURE `groupon_calculer_frais_port`(
    IN p_commande_id VARCHAR(32),
    IN p_type_commande ENUM('poids','nombre','montant','sans_frais'),
    IN p_valeur DECIMAL(10,3)
)
BEGIN
    DECLARE v_frais DECIMAL(8,2) DEFAULT 0;
    
    SELECT pf.frais INTO v_frais
    FROM `groupon_paliers_frais` pf
    WHERE pf.commande_id = p_commande_id
    AND p_valeur >= pf.min_value
    AND (pf.max_value IS NULL OR p_valeur < pf.max_value)
    ORDER BY pf.min_value DESC
    LIMIT 1;
    
    SELECT COALESCE(v_frais, 0) as frais_port;
END //

-- Procédure pour répartir les frais de port
CREATE PROCEDURE `groupon_repartir_frais_port`(
    IN p_commande_id VARCHAR(32)
)
BEGIN
    DECLARE v_frais_total DECIMAL(8,2);
    DECLARE v_montant_total DECIMAL(10,2);
    
    -- Récupérer les frais de port et le montant total
    SELECT frais_port, montant_total 
    INTO v_frais_total, v_montant_total
    FROM `groupon_commandes` 
    WHERE id = p_commande_id;
    
    -- Mettre à jour les participants
    UPDATE `groupon_participants` p
    SET 
        part_frais_port = CASE 
            WHEN v_montant_total > 0 THEN (v_frais_total * montant_produits / v_montant_total)
            ELSE 0 
        END,
        montant_total = montant_produits + CASE 
            WHEN v_montant_total > 0 THEN (v_frais_total * montant_produits / v_montant_total)
            ELSE 0 
        END
    WHERE commande_id = p_commande_id;
END //

DELIMITER ;

-- Données de test (optionnel)
-- INSERT INTO `groupon_users` (`id`, `email`, `nom`, `prenom`, `password_hash`, `date_inscription`) VALUES
-- ('test123456789012345678901234567890', 'admin@example.com', 'Admin', 'Test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NOW());

-- Afficher les tables créées
SHOW TABLES LIKE 'groupon_%';
