<?php
/**
 * Simple git pull endpoint
 * Permet de mettre à jour le code depuis GitHub via SFTP
 */

header('Content-Type: application/json');

try {
    $cwd = getcwd();
    $root = dirname(dirname(__FILE__));
    chdir($root);
    
    $output = [];
    $exitCode = 0;
    exec('git pull origin main 2>&1', $output, $exitCode);
    $output = implode("\n", $output);
    
    chdir($cwd);
    
    if ($exitCode === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Git pull completed',
            'output' => $output
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Git pull failed',
            'output' => $output
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
