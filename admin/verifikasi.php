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
                p.id, p.nisn, p.nama_lengkap, p.created_at,
                p.file_kk, p.file_akte, p.file_photo,
                p.file_raport_1, p.file_raport_2, p.file_raport_3, p.file_raport_4, p.file_raport_5,
                jp.nama_jalur, v.status_verifikasi, v.catatan,
                DATE_FORMAT(p.created_at, '%d/%m/%Y') as tanggal_daftar
            FROM peserta p 
            LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
            LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
            WHERE 1=1";

    $params = array();
    if (!empty($search)) {
        $searchLike = '%' . strtolower($search) . '%';
        $query .= " AND (LOWER(p.nama_lengkap) LIKE :searchName OR p.nisn LIKE :searchNisn)";
        $params[':searchName'] = $searchLike;
        $params[':searchNisn'] = $searchLike;
    }

    $query .= " ORDER BY 
                CASE v.status_verifikasi WHEN 'Verified' THEN 1 WHEN 'Pending' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END,
                p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
    $stmt->execute();
    $pending_verifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($pending_verifications as &$peserta) {
        $missing_files = [];
        $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
        if(empty($peserta['file_kk']) || !file_exists("../File/{$folder_name}/{$peserta['file_kk']}")) $missing_files[] = "Kartu Keluarga";
        if(empty($peserta['file_akte']) || !file_exists("../File/{$folder_name}/{$peserta['file_akte']}")) $missing_files[] = "Akte Kelahiran";
        if(empty($peserta['file_photo']) || !file_exists("../File/{$folder_name}/{$peserta['file_photo']}")) $missing_files[] = "Pas Foto";
        
        for($i = 1; $i <= 5; $i++) {
            $field_name = "file_raport_" . $i;
            if(empty($peserta[$field_name]) || !file_exists("../File/{$folder_name}/{$peserta[$field_name]}")) {
                $missing_files[] = "Raport Semester $i";
            }
        }
        $peserta['auto_note'] = !empty($missing_files) ? "Silakan lengkapi berkas:\n- " . implode("\n- ", $missing_files) : "";
    }
    unset($peserta);

} catch (PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan saat mengambil data.";
    $pending_verifications = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Peserta - PMBM</title>
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
                    <h2 class="mb-0 fw-bold text-dark">Verifikasi Peserta</h2>
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
                                        <th class="border-0 py-3 text-muted small">Peserta</th>
                                        <th class="border-0 py-3 text-muted small">Jalur</th>
                                        <th class="border-0 py-3 text-muted small">Status</th>
                                        <th class="border-0 py-3 text-muted small">Catatan</th>
                                        <th class="pe-4 border-0 rounded-top-4 py-3 text-muted small text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <?php foreach($pending_verifications as $index => $peserta): 
                                        $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
                                        $photo_path = "../File/{$folder_name}/{$peserta['file_photo']}";
                                        $img_src = (!empty($peserta['file_photo']) && file_exists($photo_path)) ? $photo_path : '../assets/img/default-profile.png';
                                        
                                        $statusClass = 'warning'; $statusText = 'Pending';
                                        if ($peserta['status_verifikasi'] === 'Verified') { $statusClass = 'success'; $statusText = 'Terverifikasi'; }
                                        elseif ($peserta['status_verifikasi'] === null) { $statusClass = 'secondary'; $statusText = 'Belum Diverifikasi'; }
                                        elseif ($peserta['status_verifikasi'] === 'rejected') { $statusClass = 'danger'; $statusText = 'Ditolak'; }
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
                                            <span class="badge bg-<?= $statusClass ?> bg-opacity-10 text-<?= $statusClass ?> border border-<?= $statusClass ?>">
                                                <i class="bi bi-circle-fill" style="font-size: 0.4rem; vertical-align: middle;"></i> <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-truncate text-muted small" style="max-width: 200px;">
                                                <?= !empty($peserta['catatan']) ? '<i class="bi bi-chat-text-fill text-warning me-1"></i>' . htmlspecialchars($peserta['catatan']) : '-' ?>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <button type="button" class="btn btn-sm btn-<?= $peserta['status_verifikasi'] === 'Verified' ? 'light border' : 'primary' ?> rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#verifikasiModal<?= $peserta['id'] ?>">
                                                <i class="bi <?= $peserta['status_verifikasi'] === 'Verified' ? 'bi-pencil-square' : 'bi-check2-circle' ?>"></i> <?= $peserta['status_verifikasi'] === 'Verified' ? 'Edit' : 'Verifikasi' ?>
                                            </button>

                                            <div class="modal fade text-start" id="verifikasiModal<?= $peserta['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 rounded-4 shadow">
                                                        <div class="modal-header border-bottom-0 pb-0">
                                                            <h5 class="modal-title fw-bold">Verifikasi Berkas</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="process/verifikasi_process.php" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?= $peserta['id'] ?>">
                                                                <div class="d-flex align-items-center p-3 bg-light rounded-3 mb-3">
                                                                    <img src="<?= htmlspecialchars($img_src) ?>" class="avatar-img me-3">
                                                                    <div>
                                                                        <div class="fw-bold"><?= htmlspecialchars($peserta['nama_lengkap']) ?></div>
                                                                        <div class="small text-muted">NISN: <?= htmlspecialchars($peserta['nisn']) ?></div>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">Status Verifikasi</label>
                                                                    <select class="form-select rounded-3" name="status_verifikasi" id="statusVerifikasi<?= $peserta['id'] ?>" onchange="togglePengumumanStatus(<?= $peserta['id'] ?>)" required>
                                                                        <option value="Pending" <?= $peserta['status_verifikasi'] === 'Pending' ? 'selected' : '' ?>>Pending (Menunggu)</option>
                                                                        <option value="Verified" <?= $peserta['status_verifikasi'] === 'Verified' ? 'selected' : '' ?>>Verified (Diterima Berkas)</option>
                                                                        <option value="Rejected" <?= $peserta['status_verifikasi'] === 'Rejected' ? 'selected' : '' ?>>Rejected (Ditolak / Berkas Kurang)</option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3" id="pengumumanStatus<?= $peserta['id'] ?>" style="display: <?= $peserta['status_verifikasi'] === 'Verified' ? 'block' : 'none' ?>;">
                                                                    <label class="form-label text-muted small">Status Penerimaan Akhir (Opsional)</label>
                                                                    <select class="form-select rounded-3" name="status_penerimaan">
                                                                        <option value="">Pilih Status</option>
                                                                        <option value="Diterima">Diterima di Sekolah</option>
                                                                        <option value="Ditolak">Tidak Diterima</option>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">Catatan Admin</label>
                                                                    <?php if (!empty($peserta['auto_note'])): ?>
                                                                        <div class="alert alert-danger small py-2 mb-2"><i class="bi bi-exclamation-circle-fill"></i> System: <?= nl2br(htmlspecialchars($peserta['auto_note'])) ?></div>
                                                                    <?php endif; ?>
                                                                    <textarea class="form-control rounded-3" name="catatan" rows="3" placeholder="Tulis alasan jika ditolak..."><?= htmlspecialchars($peserta['catatan'] ?? $peserta['auto_note'] ?? '') ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-top-0 pt-0">
                                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Data</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePengumumanStatus(id) {
            const statusVerifikasi = document.getElementById('statusVerifikasi' + id);
            const pengumumanStatus = document.getElementById('pengumumanStatus' + id);
            pengumumanStatus.style.display = statusVerifikasi.value === 'Verified' ? 'block' : 'none';
        }
        window.addEventListener('load', function() {
            <?php if(isset($_SESSION['verifikasi_success'])): ?>
                Swal.fire({ title: 'Berhasil!', text: '<?= $_SESSION['verifikasi_success'] ?>', icon: 'success', timer: 1500, showConfirmButton: false });
                <?php unset($_SESSION['verifikasi_success']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>