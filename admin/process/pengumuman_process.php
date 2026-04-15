<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: ../login.php");
    exit;
}

if(!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Parameter tidak lengkap!";
    header("Location: ../pengumuman.php");
    exit;
}

$peserta_id = $_GET['id'];
$status = strtolower($_GET['status']);
$admin_id = $_SESSION['admin']['id'] ?? null;

if(!in_array($status, ['diterima', 'ditolak'])) {
    $_SESSION['error'] = "Status tidak valid!";
    header("Location: ../pengumuman.php");
    exit;
}

try {
    // Cek apakah data sudah ada di pengumuman
    $check = $pdo->prepare("SELECT id FROM pengumuman WHERE peserta_id = ?");
    $check->execute([$peserta_id]);
    
    if($check->fetch()) {
        // Update data jika sudah ada (Mencegah Clone)
        $stmt = $pdo->prepare("UPDATE pengumuman SET status_penerimaan = ?, admin_id = ?, updated_at = NOW() WHERE peserta_id = ?");
        $stmt->execute([$status, $admin_id, $peserta_id]);
    } else {
        // Insert data baru jika belum ada
        $stmt = $pdo->prepare("INSERT INTO pengumuman (peserta_id, status_penerimaan, admin_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$peserta_id, $status, $admin_id]);
    }
    
    $_SESSION['success'] = "Status penerimaan peserta berhasil diperbarui menjadi " . strtoupper($status) . "!";
} catch(PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
}

header("Location: ../pengumuman.php");
exit;