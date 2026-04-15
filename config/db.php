<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

date_default_timezone_set('Asia/Jakarta'); // Add this line after session_start()

// Database Configuration
$host = 'sql313.infinityfree.com';
$db   = 'if0_41320441_pmbm26';
$user = 'if0_41320441';
$pass = 'NinipGanteng123';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed!");
}

// Fonnte WhatsApp API
define('FONNTE_API', 'https://api.fonnte.com/send');
define('FONNTE_TOKEN', '4S54xvg83YcPb3YWzVAR');
define('ADMIN_PHONE', '6285725128427');