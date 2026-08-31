<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
try {
    db()->query('SELECT 1');
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'service' => 'atlas', 'timestamp' => gmdate('c')], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'service' => 'atlas'], JSON_UNESCAPED_SLASHES);
}
?>