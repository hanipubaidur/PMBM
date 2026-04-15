<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

// Fetch jalur pendaftaran
$stmt = $pdo->query("SELECT * FROM jalur_pendaftaran ORDER BY nama_jalur");
$jalur_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - PMBM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'partials/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row g-0">
            
            <div class="col-md-2 sidebar-container">
                <?php include 'partials/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 pt-4 px-4">
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Pengaturan Sistem</h2>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tambahJalurModal">
                        <i class="bi bi-plus-circle"></i> Tambah Jalur Pendaftaran
                    </button>
                </div>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(empty($jalur_list)): ?>
                    <div class="alert alert-warning">
                        Belum ada jalur pendaftaran. Silakan klik tombol "Tambah Jalur Pendaftaran" di atas untuk memulai.
                    </div>
                <?php else: ?>
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pengaturan Tanggal Jalur Pendaftaran</h5>
                        </div>
                        <div class="card-body">
                            <form action="process/update_periode.php" method="POST">
                                <?php foreach($jalur_list as $jalur): ?>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold"><?= htmlspecialchars($jalur['nama_jalur']) ?></label>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small mb-1">Tanggal Buka</label>
                                        <input type="datetime-local" class="form-control" 
                                               name="tanggal_buka[<?= $jalur['id'] ?>]" 
                                               value="<?= $jalur['tanggal_buka'] ? date('Y-m-d\TH:i', strtotime($jalur['tanggal_buka'])) : '' ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small mb-1">Tanggal Tutup</label>
                                        <input type="datetime-local" class="form-control" 
                                               name="tanggal_tutup[<?= $jalur['id'] ?>]" 
                                               value="<?= $jalur['tanggal_tutup'] ? date('Y-m-d\TH:i', strtotime($jalur['tanggal_tutup'])) : '' ?>">
                                    </div>
                                </div>
                                <hr>
                                <?php endforeach; ?>
                                <button type="submit" class="btn btn-primary">Simpan Pengaturan Tanggal</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pengaturan Kuota per Jalur</h5>
                        </div>
                        <div class="card-body">
                            <form action="process/update_kuota.php" method="POST">
                                <div class="row">
                                <?php foreach($jalur_list as $jalur): ?>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><?= htmlspecialchars($jalur['nama_jalur']) ?></label>
                                        <input type="number" class="form-control" 
                                               name="kuota[<?= $jalur['id'] ?>]" 
                                               value="<?= htmlspecialchars($jalur['kuota'] ?? '') ?>"
                                               min="0">
                                    </div>
                                <?php endforeach; ?>
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan Pengaturan Kuota</button>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pengaturan Tanggal Pengumuman per Jalur</h5>
                        </div>
                        <div class="card-body">
                            <form action="process/update_pengumuman.php" method="POST">
                                <div class="row">
                                <?php foreach($jalur_list as $jalur): ?>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label"><?= htmlspecialchars($jalur['nama_jalur']) ?></label>
                                        <input type="datetime-local" class="form-control" 
                                               name="tanggal_pengumuman[<?= $jalur['id'] ?>]" 
                                               value="<?= $jalur['tanggal_pengumuman'] ? date('Y-m-d\TH:i', strtotime($jalur['tanggal_pengumuman'])) : '' ?>">
                                    </div>
                                <?php endforeach; ?>
                                </div>
                                <button type="submit" class="btn btn-primary">Simpan Tanggal Pengumuman</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div class="modal fade" id="tambahJalurModal" tabindex="-1" aria-labelledby="tambahJalurModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="process/tambah_jalur.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tambahJalurModalLabel">Tambah Jalur Pendaftaran Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_jalur" class="form-label">Nama Jalur (contoh: Jalur Prestasi)</label>
                            <input type="text" class="form-control" id="nama_jalur" name="nama_jalur" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Simpan Jalur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>