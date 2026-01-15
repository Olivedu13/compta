<?php
header('Content-Type: application/json; charset=utf-8');

$password = "password123";
$hash = '$2y$10$dxqgPTWo98TYjGpN87xb8O/zfdMHvF1yyxWDKtGA4TslbPVyFjqt6';

$result = password_verify($password, $hash);
echo json_encode(['password_verify_result' => $result, 'password' => $password]);
