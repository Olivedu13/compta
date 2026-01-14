<?php
/**
 * Script de nettoyage des fichiers inutiles
 * À appeler une seule fois : https://compta.sarlatc.com/cleanup.php
 */

// Protection - vérifier une clé spéciale
$key = $_GET['key'] ?? '';
if ($key !== 'cleanup_2024_sarlatc') {
    http_response_code(403);
    die('Accès refusé');
}

$files_to_delete = [
    'test.php',
    'test_ok.php',
    'simple.php',
    'diagnostic.php',
    'listing.php',
    'check-paths.php',
    'api/test.html'
];

$deleted = [];
$failed = [];

foreach ($files_to_delete as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        if (unlink($path)) {
            $deleted[] = $file;
        } else {
            $failed[] = $file;
        }
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'deleted' => $deleted,
    'failed' => $failed,
    'message' => count($deleted) . ' fichier(s) supprimé(s)'
]);
?>
