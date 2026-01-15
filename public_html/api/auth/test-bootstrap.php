<?php
header('Content-Type: application/json');

try {
    // Step 1: Load bootstrap
    require_once dirname(dirname(__FILE__)) . '/bootstrap.php';
    echo json_encode(['status' => 'Bootstrap loaded OK']);
} catch (\Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
