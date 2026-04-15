<?php
session_start();
$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$current_year = (int)$now->format('Y');
$current_month = (int)$now->format('m');
$academic_year_start = ($current_month > 6) ? $current_year + 1 : $current_year;
$academic_year_end = $academic_year_start + 1;

require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/functions.php';

if(empty($_SESSION['user'])) {
    $_SESSION['error'] = "Silakan login terlebih dahulu!";
    header("Location: index.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT p.*, j.nama_jalur, v.status_verifikasi, v.catatan
                           FROM peserta p 
                           LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id
                           LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id 
                           WHERE p.id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $peserta = $stmt->fetch();

    $missing_files = [];
    $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));

    $required_files = ['file_kk' => 'Kartu Keluarga', 'file_akte' => 'Akte Kelahiran', 'file_photo' => 'Pas Foto'];
    foreach($required_files as $field => $label) {
        if(empty($peserta[$field]) || !file_exists("File/{$folder_name}/{$peserta[$field]}")) $missing_files[] = $label;
    }
    for($i = 1; $i <= 5; $i++) {
        $field_name = "file_raport_" . $i;
        if(empty($peserta[$field_name]) || !file_exists("File/{$folder_name}/{$peserta[$field_name]}")) $missing_files[] = "Raport Semester " . $i;
    }

    $stmt = $pdo->prepare("SELECT * FROM prestasi WHERE peserta_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user']['id']]);
    $prestasi = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, nama_jalur FROM jalur_pendaftaran");
    $stmt->execute();
    $jalur_list = $stmt->fetchAll();

    $csrf_token = generateCsrfToken();
} catch(PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan sistem";
    header("Location: index.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Token keamanan tidak valid!";
        header("Location: dashboard.php");
        exit;
    }
    ob_start();
    try {
        if(isset($_POST['update_profile'])) {
            $fields = [
                'tempat_lahir', 'tanggal_lahir', 'agama_siswa', 'no_wa_siswa', 'asal_sekolah', 'nik',
                'alamat_siswa_jalan', 'alamat_siswa_rt', 'alamat_siswa_rw', 'tahun_lulus', 'jalur_id',
                'alamat_siswa_kelurahan', 'alamat_siswa_kecamatan', 'alamat_siswa_kota',
                'nama_ayah', 'nama_ibu', 'pekerjaan_ayah', 'pekerjaan_ibu',
                'agama_ayah', 'agama_ibu', 'no_telp_ortu', 'jenis_kelamin',
                'alamat_ortu_jalan', 'alamat_ortu_rt', 'alamat_ortu_rw',
                'alamat_ortu_kelurahan', 'alamat_ortu_kecamatan', 'alamat_ortu_kota',
                'tempat_lahir_ayah', 'tempat_lahir_ibu', 'tanggal_lahir_ayah', 'tanggal_lahir_ibu',
                'jarak_ke_sekolah'
            ];
            $updates = []; $params = [];
            foreach($fields as $field) {
                if(isset($_POST[$field])) {
                    $updates[] = "$field = ?";
                    $params[] = $_POST[$field] === '' ? null : $_POST[$field];
                }
            }
            $params[] = $_SESSION['user']['id'];
            if(!empty($updates)) {
                $sql = "UPDATE peserta SET " . implode(', ', $updates) . " WHERE id = ?";
                $pdo->prepare($sql)->execute($params);
            }
            $_SESSION['success'] = "Data berhasil diperbarui!";
        }

        if(isset($_POST['add_prestasi'])) {
            $stmt = $pdo->prepare("INSERT INTO prestasi (peserta_id, bidang_prestasi, judul_prestasi, peringkat, tingkat) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user']['id'], $_POST['bidang_prestasi'], $_POST['judul_prestasi'], $_POST['peringkat'], $_POST['tingkat']]);
            $_SESSION['success'] = "Prestasi berhasil ditambahkan!";
        }

        if(isset($_POST['delete_prestasi'])) {
            $stmt = $pdo->prepare("DELETE FROM prestasi WHERE id = ? AND peserta_id = ?");
            $stmt->execute([$_POST['prestasi_id'], $_SESSION['user']['id']]);
            $_SESSION['success'] = "Prestasi berhasil dihapus!";
        }
        ob_clean(); header("Location: dashboard.php"); exit;
    } catch(PDOException $e) {
        ob_clean(); $_SESSION['error'] = "Terjadi kesalahan sistem"; header("Location: dashboard.php"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Data - PMBM MAN 1 Musi Rawas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { padding-top: 90px; background-color: #f8f9fa; }
        .doc-card { transition: transform 0.2s; border: 1px solid #e9ecef; }
        .doc-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-white shadow-sm border-bottom fixed-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary" href="index.php">
                <img src="https://cdn.postimage.me/2026/04/11/logo-kemenag.png" alt="Logo" height="40" class="me-2 drop-shadow-sm">
                <div class="d-flex flex-column">
                    <span class="fw-bold" style="letter-spacing: 0.5px;">PMBM MAN 1 MUSI RAWAS</span>
                    <small class="text-muted fw-medium" style="font-size: 0.75rem;">Tahun Ajaran <?= $academic_year_start . '/' . $academic_year_end ?></small>
                </div>
            </a>
            <button class="navbar-toggler border-primary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto"></ul>
                <div class="d-flex gap-2">
                    <a href="profile.php" class="btn btn-outline-success rounded-pill px-4 fw-medium shadow-sm"><i class="bi bi-person me-1"></i> Profile</a>
                    <a href="logout.php" class="btn btn-danger rounded-pill px-4 fw-medium shadow-sm"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if(!empty($missing_files)): ?>
            <div class="alert alert-danger shadow-sm border-0 rounded-4 d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-octagon-fill fs-3 me-3"></i>
                <div>
                    <strong class="d-block mb-1">Perhatian! Dokumen Belum Lengkap</strong>
                    <span class="small">Mohon segera upload: <?= implode(', ', $missing_files) ?> di bagian bawah halaman.</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4 border-0 rounded-4 bg-white overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle-fill me-2"></i>Status Akun</h5>
                            <?php 
                            $status = $peserta['status_verifikasi'] ?? 'Pending';
                            if ($status === 'Verified') echo "<span class='badge bg-success px-3 py-2 rounded-pill'><i class='bi bi-check-circle-fill'></i> Terverifikasi</span>";
                            elseif ($status === 'rejected') echo "<span class='badge bg-danger px-3 py-2 rounded-pill'><i class='bi bi-x-circle-fill'></i> Ditolak</span>";
                            else echo "<span class='badge bg-warning text-dark px-3 py-2 rounded-pill'><i class='bi bi-hourglass-split'></i> Belum Diverifikasi</span>";
                            ?>
                        </div>
                        <div class="row g-3 bg-light p-3 rounded-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Nama Lengkap</div>
                                <div class="fw-bold"><?= htmlspecialchars($peserta['nama_lengkap']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Jalur Pilihan</div>
                                <div class="fw-bold text-primary"><?= htmlspecialchars($peserta['nama_jalur']) ?></div>
                            </div>
                        </div>
                        <?php if(!empty($peserta['catatan'])): ?>
                            <div class="alert alert-warning mt-3 mb-0 small rounded-3 border-0">
                                <strong><i class="bi bi-chat-left-dots-fill me-1"></i> Catatan Admin:</strong><br>
                                <?= nl2br(htmlspecialchars($peserta['catatan'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" id="uploadForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="card shadow-sm mb-4 border-0 rounded-4">
                        <div class="card-header bg-primary text-white rounded-top-4 py-3">
                            <h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Edit Biodata Siswa</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">NISN</label>
                                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($peserta['nisn']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">NIK Siswa</label>
                                    <input type="text" class="form-control" name="nik" value="<?= htmlspecialchars($peserta['nik'] ?? '') ?>" pattern="[0-9]{16}" title="16 Digit NIK" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Tempat Lahir</label>
                                    <input type="text" class="form-control" name="tempat_lahir" value="<?= htmlspecialchars($peserta['tempat_lahir'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Tanggal Lahir</label>
                                    <input type="date" class="form-control" name="tanggal_lahir" value="<?= htmlspecialchars($peserta['tanggal_lahir'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">No. WhatsApp</label>
                                    <input type="tel" class="form-control" name="no_wa_siswa" value="<?= htmlspecialchars($peserta['no_wa_siswa'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Asal Sekolah (SMP/MTs)</label>
                                    <input type="text" class="form-control" name="asal_sekolah" value="<?= htmlspecialchars($peserta['asal_sekolah']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Tahun Lulus</label>
                                    <input type="number" class="form-control" name="tahun_lulus" value="<?= htmlspecialchars($peserta['tahun_lulus'] ?? '') ?>" min="2020" max="2030" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Jalur Pendaftaran</label>
                                    <?php if($peserta['jalur_id']): ?>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($peserta['nama_jalur']) ?>" readonly>
                                        <input type="hidden" name="jalur_id" value="<?= $peserta['jalur_id'] ?>">
                                    <?php else: ?>
                                        <select class="form-select" name="jalur_id" required>
                                            <?php foreach($jalur_list as $jalur): ?>
                                                <option value="<?= $jalur['id'] ?>"><?= htmlspecialchars($jalur['nama_jalur']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Agama</label>
                                    <select class="form-select" name="agama_siswa" required>
                                        <?php foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $a): ?>
                                            <option value="<?= $a ?>" <?= ($peserta['agama_siswa'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Jenis Kelamin</label>
                                    <select class="form-select" name="jenis_kelamin" required>
                                        <option value="Laki-Laki" <?= ($peserta['jenis_kelamin'] ?? '') === 'Laki-Laki' ? 'selected' : '' ?>>Laki-Laki</option>
                                        <option value="Perempuan" <?= ($peserta['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="text-primary mb-3">Detail Alamat Tempat Tinggal</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small text-muted">Jalan / Nama Dusun</label>
                                    <input type="text" class="form-control" name="alamat_siswa_jalan" value="<?= htmlspecialchars($peserta['alamat_siswa_jalan'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">RT</label>
                                    <input type="number" class="form-control" name="alamat_siswa_rt" value="<?= htmlspecialchars($peserta['alamat_siswa_rt'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">RW</label>
                                    <input type="number" class="form-control" name="alamat_siswa_rw" value="<?= htmlspecialchars($peserta['alamat_siswa_rw'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Desa / Kelurahan</label>
                                    <input type="text" class="form-control" name="alamat_siswa_kelurahan" value="<?= htmlspecialchars($peserta['alamat_siswa_kelurahan'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Kecamatan</label>
                                    <input type="text" class="form-control" name="alamat_siswa_kecamatan" value="<?= htmlspecialchars($peserta['alamat_siswa_kecamatan'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Kabupaten / Kota</label>
                                    <input type="text" class="form-control" name="alamat_siswa_kota" value="<?= htmlspecialchars($peserta['alamat_siswa_kota'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Jarak ke Sekolah (km) <span class="text-info">*Opsional</span></label>
                                    <input type="number" step="0.01" class="form-control" name="jarak_ke_sekolah" value="<?= htmlspecialchars($peserta['jarak_ke_sekolah'] ?? '') ?>" placeholder="Misal: 2.5">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0 rounded-4">
                        <div class="card-header bg-success text-white rounded-top-4 py-3">
                            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Edit Data Orang Tua</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Nomor Kartu Keluarga (KK)</label>
                                <input type="number" class="form-control border-success text-success fw-bold" name="no_kk" value="<?= htmlspecialchars($peserta['no_kk'] ?? '') ?>" required>
                            </div>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <h6 class="text-success mb-3 border-bottom pb-2">Identitas Ayah</h6>
                                        <div class="mb-2"><label class="form-label small text-muted">Nama Lengkap</label><input type="text" class="form-control form-control-sm" name="nama_ayah" value="<?= htmlspecialchars($peserta['nama_ayah'] ?? '') ?>" required></div>
                                        <div class="mb-2"><label class="form-label small text-muted">Tempat Lahir</label><input type="text" class="form-control form-control-sm" name="tempat_lahir_ayah" value="<?= htmlspecialchars($peserta['tempat_lahir_ayah'] ?? '') ?>" required></div>
                                        <div class="mb-2"><label class="form-label small text-muted">Tanggal Lahir</label><input type="date" class="form-control form-control-sm" name="tanggal_lahir_ayah" value="<?= htmlspecialchars($peserta['tanggal_lahir_ayah'] ?? '') ?>" required></div>
                                        <div class="mb-2"><label class="form-label small text-muted">Pekerjaan</label><input type="text" class="form-control form-control-sm" name="pekerjaan_ayah" value="<?= htmlspecialchars($peserta['pekerjaan_ayah'] ?? '') ?>" required></div>
                                        <div class="mb-0"><label class="form-label small text-muted">Agama</label>
                                            <select class="form-select form-select-sm" name="agama_ayah" required>
                                                <?php foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $a): ?><option value="<?= $a ?>" <?= ($peserta['agama_ayah'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 bg-light rounded-3 border">
                                        <h6 class="text-success mb-3 border-bottom pb-2">Identitas Ibu</h6>
                                        <div class="mb-2"><label class="form-label small text-muted">Nama Lengkap</label><input type="text" class="form-control form-control-sm" name="nama_ibu" value="<?= htmlspecialchars($peserta['nama_ibu'] ?? '') ?>" required></div>
                                        <div class="mb-2"><label class="form-label small text-muted">Tempat Lahir</label><input type="text" class="form-control form-control-sm" name="tempat_lahir_ibu" value="<?= htmlspecialchars($peserta['tempat_lahir_ibu'] ?? '') ?>" required></div>
                                        <div class="mb-2"><label class="form-label small text-muted">Tanggal Lahir</label><input type="date" class="form-control form-control-sm" name="tanggal_lahir_ibu" value="<?= htmlspecialchars($peserta['tanggal_lahir_ibu'] ?? '') ?>" required></div>
                                        <div class="mb-2"><label class="form-label small text-muted">Pekerjaan</label><input type="text" class="form-control form-control-sm" name="pekerjaan_ibu" value="<?= htmlspecialchars($peserta['pekerjaan_ibu'] ?? '') ?>" required></div>
                                        <div class="mb-0"><label class="form-label small text-muted">Agama</label>
                                            <select class="form-select form-select-sm" name="agama_ibu" required>
                                                <?php foreach(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'] as $a): ?><option value="<?= $a ?>" <?= ($peserta['agama_ibu'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option><?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h6 class="text-success mb-3">Alamat & Kontak Orang Tua</h6>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">No. Handphone Aktif (Bisa Dihubungi)</label>
                                    <input type="tel" class="form-control w-50" name="no_telp_ortu" value="<?= htmlspecialchars($peserta['no_telp_ortu'] ?? '') ?>" required>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12"><label class="form-label small text-muted">Jalan / Dusun</label><input type="text" class="form-control" name="alamat_ortu_jalan" value="<?= htmlspecialchars($peserta['alamat_ortu_jalan'] ?? '') ?>" required></div>
                                    <div class="col-md-6"><label class="form-label small text-muted">RT</label><input type="number" class="form-control" name="alamat_ortu_rt" value="<?= htmlspecialchars($peserta['alamat_ortu_rt'] ?? '') ?>" required></div>
                                    <div class="col-md-6"><label class="form-label small text-muted">RW</label><input type="number" class="form-control" name="alamat_ortu_rw" value="<?= htmlspecialchars($peserta['alamat_ortu_rw'] ?? '') ?>" required></div>
                                    <div class="col-md-4"><label class="form-label small text-muted">Kelurahan</label><input type="text" class="form-control" name="alamat_ortu_kelurahan" value="<?= htmlspecialchars($peserta['alamat_ortu_kelurahan'] ?? '') ?>" required></div>
                                    <div class="col-md-4"><label class="form-label small text-muted">Kecamatan</label><input type="text" class="form-control" name="alamat_ortu_kecamatan" value="<?= htmlspecialchars($peserta['alamat_ortu_kecamatan'] ?? '') ?>" required></div>
                                    <div class="col-md-4"><label class="form-label small text-muted">Kab/Kota</label><input type="text" class="form-control" name="alamat_ortu_kota" value="<?= htmlspecialchars($peserta['alamat_ortu_kota'] ?? '') ?>" required></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4 border-0 rounded-4">
                        <div class="card-header bg-secondary text-white rounded-top-4 py-3">
                            <h5 class="mb-0"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload Berkas Persyaratan</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="alert alert-light border shadow-sm small mb-4">
                                <i class="bi bi-info-circle-fill text-info me-2"></i><strong>Panduan Upload:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    <li>Format dokumen: <strong>PDF</strong> (Maksimal 5MB)</li>
                                    <li>Format pas foto: <strong>JPG/PNG</strong> (Otomatis di-compress jika > 5MB)</li>
                                </ul>
                            </div>

                            <h6 class="mb-3 border-bottom pb-2">Dokumen Utama</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="card h-100 doc-card shadow-sm bg-white border">
                                        <div class="card-body text-center d-flex flex-column align-items-center">
                                            <h6 class="card-title fw-bold text-dark">Kartu Keluarga</h6>
                                            <?php if(!empty($peserta['file_kk']) && file_exists("File/{$folder_name}/{$peserta['file_kk']}")): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-1 mb-2"></i>
                                                <p class="small text-muted text-truncate w-100 mb-2">Tersimpan</p>
                                            <?php else: ?><i class="bi bi-x-circle text-danger fs-1 mb-2"></i><?php endif; ?>
                                            <input type="file" class="form-control form-control-sm file-upload mt-auto w-100" name="file_kk" accept="application/pdf" data-type="kk">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 doc-card shadow-sm bg-white border">
                                        <div class="card-body text-center d-flex flex-column align-items-center">
                                            <h6 class="card-title fw-bold text-dark">Akte Kelahiran</h6>
                                            <?php if(!empty($peserta['file_akte']) && file_exists("File/{$folder_name}/{$peserta['file_akte']}")): ?>
                                                <i class="bi bi-check-circle-fill text-success fs-1 mb-2"></i>
                                                <p class="small text-muted text-truncate w-100 mb-2">Tersimpan</p>
                                            <?php else: ?><i class="bi bi-x-circle text-danger fs-1 mb-2"></i><?php endif; ?>
                                            <input type="file" class="form-control form-control-sm file-upload mt-auto w-100" name="file_akte" accept="application/pdf" data-type="akte">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100 doc-card shadow-sm bg-white border">
                                        <div class="card-body text-center d-flex flex-column align-items-center">
                                            <h6 class="card-title fw-bold text-dark">Pas Foto</h6>
                                            <?php if(!empty($peserta['file_photo']) && file_exists("File/{$folder_name}/{$peserta['file_photo']}")): ?>
                                                <img src="File/<?= $folder_name ?>/<?= $peserta['file_photo'] ?>?v=<?= time() ?>" class="img-thumbnail shadow-sm mb-2" style="width: 50px; height: 60px; object-fit: cover;">
                                            <?php else: ?><i class="bi bi-person-bounding-box text-danger fs-1 mb-2"></i><?php endif; ?>
                                            <input type="file" class="form-control form-control-sm file-upload mt-auto w-100" name="file_photo" accept="image/jpeg,image/png" data-type="photo">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-3 border-bottom pb-2">Nilai Raport (Semester 1 - 5)</h6>
                            <div class="row g-2">
                                <?php for($i = 1; $i <= 5; $i++): $field_name = "file_raport_" . $i; ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card doc-card shadow-sm border bg-light">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <h6 class="mb-0 fw-bold">Semester <?= $i ?></h6>
                                                    <?= (!empty($peserta[$field_name]) && file_exists("File/{$folder_name}/{$peserta[$field_name]}")) ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-exclamation-circle text-danger"></i>' ?>
                                                </div>
                                                <input type="file" class="form-control form-control-sm file-upload" name="<?= $field_name ?>" accept="application/pdf" data-type="raport" data-semester="<?= $i ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <div id="uploadStatus" class="alert alert-info mt-4" style="display: none;">
                                <div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-3"></div><strong>Sedang menyimpan dan mengunggah data... Mohon tunggu.</strong></div>
                            </div>

                            <div class="mt-4 pt-3 border-top text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm" id="submitBtn">
                                    <i class="bi bi-save me-2"></i> Simpan Seluruh Perubahan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4 border-0 rounded-4 sticky-top" style="top: 100px;">
                    <div class="card-header bg-info text-white rounded-top-4 py-3">
                        <h5 class="mb-0"><i class="bi bi-trophy-fill me-2"></i>Tambah Prestasi</h5>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <form method="post" class="mb-4" onsubmit="return handleAddPrestasi(event)">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="add_prestasi" value="1">
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-medium mb-1">Bidang Prestasi</label>
                                <select class="form-select rounded-3" name="bidang_prestasi" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="Prestasi Akademik">Prestasi Akademik</option>
                                    <option value="Prestasi Olahraga">Prestasi Olahraga</option>
                                    <option value="Prestasi Seni">Prestasi Seni</option>
                                    <option value="Prestasi Non-Akademik">Prestasi Non-Akademik</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-medium mb-1">Judul / Nama Lomba</label>
                                <input type="text" class="form-control rounded-3" name="judul_prestasi" placeholder="Misal: Juara 1 KSM" required>
                            </div>
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <label class="small text-muted fw-medium mb-1">Peringkat</label>
                                    <input type="text" class="form-control rounded-3" name="peringkat" placeholder="Misal: Emas" required>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted fw-medium mb-1">Tingkat</label>
                                    <select class="form-select rounded-3" name="tingkat" required>
                                        <option value="">Pilih</option>
                                        <option value="Kecamatan">Kecamatan</option>
                                        <option value="Kabupaten">Kabupaten</option>
                                        <option value="Provinsi">Provinsi</option>
                                        <option value="Nasional">Nasional</option>
                                        <option value="Internasional">Internasional</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info text-white w-100 rounded-pill fw-bold shadow-sm"><i class="bi bi-plus-circle me-1"></i> Simpan Prestasi</button>
                        </form>

                        <h6 class="border-bottom pb-2 text-info fw-bold">Riwayat Prestasi Disimpan</h6>
                        <?php if(empty($prestasi)): ?>
                            <div class="text-center py-3 text-muted small border rounded-3 bg-white"><i class="bi bi-stars d-block fs-3 mb-1"></i>Belum ada data prestasi</div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach($prestasi as $p): ?>
                                <div class="p-3 bg-white border rounded-3 shadow-sm position-relative">
                                    <form method="post" class="position-absolute top-0 end-0 p-2">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="delete_prestasi" value="1">
                                        <input type="hidden" name="prestasi_id" value="<?= $p['id'] ?>">
                                        <button type="button" class="btn btn-sm text-danger p-0 border-0" onclick="confirmDelete(this)"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                    <span class="badge bg-secondary mb-1"><?= htmlspecialchars($p['bidang_prestasi']) ?></span>
                                    <div class="fw-bold text-dark small leading-tight mb-1"><?= htmlspecialchars($p['judul_prestasi']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($p['peringkat']) ?> - Tingkat <?= htmlspecialchars($p['tingkat']) ?></div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmDelete(button) {
        Swal.fire({
            title: 'Hapus Prestasi?', text: 'Data tidak dapat dikembalikan!', icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus!'
        }).then((result) => { if (result.isConfirmed) button.closest('form').submit(); });
    }
    function handleAddPrestasi(e) {
        e.preventDefault();
        fetch(window.location.href, { method: 'POST', body: new FormData(e.target) })
        .then(() => Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Prestasi ditambahkan', timer: 1500, showConfirmButton: false }).then(() => window.location.reload()))
        .catch(() => Swal.fire({ icon: 'error', title: 'Error!' }));
        return false;
    }
    </script>

    <footer class="bg-white border-top mt-5 py-4 shadow-sm">
        <div class="container text-center text-muted small">
            &copy; <?= date('Y') ?> PMBM MAN 1 Musi Rawas. All rights reserved.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/browser-image-compression/2.0.0/browser-image-compression.min.js"></script>
    <script>
        async function compressImage(file) {
            try { return await imageCompression(file, { maxSizeMB: 1, maxWidthOrHeight: 800, useWebWorker: true }); }
            catch (e) { return file; }
        }

        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn'), status = document.getElementById('uploadStatus');
            btn.disabled = true; status.style.display = 'block';
            
            try {
                const res = await fetch('process/upload_handler.php', { method: 'POST', body: new FormData(this) });
                const json = await res.json();
                if (json.success) {
                    await Swal.fire({ icon: 'success', title: 'Tersimpan!', text: 'Data & berkas berhasil diupdate.', timer: 2000, showConfirmButton: false });
                    window.location.href = 'profile.php';
                } else throw new Error(json.message);
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: err.message || 'Terjadi kesalahan sistem.' });
            } finally {
                btn.disabled = false; status.style.display = 'none';
            }
        });

        document.querySelectorAll('.file-upload').forEach(input => {
            input.addEventListener('change', async function() {
                if(this.files.length > 0) {
                    const file = this.files[0], maxSize = 5 * 1024 * 1024, isPhoto = this.name === 'file_photo';
                    if(file.size > maxSize) {
                        if(isPhoto) {
                            const comp = await compressImage(file);
                            if(comp.size <= maxSize) { const dt = new DataTransfer(); dt.items.add(comp); this.files = dt.files; return; }
                        }
                        Swal.fire({ icon: 'error', title: 'Terlalu Besar', text: 'Maksimal 5MB!' }); this.value = ''; return;
                    }
                    if(isPhoto && !['image/jpeg', 'image/png'].includes(file.type)) { Swal.fire({ icon:'error', title:'Format Salah', text:'Foto harus JPG/PNG' }); this.value = ''; }
                    else if(!isPhoto && file.type !== 'application/pdf') { Swal.fire({ icon:'error', title:'Format Salah', text:'Dokumen harus PDF' }); this.value = ''; }
                }
            });
        });

        window.addEventListener('load', function() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('success')) {
                Swal.fire({ title: 'Berhasil!', text: 'Data disimpan.', icon: 'success', timer: 1500, showConfirmButton: false }).then(() => window.history.replaceState({}, '', 'dashboard.php'));
            } else if (params.has('error')) {
                Swal.fire({ title: 'Error!', text: 'Gagal menyimpan.', icon: 'error' }).then(() => window.history.replaceState({}, '', 'dashboard.php'));
            }
        });
    </script>
</body>
</html>