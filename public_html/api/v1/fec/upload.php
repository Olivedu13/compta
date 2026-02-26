<?php
/**
 * POST /api/v1/fec/upload.php
 * Import des lignes FEC dans la table ecritures
 * 
 * Body JSON:
 * {
 *   "exercice": 2025,
 *   "lines": [ { "journal_code":"AN", "journal_lib":"a nouveau", ... }, ... ]
 * }
 * 
 * Remplace toutes les écritures existantes pour cet exercice.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Méthode POST requise');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['exercice']) || !isset($input['lines']) || !is_array($input['lines'])) {
        http_response_code(400);
        throw new Exception('Paramètres manquants: exercice et lines requis');
    }

    $exercice = (int)$input['exercice'];
    $lines = $input['lines'];

    if ($exercice < 1900 || $exercice > 2100) {
        http_response_code(400);
        throw new Exception('Exercice invalide');
    }

    if (empty($lines)) {
        http_response_code(400);
        throw new Exception('Aucune ligne à importer');
    }

    // Find database
    $projectRoot = dirname(dirname(dirname(__DIR__)));
    if (!file_exists($projectRoot . '/compta.db')) {
        $projectRoot = dirname($projectRoot);
    }
    $dbPath = $projectRoot . '/compta.db';

    if (!file_exists($dbPath)) {
        throw new Exception("Base de données introuvable");
    }

    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete existing entries for this exercice (only on first chunk)
    $append = !empty($input['append']);
    $deleted = 0;
    if (!$append) {
        $delStmt = $db->prepare("DELETE FROM ecritures WHERE exercice = ?");
        $delStmt->execute([$exercice]);
        $deleted = $delStmt->rowCount();
    }

    // Prepare insert
    $stmt = $db->prepare("INSERT INTO ecritures (
        exercice, journal_code, journal_lib, ecriture_num, ecriture_date,
        compte_num, compte_lib, numero_tiers, lib_tiers,
        piece_ref, date_piece, libelle_ecriture,
        debit, credit, lettrage_flag, date_lettrage,
        montant_devise, devise_ecriture, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))");

    $db->beginTransaction();
    $imported = 0;
    $errors = 0;

    foreach ($lines as $i => $l) {
        try {
            $stmt->execute([
                $exercice,
                $l['journal_code'] ?? '',
                $l['journal_lib'] ?? '',
                $l['ecriture_num'] ?? '',
                $l['ecriture_date'] ?? '',
                $l['compte_num'] ?? '',
                $l['compte_lib'] ?? '',
                $l['numero_tiers'] ?? '',
                $l['lib_tiers'] ?? '',
                $l['piece_ref'] ?? '',
                $l['date_piece'] ?? '',
                $l['libelle_ecriture'] ?? '',
                (float)($l['debit'] ?? 0),
                (float)($l['credit'] ?? 0),
                $l['lettrage_flag'] ?? '',
                $l['date_lettrage'] ?? '',
                $l['montant_devise'] ?? '',
                $l['devise_ecriture'] ?? '',
            ]);
            $imported++;
        } catch (Exception $e) {
            $errors++;
            if ($errors > 50) {
                throw new Exception("Trop d'erreurs d'insertion (> 50), import annulé");
            }
        }
    }

    $db->commit();

    // Stats on class 6 (charges)
    $chargesStmt = $db->prepare("SELECT COUNT(*) FROM ecritures WHERE exercice = ? AND SUBSTR(compte_num, 1, 1) = '6'");
    $chargesStmt->execute([$exercice]);
    $nbCharges = (int)$chargesStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'deleted_previous' => $deleted,
        'errors' => $errors,
        'exercice' => $exercice,
        'nb_charges_class6' => $nbCharges,
        'message' => "Import réussi: $imported lignes importées pour l'exercice $exercice ($nbCharges écritures de charges classe 6)"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    if (http_response_code() === 200) http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
