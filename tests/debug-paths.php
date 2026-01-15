<?php
// Debug simple pour vérifier la structure
header('Content-Type: application/json');

$output = [];

// Test 1: Fichiers existent?
$output['bootstrap_pub'] = file_exists(__DIR__ . '/bootstrap.php') ? 'OK' : 'MISSING';
$output['bootstrap_backend'] = file_exists(__DIR__ . '/../backend/bootstrap.php') ? 'OK' : 'MISSING';

// Test 2: Paths
$output['current_dir'] = __DIR__;
$output['parent_dir'] = dirname(__DIR__);

// Test 3: Charger bootstrap
try {
    require_once __DIR__ . '/bootstrap.php';
    $output['bootstrap_load'] = 'OK';
} catch (\Exception $e) {
    $output['bootstrap_error'] = $e->getMessage();
}

// Test 4: .env - Chercher à plusieurs endroits
$env_locations = [
    '/homepages/29/d210120109/.env',
    dirname(dirname(__FILE__)) . '/.env',  // Parent de public_html
    '/.env',
    '/var/www/.env'
];

$env_found = 'MISSING';
foreach ($env_locations as $loc) {
    if (@file_exists($loc)) {
        $env_found = 'OK at ' . $loc;
        break;
    }
}
$output['env_exists'] = $env_found;
$output['checked_paths'] = $env_locations;

echo json_encode($output, JSON_PRETTY_PRINT);
?>
