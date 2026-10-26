<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path !== '/status.php' && $path !== '/') {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"status":"not_found"}';
    return;
}

require '/var/www/html/status.php';
