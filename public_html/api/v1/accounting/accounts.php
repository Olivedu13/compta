<?php
/**
 * GET /api/v1/accounting/accounts
 * Liste des comptes avec leurs soldes
 * 
 * Params:
 * - exercice (required): Année comptable (ex: 2024)
 * - classe (optional): Filtrer par classe (1-9)
 */

use App\Config\InputValidator;
use App\Config\Logger;

try {
    $exercice = InputValidator::asYear($_GET['exercice'] ?? null);
    
    if (!$exercice) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required');
    }
    
    $db = getDatabase();
    
    // Filtre optionnel par classe
    $whereClause = "WHERE b.exercice = ?";
    $params = [$exercice];
    
    if (!empty($_GET['classe'])) {
        $classe = preg_replace('/[^0-9]/', '', $_GET['classe']);
        if (strlen($classe) === 1) {
            $whereClause .= " AND SUBSTRING(b.compte_num, 1, 1) = ?";
            $params[] = $classe;
        }
    }
    
    $comptes = $db->fetchAll(
        "SELECT 
            b.compte_num,
            SUBSTRING(b.compte_num, 1, 1) as classe,
            b.solde,
            b.debit,
            b.credit,
            p.libelle as compte_libelle
        FROM fin_balance b
        LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
        $whereClause
        ORDER BY b.compte_num",
        $params
    );
    
    Logger::info('Accounts retrieved', [
        'exercice' => $exercice,
        'count' => count($comptes)
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($comptes),
        'data' => $comptes,
        'exercice' => $exercice
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    Logger::error('Failed to fetch accounts', ['error' => $e->getMessage()]);
}
