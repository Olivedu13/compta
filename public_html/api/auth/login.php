<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

error_log("=== LOGIN START ===");

// Step 1: Get input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Invalid JSON']));
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
error_log("LOGIN: Email=$email, Password length=" . strlen($password));

if (!$email || !$password) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Email and password required']));
}

// Step 2: Load bootstrap — compatible local (public_html/) et Ionos (flat webroot)
$_root = dirname(dirname(dirname(__FILE__)));
if (!file_exists($_root . '/bootstrap.php')) {
    $_root = dirname($_root);
}
$bootstrap_path = $_root . '/bootstrap.php';
error_log("LOGIN: Loading bootstrap from $bootstrap_path");

if (!file_exists($bootstrap_path)) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Bootstrap not found at ' . $bootstrap_path]));
}

try {
    require_once $bootstrap_path;
    error_log("LOGIN: Bootstrap loaded successfully");
} catch (Exception $e) {
    error_log("LOGIN: Bootstrap error: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Bootstrap failed', 'msg' => $e->getMessage()]));
}

try {
    // Step 3: Get DB and fetch user
    $db = \App\Config\Database::getInstance();
    error_log("LOGIN: Database ready");
    
    $user = $db->fetchOne("SELECT id, email, nom, prenom, password_hash, role FROM sys_utilisateurs WHERE email = ?", [$email]);
    
    if (!$user) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'User not found']));
    }
    
    error_log("LOGIN: User found: " . $user['nom']);
    
    // Step 4: Verify password
    if (!password_verify($password, $user["password_hash"])) {
        http_response_code(401);
        die(json_encode(['success' => false, 'error' => 'Invalid password']));
    }
    
    error_log("LOGIN: Password verified");
    
    // Step 5: Create JWT
    $payload = [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'role' => $user['role'] ?? 'user'
    ];
    
    $token = \App\Config\JwtManager::createToken($payload, 86400);
    error_log("LOGIN: Token created");
    
    // Step 6: Update last login
    $db->query("UPDATE sys_utilisateurs SET date_dernier_login = NOW() WHERE id = ?", [(int)$user['id']]);
    
    // Step 7: Return success
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'role' => $user['role'] ?? 'user'
        ],
        'expiresIn' => 86400
    ]);
    
    error_log("=== LOGIN SUCCESS ===");
    
} catch (\Exception $e) {
    error_log("LOGIN ERROR: " . $e->getMessage() . " | " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
