<?php
/**
 * Test endpoint to verify routing
 */
echo json_encode([
    'success' => true,
    'message' => 'API v1 router is working',
    'uri' => $_SERVER['REQUEST_URI'],
    'orig_uri' => $_SERVER['ORIG_REQUEST_URI'] ?? 'N/A',
    'script_name' => $_SERVER['SCRIPT_NAME'],
    'request_method' => $_SERVER['REQUEST_METHOD']
], JSON_PRETTY_PRINT);
