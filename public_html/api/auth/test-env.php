<?php
header('Content-Type: application/json');

// Load env manually
$envFile = dirname(dirname(dirname(__FILE__))) . '/.env';

$output = [];
$output['env_file_path'] = $envFile;
$output['env_file_exists'] = file_exists($envFile);

if (!file_exists($envFile)) {
    // Try parent
    $envFile2 = dirname(dirname(dirname(dirname(__FILE__)))) . '/.env';
    $output['env_file_path_2'] = $envFile2;
    $output['env_file_2_exists'] = file_exists($envFile2);
    if (file_exists($envFile2)) {
        $envFile = $envFile2;
    }
}

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    
    $output['env_loaded'] = true;
    $output['DB_HOST'] = getenv('DB_HOST');
    $output['DB_NAME'] = getenv('DB_NAME');
    $output['DB_USER'] = getenv('DB_USER');
    $output['JWT_SECRET'] = substr(getenv('JWT_SECRET'), 0, 10) . '***';
} else {
    $output['env_loaded'] = false;
    $output['error'] = '.env not found';
}

echo json_encode($output);
?>
