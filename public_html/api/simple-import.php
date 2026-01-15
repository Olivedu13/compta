<?php
/**
 * Endpoint de import FEC SIMPLIFIÉ
 * Avec remplissage du plan comptable depuis le FEC
 * 
 * REFACTORISÉ pour utiliser:
 * - bootstrap.php pour initialisation
 * - FecAnalyzer pour parsing robuste
 * - ImportService pour import des données
 */

// Bootstrap unique - initialisation complète + sécurité
require_once dirname(dirname(dirname(__FILE__))) . '/backend/bootstrap.php';

// Imports
use App\Config\Database;
use App\Config\Logger;
use App\Services\FecAnalyzer;
use App\Services\ImportService;

header('Content-Type: application/json; charset=utf-8');

try {
    // ========================================
    // Validation de la requête
    // ========================================
    
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        throw new Exception("Fichier requis");
    }
    
    $file = $_FILES['file'];
    $tmpFile = $file['tmp_name'];
    
    if (!file_exists($tmpFile)) {
        http_response_code(400);
        throw new Exception("Fichier temporaire non trouvé");
    }
    
    if (!is_uploaded_file($tmpFile)) {
        http_response_code(400);
        throw new Exception("Fichier non uploadé correctement");
    }
    
    Logger::info("Début import FEC", ['file' => $file['name'], 'size' => $file['size']]);
    
    // ========================================
    // Étape 1 : Analyse du FEC
    // ========================================
    
    $analyzer = new FecAnalyzer();
    $analysis = $analyzer->analyze($tmpFile);
    
    if ($analysis['status'] !== 'success') {
        http_response_code(400);
        throw new Exception("Analyse FEC échouée: " . json_encode($analysis['anomalies']['critical']));
    }
    
    if (!$analysis['ready_for_import']) {
        http_response_code(400);
        throw new Exception("FEC contient des anomalies critiques et ne peut pas être importé");
    }
    
    Logger::info("FEC analysé avec succès", [
        'lines' => $analysis['file_info']['total_lines'],
        'accounts' => $analysis['data_statistics']['accounts_count'],
        'exercice' => $analysis['exercice_detected']
    ]);
    
    // ========================================
    // Étape 2 : Import via ImportService
    // ========================================
    
    $importer = new ImportService();
    $result = $importer->importFEC($tmpFile);
    
    Logger::info("Import FEC terminé avec succès", $result);
    
    // ========================================
    // Réponse de succès
    // ========================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'message' => $result['rows_inserted'] . " écritures FEC importées",
            'count' => $result['rows_inserted'],
            'exercice' => $analysis['exercice_detected'],
            'analysis' => [
                'accounts_found' => $analysis['data_statistics']['accounts_count'],
                'journals_found' => $analysis['data_statistics']['journals_count'],
                'balance_check' => $analysis['data_statistics']['total_debit'] . ' EUR (débits) = ' . $analysis['data_statistics']['total_credit'] . ' EUR (crédits)',
                'is_balanced' => $analysis['data_statistics']['total_debit'] == $analysis['data_statistics']['total_credit']
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    Logger::error("Erreur lors de l'import FEC", [
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} finally {
    // Nettoie le fichier temporaire
    if (isset($tmpFile) && file_exists($tmpFile)) {
        @unlink($tmpFile);
    }
}
?>
