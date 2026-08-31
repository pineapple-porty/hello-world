<?php
require __DIR__ . '/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
echo "Atlas database is ready.\n";
echo "Storage driver: " . db()->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
echo "Schema and starter content are created automatically on first request.\n";
?>