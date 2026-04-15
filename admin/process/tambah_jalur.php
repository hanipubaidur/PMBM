<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_jalur = trim($_POST['nama_jalur'] ?? '');

    if (!empty($nama_jalur)) {
        try {
            // Insert nama jalur saja, tanggal dan kuota diisi null dulu
            $stmt = $pdo->prepare("INSERT INTO jalur_pendaftaran (nama_jalur) VALUES (?)");
            $stmt->execute([$nama_jalur]);
            
            $_SESSION['success'] = "Jalur pendaftaran '$nama_jalur' berhasil ditambahkan!";
        } catch (PDOException $e) {
            // Handle error jika nama jalur duplikat (karena di database kamu nama_jalur itu UNIQUE)
            if ($e->getCode() == 23000) {
                $_SESSION['error'] = "Gagal: Jalur pendaftaran dengan nama tersebut sudah ada!";
            } else {
                $_SESSION['error'] = "Gagal menambah jalur: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error'] = "Nama jalur tidak boleh kosong!";
    }
} else {
    $_SESSION['error'] = "Metode request tidak valid!";
}

header("Location: ../pengaturan.php");
exit;