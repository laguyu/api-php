<?php

declare(strict_types=1);

$publicIndex = __DIR__ . '/../public/index.php';

if (!is_file($publicIndex)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'error' => 'Server Misconfiguration',
        'message' => 'Entry point public/index.php was not found.',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

require $publicIndex;
