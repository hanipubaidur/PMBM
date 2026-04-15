<?php
require_once '../../config/db.php';
require_once '../../includes/admin_auth.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method not allowed');
}

try {
    // Get peserta data first to check files
    $stmt = $pdo->prepare("SELECT * FROM peserta WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $peserta = $stmt->fetch();

    if(!$peserta) {
        throw new Exception("Data peserta tidak ditemukan");
    }

    // Check missing files
    $missing_files = [];
    $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
    
    // Check required files
    $required_files = [
        'file_kk' => 'Kartu Keluarga',
        'file_akte' => 'Akte Kelahiran',
        'file_photo' => 'Pas Foto'
    ];

    foreach($required_files as $field => $label) {
        if(empty($peserta[$field]) || !file_exists("../../File/{$folder_name}/{$peserta[$field]}")) {
            $missing_files[] = $label;
        }
    }

    // Check Raport
    for($i = 1; $i <= 5; $i++) {
        $field_name = "file_raport_" . $i;
        if(empty($peserta[$field_name]) || !file_exists("../../File/{$folder_name}/{$peserta[$field_name]}")) {
            $missing_files[] = "Raport Semester " . $i;
        }
    }

    // Generate automatic note if files are missing
    $auto_note = !empty($missing_files) ? "Silakan lengkapi berkas berikut:\n- " . implode("\n- ", $missing_files) : "";
    
    // Use admin's manual note if provided
    $final_note = !empty($_POST['catatan']) ? $_POST['catatan'] : 
                  (!empty($missing_files) ? $auto_note : "");

    $pdo->beginTransaction();

    // 1. UPDATE atau INSERT ke tabel verifikasi_peserta
    $stmt = $pdo->prepare("SELECT id FROM verifikasi_peserta WHERE peserta_id = ?");
    $stmt->execute([$_POST['id']]);
    $exists = $stmt->fetch();

    if($exists) {
        $stmt = $pdo->prepare("UPDATE verifikasi_peserta SET 
            status_verifikasi = ?, catatan = ?, admin_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE peserta_id = ?");
        $stmt->execute([$_POST['status_verifikasi'], $final_note, $_SESSION['admin']['id'], $_POST['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO verifikasi_peserta 
            (peserta_id, admin_id, status_verifikasi, catatan) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['id'], $_SESSION['admin']['id'], $_POST['status_verifikasi'], $final_note]);
    }

    // 2. Sinkronisasi dengan tabel pengumuman
    if($_POST['status_verifikasi'] === 'Verified') {
        if (!empty($_POST['status_penerimaan'])) {
            $stmt = $pdo->prepare("SELECT id FROM pengumuman WHERE peserta_id = ?");
            $stmt->execute([$_POST['id']]);
            
            if($stmt->fetch()) {
                // Update jika sudah ada (Mencegah Clone)
                $stmt = $pdo->prepare("UPDATE pengumuman SET status_penerimaan = ?, admin_id = ?, updated_at = CURRENT_TIMESTAMP WHERE peserta_id = ?");
                $stmt->execute([$_POST['status_penerimaan'], $_SESSION['admin']['id'], $_POST['id']]);
            } else {
                // Insert jika belum ada
                $stmt = $pdo->prepare("INSERT INTO pengumuman (peserta_id, status_penerimaan, admin_id, created_at, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                $stmt->execute([$_POST['id'], $_POST['status_penerimaan'], $_SESSION['admin']['id']]);
            }
        }
    } else {
        // Jika status verifikasi diubah kembali menjadi Pending/Rejected, HAPUS datanya dari pengumuman agar tidak nyangkut
        $stmt = $pdo->prepare("DELETE FROM pengumuman WHERE peserta_id = ?");
        $stmt->execute([$_POST['id']]);
    }

    $pdo->commit();
    $_SESSION['verifikasi_success'] = "Status verifikasi berhasil diperbarui";
    header("Location: ../verifikasi.php");
    exit;

} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: ../verifikasi.php");
    exit;
}