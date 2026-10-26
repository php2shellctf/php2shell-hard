<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

$token = @file_get_contents('/run/ctf/proof-token');
$proof = @file_get_contents('/out/f');
$token = is_string($token) ? trim($token) : '';
$proof = is_string($proof) ? trim($proof) : '';
$solved = $token !== '' && $proof !== '' && hash_equals($token, $proof);

echo json_encode([
    'challenge' => 'hard',
    'status' => $solved ? 'rce_proven' : 'pending',
    'next' => $solved ? '/nickname.php' : null,
], JSON_UNESCAPED_SLASHES);
