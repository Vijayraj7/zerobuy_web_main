<?php
// Simple webhook test file
// Access this at: https://yourdomain.com/webhook-test.php

header('Content-Type: application/json');

$logFile = __DIR__ . '/../storage/logs/webhook-debug.log';

$data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'get' => $_GET,
    'post' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'server' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]
];

file_put_contents($logFile, json_encode($data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

echo json_encode([
    'status' => 'success',
    'message' => 'Webhook test received',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
