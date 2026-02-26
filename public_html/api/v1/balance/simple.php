<?php
/**
 * GET /api/v1/balance/simple
 * Récupère le bilan simple filtré par année
 * 
 * @method GET
 * @param {int} exercice - Year (default: current year)
 * @param {int} page - Page number (default: 1)
 * @param {int} limit - Results per page (default: 100, max: 500)
 * 
 * @return {success: boolean, data: array, pagination: object, error?: string}
 */

// Find project root (works locally with public_html/ and on Ionos flat webroot)
$_projectRoot = dirname(dirname(dirname(__DIR__)));
if (!file_exists($_projectRoot . '/backend/bootstrap.php')) {
    $_projectRoot = dirname($_projectRoot);
}
require_once $_projectRoot . '/backend/bootstrap.php';

use App\Config\Database;
use App\Config\InputValidator;
use App\Config\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    // Validation des entrées
    try {
        $exercice = InputValidator::asYear($_GET['exercice'] ?? date('Y'));
        $page = InputValidator::asPage($_GET['page'] ?? 1);
        $limit = InputValidator::asLimit($_GET['limit'] ?? 100, 500);
    } catch (\InvalidArgumentException $e) {
        http_response_code(400);
        throw new \Exception("Invalid parameter: " . $e->getMessage());
    }
    
    $offset = ($page - 1) * $limit;
    $db = Database::getInstance();
    
    // Total pour pagination
    $total = $db->fetchOne(
        "SELECT COUNT(*) as count FROM fin_balance WHERE exercice = ?",
        [$exercice]
    )['count'] ?? 0;
    
    // Données avec JOIN sécurisé
    $balances = $db->fetchAll(
        "SELECT b.*, p.compte_lib 
         FROM fin_balance b
         LEFT JOIN sys_plan_comptable p ON b.compte_num = p.compte_num
         WHERE b.exercice = ?
         ORDER BY b.compte_num
         LIMIT ? OFFSET ?",
        [$exercice, $limit, $offset]
    );
    
    Logger::info("Balance retrieved", [
        'exercice' => $exercice,
        'rows' => count($balances),
        'page' => $page
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $balances,
        'pagination' => [
            'page' => (int) $page,
            'limit' => (int) $limit,
            'total' => (int) $total,
            'pages' => ceil($total / $limit)
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (\Exception $e) {
    Logger::error("Balance API error", [
        'error' => $e->getMessage()
    ]);
    
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    
    $errorMsg = (getenv('APP_ENV') === 'production') 
        ? 'Service unavailable' 
        : $e->getMessage();
    
    echo json_encode([
        'success' => false,
        'error' => $errorMsg
    ]);
}
