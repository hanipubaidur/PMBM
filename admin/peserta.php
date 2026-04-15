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
                p.*,
                v.status_verifikasi,
                v.catatan,
                jp.nama_jalur,
                DATE_FORMAT(p.created_at, '%d/%m/%Y') as tanggal_daftar
            FROM peserta p 
            LEFT JOIN jalur_pendaftaran jp ON jp.id = p.jalur_id
            LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
            WHERE 1=1";

    $params = array();
    if (!empty($search)) {
        $searchLike = '%' . strtolower($search) . '%';
        $query .= " AND (LOWER(p.nama_lengkap) LIKE :searchName 
                        OR p.nisn LIKE :searchNisn)";
        $params[':searchName'] = $searchLike;
        $params[':searchNisn'] = $searchLike;
    }

    $query .= " ORDER BY 
                CASE v.status_verifikasi
                    WHEN 'Verified' THEN 1 
                    WHEN 'Pending' THEN 2
                    WHEN 'rejected' THEN 3
                    ELSE 4 
                END,
                p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    
    if (!$stmt->execute()) {
        throw new PDOException("Failed to execute query");
    }
    
    $peserta_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cek nama yang mirip
    $similar_names = [];
    $name_count = [];
    $checked_pairs = [];

    foreach ($peserta_list as $peserta) {
        $nama = strtolower($peserta['nama_lengkap']);
        $name_count[$nama] = isset($name_count[$nama]) ? $name_count[$nama] + 1 : 1;
        
        foreach ($peserta_list as $other) {
            if ($peserta['id'] !== $other['id']) {
                $other_nama = strtolower($other['nama_lengkap']);
                $pair_key = $nama < $other_nama ? $nama . '|||' . $other_nama : $other_nama . '|||' . $nama;
                
                if (!isset($checked_pairs[$pair_key])) {
                    similar_text($nama, $other_nama, $percent);
                    if ($percent > 80) {
                        $similar_names[] = ['nama1' => ucwords($nama), 'nama2' => ucwords($other_nama)];
                    }
                    $checked_pairs[$pair_key] = true;
                }
            }
        }
    }

    $warning_messages = [];
    foreach ($similar_names as $pair) {
        $warning_messages[] = "Nama mirip: <strong>{$pair['nama1']}</strong> & <strong>{$pair['nama2']}</strong>";
    }
    foreach ($name_count as $name => $count) {
        if ($count > 1) {
            $warning_messages[] = "Ada $count peserta bernama: <strong>" . ucwords($name) . "</strong>";
        }
    }
    
    if (!empty($search)) {
        $count = count($peserta_list);
        $searchMessage = "Ditemukan {$count} hasil pencarian untuk \"{$search}\"";
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan saat mencari data.";
    $peserta_list = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peserta - PMBM</title>
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
                    <h2 class="mb-0 fw-bold text-dark">Data Peserta</h2>
                    <form class="d-flex shadow-sm rounded-pill overflow-hidden bg-white" role="search" method="GET" style="width: 350px; border: 1px solid #dee2e6;">
                        <input class="form-control border-0 shadow-none px-4" type="search" name="search" placeholder="Cari nama / NISN..." value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-primary px-4 rounded-pill m-1" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                </div>

                <?php if(!empty($search)): ?>
                    <div class="alert alert-info rounded-4 border-0 shadow-sm d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-info-circle-fill me-2"></i> <?= $searchMessage ?></span>
                        <a href="peserta.php" class="btn btn-sm btn-outline-info rounded-pill">Reset Pencarian</a>
                    </div>
                <?php endif; ?>

                <?php if(!empty($warning_messages)): ?>
                    <div class="alert alert-warning rounded-4 border-0 shadow-sm">
                        <h6 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Perhatian (Deteksi Duplikasi)</h6>
                        <ul class="mb-0 small">
                            <?php foreach($warning_messages as $message): ?>
                                <li><?= $message ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0 rounded-4 mb-5">
                    <div class="card-body p-0">
                        <?php if(empty($peserta_list)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                <?= empty($search) ? 'Belum ada data peserta yang mendaftar.' : 'Tidak ada hasil pencarian.' ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4 border-0 rounded-top-4 py-3 text-muted small">No</th>
                                            <th class="border-0 py-3 text-muted small">Peserta</th>
                                            <th class="border-0 py-3 text-muted small">Jalur</th>
                                            <th class="border-0 py-3 text-muted small">Status</th>
                                            <th class="pe-4 border-0 rounded-top-4 py-3 text-muted small text-end">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <?php foreach($peserta_list as $index => $peserta): 
                                            $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));
                                            $photo_path = "../File/{$folder_name}/{$peserta['file_photo']}";
                                            $img_src = (!empty($peserta['file_photo']) && file_exists($photo_path)) ? $photo_path : '../assets/img/default-profile.png';
                                            
                                            $status = $peserta['status_verifikasi'] ?? 'Pending';
                                            $statusClass = ['Pending' => 'warning', 'Verified' => 'success', 'Rejected' => 'danger'][$status];
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
                                                    <i class="bi bi-circle-fill" style="font-size: 0.4rem; vertical-align: middle;"></i> <?= htmlspecialchars($status) ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="profile_siswa.php?id=<?= $peserta['id'] ?>" class="btn btn-sm btn-light rounded-circle shadow-sm me-1" title="Lihat Detail">
                                                    <i class="bi bi-eye text-primary"></i>
                                                </a>
                                                <form action="process/delete_peserta.php" method="POST" class="d-inline-block" onsubmit="return confirmDelete(event)">
                                                    <input type="hidden" name="peserta_id" value="<?= $peserta['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-light rounded-circle shadow-sm" title="Hapus">
                                                        <i class="bi bi-trash text-danger"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    async function confirmDelete(e) {
        e.preventDefault();
        const result = await Swal.fire({
            title: 'Hapus Peserta?',
            text: 'Data peserta yang dihapus tidak dapat dikembalikan',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        });
        if (result.isConfirmed) e.target.submit();
    }
    window.addEventListener('load', function() {
        <?php if(isset($_SESSION['success'])): ?>
            Swal.fire({ title: 'Berhasil!', text: '<?= $_SESSION['success'] ?>', icon: 'success', timer: 1500, showConfirmButton: false });
        <?php unset($_SESSION['success']); endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            Swal.fire({ title: 'Error!', text: '<?= $_SESSION['error'] ?>', icon: 'error' });
        <?php unset($_SESSION['error']); endif; ?>
    });
    </script>
</body>
</html>