-- ========================================
-- Atelier Thierry Christiane - Schema SQL
-- Système de gestion comptable bijouterie
-- ========================================

-- ========================================
-- TABLE : Journaux (CRÉER EN PREMIER - pas de dépendances)
-- ========================================

CREATE TABLE IF NOT EXISTS sys_journaux (
    code VARCHAR(10) PRIMARY KEY,
    libelle VARCHAR(255) NOT NULL,
    type_journal VARCHAR(20) COMMENT 'VE (ventes), AC (achats), BQ (banque), etc.',
    devise_defaut VARCHAR(3) DEFAULT 'EUR',
    is_actif BOOLEAN DEFAULT TRUE,
    
    KEY idx_type (type_journal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Référentiel des journaux comptables';

-- ========================================
-- TABLE : Plan Comptable (CRÉER EN DEUXIÈME)
-- ========================================

CREATE TABLE IF NOT EXISTS sys_plan_comptable (
    compte_num VARCHAR(10) PRIMARY KEY COMMENT 'Numéro de compte (ex: 311, 401, 601)',
    libelle VARCHAR(255) NOT NULL COMMENT 'Libellé du compte',
    classe_racine CHAR(1) NOT NULL COMMENT 'Classe racine (1-8, ou 9)',
    type_compte VARCHAR(50) COMMENT 'Type: Actif, Passif, Charge, Produit, etc.',
    nature_compte VARCHAR(50) COMMENT 'Nature: Stock, Client, Fournisseur, etc.',
    is_actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_classe_racine (classe_racine),
    KEY idx_type_compte (type_compte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Référentiel du plan comptable bijouterie selon PCG 2025';

-- ========================================
-- TABLE : Balance
-- ========================================

CREATE TABLE IF NOT EXISTS fin_balance (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    exercice YEAR NOT NULL COMMENT 'Année comptable',
    compte_num VARCHAR(10) NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0 COMMENT 'Total débit du compte',
    credit DECIMAL(15,2) DEFAULT 0 COMMENT 'Total crédit du compte',
    solde DECIMAL(15,2) DEFAULT 0 COMMENT 'Solde (débit - crédit)',
    date_import TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY idx_exercice_compte (exercice, compte_num),
    KEY idx_date_import (date_import),
    UNIQUE KEY uk_exercice_compte (exercice, compte_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Données agrégées (Balance) pour tableaux de bord et SIG';

-- ========================================
-- TABLE : Écritures (FEC - Big Data)
-- ========================================

CREATE TABLE IF NOT EXISTS fin_ecritures_fec (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    
    -- 18 champs obligatoires du FEC (Article A47 A-1)
    journal_code VARCHAR(10) NOT NULL COMMENT 'JournalCode',
    journal_lib VARCHAR(255) COMMENT 'JournalLib',
    ecriture_num VARCHAR(20) NOT NULL COMMENT 'EcritureNum - Numéro séquentiel',
    ecriture_date DATE NOT NULL COMMENT 'EcritureDate - Date de l\'écriture',
    compte_num VARCHAR(10) NOT NULL COMMENT 'CompteNum - Compte général',
    compte_lib VARCHAR(255) COMMENT 'CompteLib - Libellé du compte',
    comp_aux_num VARCHAR(20) COMMENT 'CompAuxNum - Compte auxiliaire (client, fournisseur)',
    comp_aux_lib VARCHAR(255) COMMENT 'CompAuxLib - Libellé compte auxiliaire',
    piece_ref VARCHAR(20) COMMENT 'PieceRef - Référence pièce (facture, BL)',
    piece_date DATE COMMENT 'PieceDate - Date de la pièce',
    ecriture_lib VARCHAR(255) COMMENT 'EcritureLib - Libellé écriture',
    debit DECIMAL(15,2) DEFAULT 0 COMMENT 'Debit - Montant débit',
    credit DECIMAL(15,2) DEFAULT 0 COMMENT 'Credit - Montant crédit',
    ecriture_let VARCHAR(10) COMMENT 'EcritureLet - Lettrage',
    date_let DATE COMMENT 'DateLet - Date de lettrage',
    valid_date DATE COMMENT 'ValidDate - Date de validation',
    montant_devise DECIMAL(15,2) COMMENT 'MontantDevise',
    id_devise VARCHAR(3) COMMENT 'IdDevise - Code devise (EUR, USD, etc.)',
    
    -- Métadonnées internes
    exercice YEAR COMMENT 'Année comptable dérivée',
    date_import TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Contrainte FK sur le compte racine (3 premiers chiffres)
    -- Les écritures FEC utilisent souvent des sous-comptes (ex: 41100000 -> 411)
    KEY idx_ecriture_date (ecriture_date),
    KEY idx_compte_num (compte_num),
    KEY idx_journal_code (journal_code),
    KEY idx_exercice (exercice),
    KEY idx_montants (debit, credit),
    KEY idx_piece_ref (piece_ref),
    KEY idx_compte_aux (comp_aux_num),
    KEY idx_date_import (date_import)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Détail des écritures comptables - Format FEC obligatoire';

-- ========================================
-- TABLE : Stocks (Bijouterie - Or, Diamants)
-- ========================================

CREATE TABLE IF NOT EXISTS fin_stocks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    code_article VARCHAR(50) UNIQUE NOT NULL,
    designation VARCHAR(255) NOT NULL,
    compte_num VARCHAR(10) NOT NULL COMMENT 'Compte stock associé (311, 312, etc.)',
    quantite DECIMAL(10,4) DEFAULT 0 COMMENT 'Quantité en main',
    quantite_reserve DECIMAL(10,4) DEFAULT 0,
    quantite_commande DECIMAL(10,4) DEFAULT 0,
    
    -- Valeurs
    prix_unitaire DECIMAL(12,4) NOT NULL COMMENT 'Coût unitaire (approche FIFO)',
    valeur_totale DECIMAL(15,2) COMMENT 'Valorisation stock',
    
    -- Métadonnées
    exercice YEAR,
    date_inventaire DATE,
    date_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_exercice_stock (exercice),
    KEY idx_compte_stock (compte_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Valorisation des stocks (Or, diamants, etc.)';

-- ========================================
-- TABLE : Configurations
-- ========================================

CREATE TABLE IF NOT EXISTS sys_config (
    cle VARCHAR(100) PRIMARY KEY,
    valeur LONGTEXT,
    type_donnees VARCHAR(20) COMMENT 'string, int, json, bool',
    description VARCHAR(255),
    date_maj TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuration applicative générale';

-- ========================================
-- TABLE : Utilisateurs (pour futures améliorations)
-- ========================================

CREATE TABLE IF NOT EXISTS sys_utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    password_hash VARCHAR(255),
    role VARCHAR(50) DEFAULT 'user' COMMENT 'admin, user, viewer',
    is_actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_dernier_login TIMESTAMP NULL,
    
    KEY idx_email (email),
    KEY idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Gestion des utilisateurs';

-- ========================================
-- SEED DATA : Plan Comptable Bijouterie
-- ========================================

-- Classe 1 : Comptes d'Actif
INSERT IGNORE INTO sys_plan_comptable VALUES
('101', 'Capital social', '1', 'Passif', 'Capital', TRUE, NOW()),
('164', 'Emprunts bancaires', '1', 'Passif', 'Dettes court terme', TRUE, NOW()),
('311', 'Stock Or/Métaux précieux', '3', 'Actif', 'Stock', TRUE, NOW()),
('312', 'Stock Diamants/Pierres', '3', 'Actif', 'Stock', TRUE, NOW()),
('313', 'Stock Bijoux finis', '3', 'Actif', 'Stock', TRUE, NOW()),
('401', 'Fournisseurs', '4', 'Passif', 'Tiers', TRUE, NOW()),
('411', 'Clients', '4', 'Actif', 'Tiers', TRUE, NOW()),
('512', 'Comptes bancaires', '5', 'Actif', 'Trésorerie', TRUE, NOW()),
('530', 'Caisse', '5', 'Actif', 'Trésorerie', TRUE, NOW()),
-- Comptes à 8 chiffres (utilisés dans le FEC)
('40100000', 'FOURNISSEURS', '4', 'Passif', 'Tiers', TRUE, NOW()),
('41100000', 'CLIENTS', '4', 'Actif', 'Tiers', TRUE, NOW());

-- Classe 6 : Charges
INSERT IGNORE INTO sys_plan_comptable VALUES
('601', 'Matières premières (Or/Diamants)', '6', 'Charge', 'Achat', TRUE, NOW()),
('602', 'Fournitures consommables', '6', 'Charge', 'Achat', TRUE, NOW()),
('603', 'Variation de stocks', '6', 'Charge', 'Stock', TRUE, NOW()),
('604', 'Frais accessoires d\'achat', '6', 'Charge', 'Achat', TRUE, NOW()),
('611', 'Sous-traitance artisanale', '6', 'Charge', 'Service', TRUE, NOW()),
('612', 'Locations', '6', 'Charge', 'Service', TRUE, NOW()),
('613', 'Entretien', '6', 'Charge', 'Service', TRUE, NOW()),
('621', 'Honoraires', '6', 'Charge', 'Service', TRUE, NOW()),
('622', 'Rémunérations d\'intermédiaires', '6', 'Charge', 'Service', TRUE, NOW()),
('623', 'Publicité, relations publiques', '6', 'Charge', 'Service', TRUE, NOW()),
('624', 'Frais de transport', '6', 'Charge', 'Service', TRUE, NOW()),
('625', 'Frais de télécommunications', '6', 'Charge', 'Service', TRUE, NOW()),
('626', 'Frais bancaires', '6', 'Charge', 'Service', TRUE, NOW()),
('627', 'Cotisations professionnelles', '6', 'Charge', 'Service', TRUE, NOW()),
('631', 'Impôts et taxes', '6', 'Charge', 'Impôt', TRUE, NOW()),
('637', 'Autres impôts', '6', 'Charge', 'Impôt', TRUE, NOW()),
('641', 'Salaires, traitements', '6', 'Charge', 'Personnel', TRUE, NOW()),
('645', 'Charges sociales', '6', 'Charge', 'Personnel', TRUE, NOW()),
('681', 'Dotations amortissements', '6', 'Charge', 'Amortissement', TRUE, NOW()),
('691', 'Charges financières', '6', 'Charge', 'Financier', TRUE, NOW());

-- Classe 7 : Produits
INSERT IGNORE INTO sys_plan_comptable VALUES
('701', 'Ventes de bijoux finis', '7', 'Produit', 'Vente', TRUE, NOW()),
('702', 'Ventes de matières brutes', '7', 'Produit', 'Vente', TRUE, NOW()),
('703', 'Prestations de services (réparations, façonnage)', '7', 'Produit', 'Service', TRUE, NOW()),
('704', 'Reprises sur dépréciations', '7', 'Produit', 'Ajustement', TRUE, NOW()),
('706', 'Escomptes accordés', '7', 'Produit', 'Ajustement', TRUE, NOW()),
('707', 'Autres produits', '7', 'Produit', 'Autre', TRUE, NOW()),
('741', 'Produits financiers', '7', 'Produit', 'Financier', TRUE, NOW()),
('751', 'Produits exceptionnels', '7', 'Produit', 'Exceptionnel', TRUE, NOW());

-- Journaux
INSERT IGNORE INTO sys_journaux VALUES
('VE', 'Journal des Ventes', 'VE', 'EUR', TRUE),
('AC', 'Journal des Achats', 'AC', 'EUR', TRUE),
('BQ', 'Journal de Banque', 'BQ', 'EUR', TRUE),
('OD', 'Journal des Opérations Diverses', 'OD', 'EUR', TRUE);

-- Utilisateurs test
INSERT IGNORE INTO sys_utilisateurs (email, nom, prenom, password_hash, role, is_actif) VALUES
('admin@atelier-thierry.fr', 'Admin', 'System', '$2y$10$lPWNHyZXZblFSZ5gS.GvuODQ0mULO4cE.xOJPLVTj8Yfz3qweFBB2', 'admin', TRUE),
('comptable@atelier-thierry.fr', 'Comptable', 'Test', '$2y$10$lPWNHyZXZblFSZ5gS.GvuODQ0mULO4cE.xOJPLVTj8Yfz3qweFBB2', 'user', TRUE),
('viewer@atelier-thierry.fr', 'Viewer', 'Test', '$2y$10$lPWNHyZXZblFSZ5gS.GvuODQ0mULO4cE.xOJPLVTj8Yfz3qweFBB2', 'viewer', TRUE);

-- Configuration
INSERT IGNORE INTO sys_config VALUES
('societe_nom', 'Atelier Thierry Christiane', 'string', 'Nom de la bijouterie', NOW()),
('societe_siret', '00000000000000', 'string', 'SIRET de la bijouterie', NOW()),
('devise_principale', 'EUR', 'string', 'Devise par défaut', NOW()),
('exercice_courant', YEAR(NOW()), 'int', 'Année comptable active', NOW()),
('format_decimal', ',', 'string', 'Séparateur décimal (FR: , vs US: .)', NOW());
