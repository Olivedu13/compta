-- Schema SQLite pour Compta Application

-- Plan Comptable
CREATE TABLE IF NOT EXISTS sys_plan_comptable (
    compte_num VARCHAR(10) PRIMARY KEY,
    compte_lib VARCHAR(255) NOT NULL,
    type_compte VARCHAR(20),
    nature_compte VARCHAR(20)
);

-- Journaux
CREATE TABLE IF NOT EXISTS sys_journaux (
    journal_code VARCHAR(10) PRIMARY KEY,
    journal_lib VARCHAR(255) NOT NULL,
    type_journal VARCHAR(20),
    devise VARCHAR(3) DEFAULT 'EUR'
);

-- Écritures Comptables
CREATE TABLE IF NOT EXISTS ecritures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exercice INTEGER NOT NULL,
    journal_code VARCHAR(10) NOT NULL,
    journal_lib VARCHAR(255),
    ecriture_num VARCHAR(50),
    ecriture_date DATE NOT NULL,
    compte_num VARCHAR(10),
    compte_lib VARCHAR(255),
    numero_tiers VARCHAR(50),
    lib_tiers VARCHAR(255),
    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    libelle_ecriture VARCHAR(255),
    montant_devise DECIMAL(15,2),
    devise_ecriture VARCHAR(3),
    taux_change DECIMAL(10,6),
    date_limite_reglement DATE,
    montant_reglement DECIMAL(15,2),
    motif_lettrage VARCHAR(255),
    date_lettrage DATE,
    lettrage_flag BOOLEAN DEFAULT 0,
    piece_ref VARCHAR(50),
    date_piece DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indexes pour performance
CREATE INDEX IF NOT EXISTS idx_journal ON ecritures(journal_code);
CREATE INDEX IF NOT EXISTS idx_tiers ON ecritures(numero_tiers);
CREATE INDEX IF NOT EXISTS idx_date ON ecritures(ecriture_date);
CREATE INDEX IF NOT EXISTS idx_compte ON ecritures(compte_num);
CREATE INDEX IF NOT EXISTS idx_exercice ON ecritures(exercice);
CREATE INDEX IF NOT EXISTS idx_lettrage ON ecritures(lettrage_flag);

-- Données: Plan Comptable minimal
INSERT OR IGNORE INTO sys_plan_comptable VALUES ('401', 'Fournisseurs', 'Passif', 'Tiers');
INSERT OR IGNORE INTO sys_plan_comptable VALUES ('411', 'Clients', 'Actif', 'Tiers');
INSERT OR IGNORE INTO sys_plan_comptable VALUES ('512', 'Banque', 'Actif', 'Trésorerie');
INSERT OR IGNORE INTO sys_plan_comptable VALUES ('580', 'Caisse', 'Actif', 'Trésorerie');
INSERT OR IGNORE INTO sys_plan_comptable VALUES ('601', 'Matières premières', 'Charge', 'Exploitation');
INSERT OR IGNORE INTO sys_plan_comptable VALUES ('701', 'Ventes', 'Produit', 'Exploitation');
INSERT OR IGNORE INTO sys_plan_comptable VALUES ('121', 'Résultat', 'Capitaux', 'Structure');

-- Données: Journaux
INSERT OR IGNORE INTO sys_journaux VALUES ('VE', 'Journal des Ventes', 'Ventes', 'EUR');
INSERT OR IGNORE INTO sys_journaux VALUES ('AC', 'Journal des Achats', 'Achats', 'EUR');
INSERT OR IGNORE INTO sys_journaux VALUES ('CM', 'Journal de Caisse', 'Trésorerie', 'EUR');
INSERT OR IGNORE INTO sys_journaux VALUES ('CL', 'Journal de Banque', 'Trésorerie', 'EUR');
INSERT OR IGNORE INTO sys_journaux VALUES ('OD', 'Journal des OD', 'Divers', 'EUR');
INSERT OR IGNORE INTO sys_journaux VALUES ('AN', 'Journal d''Annulation', 'Divers', 'EUR');
INSERT OR IGNORE INTO sys_journaux VALUES ('BPM', 'Journal de Paye', 'Paye', 'EUR');
