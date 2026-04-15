<?php
session_start();

header('Content-Type: text/plain');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$type = isset($_GET['type']) && in_array($_GET['type'], ['register', 'login']) ? $_GET['type'] : 'register';

// SECURITY: AMBIL TX TOKEN DARI PARAMETER GET
$tx_token = $_GET['tx_auth'] ?? '';

// SECURITY: TOLAK JIKA TOKEN KOSONG ATAU TIDAK COCOK DENGAN SESSION BE
if (empty($_SESSION['tx_token'][$type]) || !hash_equals($_SESSION['tx_token'][$type], $tx_token)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized Captcha Request');
}

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code = substr(str_shuffle($chars), 0, 6);

$_SESSION['captcha'][$type] = [
    'code' => $code,
    'time' => time(),
    'attempts' => 0,
    'expires' => time() + 90
];

echo $code;
?>