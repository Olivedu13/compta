<?php
/**
 * GET /api/v1/accounting/balance
 * Récupère la balance générale pour un exercice
 * 
 * Params:
 * - exercice (required): Année comptable (ex: 2024)
 * - page (optional): Numéro de page (défaut: 1)
 * - limit (optional): Résultats par page (défaut: 100, max: 500)
 */

use App\Config\InputValidator;
use App\Config\Logger;

try {
    // Validation des paramètres
    $exercice = InputValidator::asYear($_GET['exercice'] ?? null);
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 100), 500);
    
    if (!$exercice) {
        http_response_code(400);
        throw new Exception('Parameter exercice is required');
    }
    
    $offset = ($page - 1) * $limit;
    $db = getDatabase();
    
    // Total pour pagination
    $countResult = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM fin_balance WHERE exercice = ?",
        [$exercice]
    );
    $total = $countResult['cnt'] ?? 0;
    
    // Données paginées
    $balances = $db->fetchAll(
        "SELECT b.*, p.libelle, p.classe_racine 
         FROM fin_balance b
         LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
         WHERE b.exercice = ?
         ORDER BY b.compte_num
         LIMIT ? OFFSET ?",
        [$exercice, $limit, $offset]
    );
    
    Logger::info('Balance retrieved', [
        'exercice' => $exercice,
        'rows' => count($balances),
        'page' => $page
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $balances,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    Logger::error('Failed to fetch balance', ['error' => $e->getMessage()]);
}
