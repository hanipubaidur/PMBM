<?php
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', 28800);
ini_set('session.cookie_samesite', 'Lax');

session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';

ob_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Hanya metode POST yang diizinkan";
    header("Location: ../index.php?show=login");
    exit;
}

try {
    // 1. Cek Token Transaksi (Anti-Spam)
    $tx_token = $_POST['tx_token'] ?? '';
    if (!validateAndConsumeTxToken('login', $tx_token)) {
        throw new Exception("Sesi login kadaluarsa atau tidak sah! Silakan refresh halaman.");
    }

    // 2. Cek CSRF
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception("Token keamanan tidak valid!");
    }

    // 3. Cek Captcha
    if (!isset($_POST['captcha']) || !validateCaptcha($_POST['captcha'], 'login')) {
        throw new Exception("CAPTCHA tidak valid!");
    }

    $nisn = trim($_POST['nisn']);
    $password = $_POST['password'];

    if (empty($nisn) || empty($password)) {
        throw new Exception("NISN dan password harus diisi!");
    }

    $stmt = $pdo->prepare("SELECT * FROM peserta WHERE nisn = ?");
    $stmt->execute([$nisn]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        incrementLoginAttempt();
        throw new Exception("NISN atau password salah!");
    }

    unset($_SESSION['login_attempts']);
    unset($_SESSION['login_blocked']);

    $_SESSION['user'] = [
        'id' => $user['id'],
        'nisn' => $user['nisn'],
        'nama' => $user['nama_lengkap'],
        'login_time' => time(),
        'device' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    ob_end_clean();
    $_SESSION['success'] = "Login berhasil!";
    header("Location: ../dashboard.php");
    exit();

} catch (Exception $e) {
    ob_end_clean();
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../index.php?show=login");
    exit();
}

function incrementLoginAttempt() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= 3) $_SESSION['login_blocked'] = time() + 300;
}
?>