<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("
    SELECT 
        COALESCE(COUNT(*), 0) as total_pendaftar,
        COALESCE(SUM(CASE WHEN v.status_verifikasi = 'Verified' THEN 1 ELSE 0 END), 0) as total_verified,
        COALESCE(SUM(CASE WHEN v.status_verifikasi = 'Rejected' THEN 1 ELSE 0 END), 0) as total_verif_rejected,
        COALESCE(SUM(CASE WHEN v.status_verifikasi IS NULL OR v.status_verifikasi = 'Pending' THEN 1 ELSE 0 END), 0) as total_pending,
        COALESCE(SUM(CASE WHEN p2.status_penerimaan = 'diterima' THEN 1 ELSE 0 END), 0) as total_accepted,
        COALESCE(SUM(CASE WHEN p2.status_penerimaan = 'ditolak' THEN 1 ELSE 0 END), 0) as total_tidak_diterima
    FROM peserta p
    LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id
    LEFT JOIN pengumuman p2 ON p.id = p2.peserta_id
");
$summary = $stmt->fetch();

$stmt = $pdo->query("
    SELECT jp.nama_jalur, COALESCE(COUNT(p.id), 0) as jumlah
    FROM jalur_pendaftaran jp
    LEFT JOIN peserta p ON jp.id = p.jalur_id
    GROUP BY jp.nama_jalur
");
$jalur_stats = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.*, jp.nama_jalur, v.status_verifikasi 
    FROM peserta p 
    LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
    LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
    ORDER BY p.created_at DESC 
    LIMIT 10
");
$latest_registrations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PMBM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; border: none; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">
    <?php include 'partials/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row g-0">
            <div class="col-md-2 sidebar-container">
                <?php include 'partials/admin_sidebar.php'; ?>
            </div>
            <div class="col-md-10 pt-4 px-4">
                <h2 class="mb-4 fw-bold text-dark">Dashboard Admin</h2>
                
                <div class="row g-3 mb-5">
                    <div class="col-md-4 col-xl-2">
                        <div class="card stat-card bg-white shadow-sm rounded-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="icon-box bg-primary bg-opacity-10 text-primary">
                                        <i class="bi bi-people-fill fs-4"></i>
                                    </div>
                                </div>
                                <h6 class="text-muted fw-normal mb-1">Total Pendaftar</h6>
                                <h3 class="fw-bold mb-0 text-dark"><?= $summary['total_pendaftar'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <div class="card stat-card bg-white shadow-sm rounded-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="icon-box bg-success bg-opacity-10 text-success">
                                        <i class="bi bi-check-circle-fill fs-4"></i>
                                    </div>
                                </div>
                                <h6 class="text-muted fw-normal mb-1">Terverifikasi</h6>
                                <h3 class="fw-bold mb-0 text-dark"><?= $summary['total_verified'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <div class="card stat-card bg-white shadow-sm rounded-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                        <i class="bi bi-hourglass-split fs-4"></i>
                                    </div>
                                </div>
                                <h6 class="text-muted fw-normal mb-1">Pending</h6>
                                <h3 class="fw-bold mb-0 text-dark"><?= $summary['total_pending'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <div class="card stat-card bg-white shadow-sm rounded-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="icon-box bg-danger bg-opacity-10 text-danger">
                                        <i class="bi bi-x-octagon-fill fs-4"></i>
                                    </div>
                                </div>
                                <h6 class="text-muted fw-normal mb-1">Ditolak (Verifikasi)</h6>
                                <h3 class="fw-bold mb-0 text-dark"><?= $summary['total_verif_rejected'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <div class="card stat-card bg-white shadow-sm rounded-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="icon-box bg-info bg-opacity-10 text-info">
                                        <i class="bi bi-award-fill fs-4"></i>
                                    </div>
                                </div>
                                <h6 class="text-muted fw-normal mb-1">Diterima Akhir</h6>
                                <h3 class="fw-bold mb-0 text-dark"><?= $summary['total_accepted'] ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-xl-2">
                        <div class="card stat-card bg-white shadow-sm rounded-4 h-100">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="icon-box bg-secondary bg-opacity-10 text-secondary">
                                        <i class="bi bi-x-circle-fill fs-4"></i>
                                    </div>
                                </div>
                                <h6 class="text-muted fw-normal mb-1">Tidak Diterima</h6>
                                <h3 class="fw-bold mb-0 text-dark"><?= $summary['total_tidak_diterima'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-xl-4">
                        <div class="card shadow-sm border-0 rounded-4 h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Statistik per Jalur</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush border-0">
                                    <?php 
                                    $colors = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];
                                    $i = 0;
                                    foreach($jalur_stats as $jalur): 
                                        $color = $colors[$i % count($colors)];
                                    ?>
                                    <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center border-0 mb-1">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-circle-fill text-<?= $color ?> me-3" style="font-size: 0.6rem;"></i>
                                            <span class="fw-medium text-secondary"><?= htmlspecialchars($jalur['nama_jalur']) ?></span>
                                        </div>
                                        <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> rounded-pill px-3 py-2 fs-6">
                                            <?= htmlspecialchars($jalur['jumlah']) ?>
                                        </span>
                                    </div>
                                    <?php $i++; endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card shadow-sm border-0 rounded-4 h-100">
                            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Pendaftar Terbaru</h5>
                                <a href="peserta.php" class="btn btn-sm btn-light text-primary fw-medium rounded-pill px-3">Lihat Semua</a>
                            </div>
                            <div class="card-body p-0 mt-3">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4 border-0 text-muted small py-3">Nama Peserta</th>
                                                <th class="border-0 text-muted small py-3">Jalur</th>
                                                <th class="border-0 text-muted small py-3">Status</th>
                                                <th class="pe-4 border-0 text-muted small py-3 text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-top-0">
                                            <?php foreach($latest_registrations as $reg): 
                                                $status = $reg['status_verifikasi'] ?? 'Pending';
                                                $statusClass = ['Pending' => 'warning', 'Verified' => 'success', 'Rejected' => 'danger'][$status];
                                            ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($reg['nama_lengkap']) ?></div>
                                                        <div class="text-muted small">NISN: <?= htmlspecialchars($reg['nisn']) ?></div>
                                                    </td>
                                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?= htmlspecialchars($reg['nama_jalur']) ?></span></td>
                                                    <td>
                                                        <span class="badge bg-<?= $statusClass ?> bg-opacity-10 text-<?= $statusClass ?> border border-<?= $statusClass ?>">
                                                            <i class="bi bi-circle-fill" style="font-size: 0.4rem; vertical-align: middle;"></i> <?= htmlspecialchars($status) ?>
                                                        </span>
                                                    </td>
                                                    <td class="pe-4 text-end">
                                                        <div class="d-flex gap-1 justify-content-end">
                                                            <a href="verifikasi.php?search=<?= urlencode($reg['nisn']) ?>" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Verifikasi">
                                                                <i class="bi bi-check2-circle text-primary"></i>
                                                            </a>
                                                            <?php if($status === 'Verified'): ?>
                                                                <a href="pengumuman.php?search=<?= urlencode($reg['nisn']) ?>" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Pengumuman">
                                                                    <i class="bi bi-megaphone text-success"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>