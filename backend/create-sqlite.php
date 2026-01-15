<?php
/**
 * Créer la base SQLite manuellement
 */

$dbFile = dirname(__DIR__) . '/compta.db';

if (file_exists($dbFile)) {
    unlink($dbFile);
    echo "Ancien fichier supprimé\n";
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "
CREATE TABLE fin_ecritures_fec (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    
    journal_code VARCHAR(10) NOT NULL,
    journal_lib VARCHAR(255),
    ecriture_num VARCHAR(20) NOT NULL,
    ecriture_date DATE NOT NULL,
    compte_num VARCHAR(10) NOT NULL,
    compte_lib VARCHAR(255),
    comp_aux_num VARCHAR(20),
    comp_aux_lib VARCHAR(255),
    piece_ref VARCHAR(20),
    piece_date DATE,
    ecriture_lib VARCHAR(255),
    debit REAL DEFAULT 0,
    credit REAL DEFAULT 0,
    ecriture_let VARCHAR(10),
    date_let DATE,
    valid_date DATE,
    montant_devise REAL,
    id_devise VARCHAR(3),
    
    exercice INTEGER,
    date_import TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_ecriture_date ON fin_ecritures_fec(ecriture_date);
CREATE INDEX idx_compte_num ON fin_ecritures_fec(compte_num);
CREATE INDEX idx_journal_code ON fin_ecritures_fec(journal_code);
CREATE INDEX idx_exercice ON fin_ecritures_fec(exercice);
CREATE INDEX idx_compte_aux ON fin_ecritures_fec(comp_aux_num);
CREATE INDEX idx_piece_ref ON fin_ecritures_fec(piece_ref);
";

foreach (explode(';', $sql) as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        $pdo->exec($statement);
        echo "✓ " . substr($statement, 0, 60) . "...\n";
    }
}

echo "\n✅ Base créée avec succès\n";
