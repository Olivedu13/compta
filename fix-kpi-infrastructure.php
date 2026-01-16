<?php
/**
 * Correction de l'infrastructure KPI
 * 1. Crée les tables manquantes
 * 2. Popule les données depuis ecritures
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = new PDO('sqlite:compta.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║        🔧 CORRECTION DE L'INFRASTRUCTURE KPI                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. DROP les tables si elles existent
echo "1️⃣ Suppression des tables si elles existent...\n";
$tables_to_drop = ['fin_balance', 'fin_ecritures_fec', 'client_sales', 'monthly_sales'];
foreach ($tables_to_drop as $table) {
    try {
        $db->exec("DROP TABLE IF EXISTS $table");
        echo "   ✅ $table dropée\n";
    } catch (Exception $e) {
        echo "   ⚠️  $table: " . $e->getMessage() . "\n";
    }
}

// 2. CREATE FIN_BALANCE
echo "\n2️⃣ Création table fin_balance...\n";
$db->exec("
    CREATE TABLE fin_balance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        exercice INTEGER NOT NULL,
        compte_num VARCHAR(20) NOT NULL,
        debit DECIMAL(15,2) DEFAULT 0,
        credit DECIMAL(15,2) DEFAULT 0,
        solde DECIMAL(15,2) DEFAULT 0,
        UNIQUE(exercice, compte_num)
    )
");
echo "   ✅ Table créée\n";

// 3. POPULATE fin_balance depuis ecritures
echo "\n3️⃣ Population de fin_balance...\n";
$db->exec("
    INSERT INTO fin_balance (exercice, compte_num, debit, credit, solde)
    SELECT 
        exercice,
        compte_num,
        SUM(CASE WHEN debit > 0 THEN debit ELSE 0 END) as debit,
        SUM(CASE WHEN credit > 0 THEN credit ELSE 0 END) as credit,
        SUM(debit - credit) as solde
    FROM ecritures
    GROUP BY exercice, compte_num
");
$count = $db->query("SELECT COUNT(*) FROM fin_balance")->fetchColumn();
echo "   ✅ $count lignes insérées\n";

// 4. CREATE FIN_ECRITURES_FEC
echo "\n4️⃣ Création table fin_ecritures_fec...\n";
$db->exec("
    CREATE TABLE fin_ecritures_fec (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        exercice INTEGER NOT NULL,
        ecriture_date DATE NOT NULL,
        compte_num VARCHAR(20) NOT NULL,
        debit DECIMAL(15,2) DEFAULT 0,
        credit DECIMAL(15,2) DEFAULT 0
    )
");
echo "   ✅ Table créée\n";

// 5. POPULATE fin_ecritures_fec
echo "\n5️⃣ Population de fin_ecritures_fec...\n";
$db->exec("
    INSERT INTO fin_ecritures_fec (exercice, ecriture_date, compte_num, debit, credit)
    SELECT exercice, ecriture_date, compte_num, debit, credit
    FROM ecritures
    WHERE compte_num LIKE '7%' OR compte_num LIKE '6%'
");
$count = $db->query("SELECT COUNT(*) FROM fin_ecritures_fec")->fetchColumn();
echo "   ✅ $count lignes insérées\n";

// 6. CREATE CLIENT_SALES
echo "\n6️⃣ Création table client_sales...\n";
$db->exec("
    CREATE TABLE client_sales (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        exercice INTEGER NOT NULL,
        client_id VARCHAR(20) NOT NULL,
        montant DECIMAL(15,2) DEFAULT 0,
        UNIQUE(exercice, client_id)
    )
");
echo "   ✅ Table créée\n";

// 7. POPULATE client_sales
echo "\n7️⃣ Population de client_sales...\n";
$db->exec("
    INSERT INTO client_sales (exercice, client_id, montant)
    SELECT 
        exercice,
        compte_num as client_id,
        ABS(SUM(credit - debit)) as montant
    FROM ecritures
    WHERE compte_num = '411' OR compte_num LIKE '411%'
    GROUP BY exercice, compte_num
");
$count = $db->query("SELECT COUNT(*) FROM client_sales")->fetchColumn();
echo "   ✅ $count lignes insérées\n";

// 8. CREATE MONTHLY_SALES
echo "\n8️⃣ Création table monthly_sales...\n";
$db->exec("
    CREATE TABLE monthly_sales (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        exercice INTEGER NOT NULL,
        mois VARCHAR(7) NOT NULL,
        ca DECIMAL(15,2) DEFAULT 0,
        UNIQUE(exercice, mois)
    )
");
echo "   ✅ Table créée\n";

// 9. POPULATE monthly_sales
echo "\n9️⃣ Population de monthly_sales...\n";
$db->exec("
    INSERT INTO monthly_sales (exercice, mois, ca)
    SELECT 
        exercice,
        SUBSTR(ecriture_date, 1, 7) as mois,
        SUM(credit) as ca
    FROM ecritures
    WHERE compte_num IN ('701', '702', '703')
    GROUP BY exercice, SUBSTR(ecriture_date, 1, 7)
");
$count = $db->query("SELECT COUNT(*) FROM monthly_sales")->fetchColumn();
echo "   ✅ $count lignes insérées\n";

// 10. VERIFICATION
echo "\n🔍 VÉRIFICATION DES DONNÉES:\n";
echo "─────────────────────────────────────────────────────────────\n";

echo "\n   fin_balance:\n";
$rows = $db->query("SELECT * FROM fin_balance ORDER BY compte_num")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    printf("      %s: D=%.2f C=%.2f S=%.2f\n", 
        $row['compte_num'], $row['debit'], $row['credit'], $row['solde']);
}

echo "\n   monthly_sales:\n";
$rows = $db->query("SELECT * FROM monthly_sales ORDER BY mois")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "      " . $row['mois'] . ": " . number_format($row['ca'], 2) . " EUR\n";
}

echo "\n   client_sales:\n";
$rows = $db->query("SELECT * FROM client_sales ORDER BY montant DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "      " . $row['client_id'] . ": " . number_format($row['montant'], 2) . " EUR\n";
}

echo "\n✅ Correction de l'infrastructure terminée!\n\n";
