<?php
session_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../api/whatsapp_api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['status' => 'error', 'message' => 'Akses tidak sah!']));
}

try {
    // ============================================
    // VALIDASI KEAMANAN TINGKAT TINGGI (ANTI-BOT)
    // ============================================
    
    // 1. Cek Token Transaksi (1-Time Use)
    $tx_token = $_POST['tx_token'] ?? '';
    if (!validateAndConsumeTxToken('register', $tx_token)) {
        throw new Exception("Sesi pendaftaran kadaluarsa atau tidak sah! Silakan tutup form dan coba buka kembali.");
    }

    // 2. Cek CSRF Biasa
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        throw new Exception("Token keamanan CSRF tidak valid! Refresh halaman dan coba lagi.");
    }

    // 3. Cek Captcha
    if (!isset($_POST['captcha']) || !validateCaptcha($_POST['captcha'], 'register')) {
        throw new Exception("CAPTCHA tidak valid atau kadaluarsa! Refresh halaman dan coba lagi.");
    }

    // ============================================
    // PROSES INPUT DATA
    // ============================================
    $nisn = preg_replace('/[^0-9]/', '', $_POST['nisn']);
    $nama = clean_input($_POST['nama_lengkap']);
    $phone = preg_replace('/^0/', '62', clean_input($_POST['no_wa_siswa']));
    $jalur_id = (int)clean_input($_POST['jalur_id']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];
    if (strlen($nisn) !== 10) $errors[] = "NISN harus 10 digit angka!";
    if (!preg_match('/^62\d{9,13}$/', $phone)) $errors[] = "Format WhatsApp tidak valid! Gunakan format 08xx-xxxx-xxxx";
    if ($password !== $confirm_password) $errors[] = "Konfirmasi password tidak cocok!";
    if (strlen($password) < 8) $errors[] = "Password minimal 8 karakter!";

    if (!empty($errors)) {
        throw new Exception(implode("<br>", $errors));
    }

    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT j.*, (SELECT COUNT(*) FROM peserta WHERE jalur_id = j.id) as total_pendaftar
        FROM jalur_pendaftaran j 
        WHERE j.id = ? AND ? BETWEEN j.tanggal_buka AND j.tanggal_tutup
    ");
    $stmt->execute([$jalur_id, $now]);
    $jalur = $stmt->fetch();

    if (!$jalur) throw new Exception("Jalur pendaftaran tidak tersedia atau sudah ditutup!");
    if ($jalur['total_pendaftar'] >= $jalur['kuota']) throw new Exception("Mohon maaf, kuota {$jalur['nama_jalur']} sudah penuh!");

    $stmt = $pdo->prepare("SELECT p.id, p.nama_lengkap, p.nisn, j.nama_jalur FROM peserta p LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id WHERE p.nisn = ? OR LOWER(p.nama_lengkap) = LOWER(?)");
    $stmt->execute([$nisn, $nama]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        throw new Exception("Maaf, NISN atau Nama sudah terdaftar di sistem.");
    }

    // Eksekusi Database
    $pdo->beginTransaction();
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO peserta (nama_lengkap, nisn, jalur_id, password, no_wa_siswa) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $nisn, $jalur_id, $hashed_password, $phone]);
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw new Exception("Gagal menyimpan data. Silakan coba beberapa saat lagi.");
    }

    try {
        sendRegistrationSuccess($nisn, $password, $phone, $nama);
    } catch (Exception $e) {
        error_log("WhatsApp API Error: " . $e->getMessage());
    }

    $_SESSION['registration_success'] = "Pendaftaran berhasil! Silakan cek WhatsApp Anda untuk informasi Akun Login.";
    header("Location: ../index.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../index.php?show=daftar");
    exit;
}
?>