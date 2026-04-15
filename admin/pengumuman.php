<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../includes/admin_auth.php';

if(empty($_SESSION['admin'])) {
    $_SESSION['error'] = "Silakan login sebagai admin terlebih dahulu!";
    header("Location: login.php");
    exit;
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $query = "SELECT 
                p.*, jp.nama_jalur, v.status_verifikasi, pg.status_penerimaan,
                DATE_FORMAT(p.created_at, '%d/%m/%Y') as tanggal_daftar
            FROM peserta p 
            LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
            LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
            LEFT JOIN pengumuman pg ON p.id = pg.peserta_id
            WHERE v.status_verifikasi = 'Verified'";

    $params = array();
    if (!empty($search)) {
        $searchLike = '%' . strtolower($search) . '%';
        $query .= " AND (LOWER(p.nama_lengkap) LIKE :searchName OR LOWER(p.nisn) LIKE :searchNisn)";
        $params[':searchName'] = $searchLike;
        $params[':searchNisn'] = $searchLike;
    }

    $query .= " ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
    $stmt->execute();
    $peserta_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan saat mencari data";
    $peserta_list = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman - Admin PMBM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .table-hover tbody tr:hover { background-color: #f8f9fa; }
        .avatar-img { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; border: 2px solid #e9ecef; }
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0 fw-bold text-dark">Keputusan Penerimaan</h2>
                    <form class="d-flex shadow-sm rounded-pill overflow-hidden bg-white" role="search" method="GET" style="width: 350px; border: 1px solid #dee2e6;">
                        <input class="form-control border-0 shadow-none px-4" type="search" name="search" placeholder="Cari nama / NISN..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary px-4 rounded-pill m-1" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                </div>

                <div class="card shadow-sm border-0 rounded-4 mb-5">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 border-0 rounded-top-4 py-3 text-muted small">No</th>
                                        <th class="border-0 py-3 text-muted small">Kandidat Siswa (Verified)</th>
                                        <th class="border-0 py-3 text-muted small">Jalur</th>
                                        <th class="border-0 py-3 text-muted small">Hasil Akhir</th>
                                        <th class="pe-4 border-0 rounded-top-4 py-3 text-muted small text-end">Aksi Keputusan</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <?php foreach($peserta_list as $index => $peserta): 
                                        $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
                                        $photo_path = "../File/{$folder_name}/{$peserta['file_photo']}";
                                        $img_src = (!empty($peserta['file_photo']) && file_exists($photo_path)) ? $photo_path : '../assets/img/default-profile.png';
                                        
                                        $status = $peserta['status_penerimaan'] ?? 'Belum diputuskan';
                                        $badge_class = ['diterima' => 'success', 'ditolak' => 'danger', 'Belum diputuskan' => 'warning'][$status];
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center py-2">
                                                <img src="<?= htmlspecialchars($img_src) ?>" alt="Foto" class="avatar-img me-3 shadow-sm">
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($peserta['nama_lengkap']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.8rem;">NISN: <?= htmlspecialchars($peserta['nisn']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border"><?= htmlspecialchars($peserta['nama_jalur']) ?></span></td>
                                        <td>
                                            <span class="badge bg-<?= $badge_class ?> bg-opacity-10 text-<?= $badge_class ?> border border-<?= $badge_class ?>">
                                                <i class="bi bi-circle-fill" style="font-size: 0.4rem; vertical-align: middle;"></i> <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="process/pengumuman_process.php?id=<?= $peserta['id'] ?>&status=diterima" 
                                               class="btn btn-sm <?= $status === 'diterima' ? 'btn-success disabled' : 'btn-outline-success' ?> rounded-pill px-3 shadow-sm me-1"
                                               onclick="return confirmStatus(event, 'terima')">
                                                <i class="bi bi-check2-all"></i> Terima
                                            </a>
                                            <a href="process/pengumuman_process.php?id=<?= $peserta['id'] ?>&status=ditolak" 
                                               class="btn btn-sm <?= $status === 'ditolak' ? 'btn-danger disabled' : 'btn-outline-danger' ?> rounded-pill px-3 shadow-sm"
                                               onclick="return confirmStatus(event, 'tolak')">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    async function confirmStatus(e, action) {
        e.preventDefault();
        const result = await Swal.fire({
            title: `${action === 'terima' ? 'Terima' : 'Tolak'} Peserta?`,
            text: `Yakin ingin memberikan status ${action === 'terima' ? 'DITERIMA' : 'DITOLAK'}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'terima' ? '#198754' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Ya, ${action === 'terima' ? 'Terima' : 'Tolak'}!`,
            cancelButtonText: 'Batal'
        });
        if (result.isConfirmed) window.location.href = e.target.href;
    }
    window.addEventListener('load', function() {
        <?php if(isset($_SESSION['success'])): ?>
            Swal.fire({ title: 'Berhasil!', text: '<?= $_SESSION['success'] ?>', icon: 'success', timer: 2000, showConfirmButton: false });
        <?php unset($_SESSION['success']); endif; ?>
    });
    </script>
</body>
</html>