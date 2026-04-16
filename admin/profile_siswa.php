<?php
require_once '../config/db.php';
require_once '../includes/admin_auth.php';

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception("ID peserta tidak valid");
    }

    // Get peserta data with prestasi
    $stmt = $pdo->prepare("
        SELECT p.*, j.nama_jalur 
        FROM peserta p 
        LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $peserta = $stmt->fetch();

    if (!$peserta) {
        throw new Exception("Data peserta tidak ditemukan");
    }

    // Gunakan format folder yang konsisten
    $folder_name = str_replace(' ', '_', strtolower($peserta['nama_lengkap']));

    // Get prestasi dengan error handling
    $stmt = $pdo->prepare("SELECT * FROM prestasi WHERE peserta_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_GET['id']]);
    $prestasi = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Database Error in profile_siswa.php: ".$e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem saat mengambil data";
    header("Location: peserta.php");
    exit;
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: peserta.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil - <?= htmlspecialchars($peserta['nama_lengkap']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .label-text { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .value-text { font-weight: 500; font-size: 1rem; margin-bottom: 1rem; color: #212529; }
        .doc-card { transition: transform 0.2s; border: 1px solid #e9ecef; }
        .doc-card:hover { transform: translateY(-3px); box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important; }
    </style>
</head>
<body class="bg-light">
    <?php include 'partials/admin_header.php'; ?>
    
    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-md-3">
                <div style="position: sticky; top: 80px;">
                    <?php include 'partials/admin_sidebar.php'; ?>
                    
                    <div class="card shadow-sm mb-4 mt-4 border-0 rounded-4">
                        <div class="card-body text-center py-4">
                            <?php 
                            $photo_path = "../File/{$folder_name}/{$peserta['file_photo']}";
                            $timestamp = file_exists($photo_path) ? '?v=' . filemtime($photo_path) . '-' . uniqid() : '';
                            
                            if(!empty($peserta['file_photo']) && file_exists($photo_path)): ?>
                                <img src="<?= htmlspecialchars($photo_path . $timestamp) ?>" 
                                     class="img-fluid rounded-circle mb-3 shadow-sm" 
                                     style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #fff;" 
                                     alt="Foto Peserta"
                                     onerror="this.src='../assets/img/default-profile.png';">
                            <?php else: ?>
                                <div class="mb-3 d-inline-block p-4 rounded-circle bg-light text-secondary">
                                    <i class="bi bi-person-fill" style="font-size: 80px;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h5 class="fw-bold text-primary mb-1"><?= htmlspecialchars($peserta['nama_lengkap']) ?></h5>
                            <p class="text-muted small mb-2">NISN: <?= htmlspecialchars($peserta['nisn']) ?></p>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-3">
                                <?= htmlspecialchars($peserta['nama_jalur']) ?>
                            </span>

                            <?php 
                            $stmt = $pdo->prepare("SELECT status_verifikasi FROM verifikasi_peserta WHERE peserta_id = ?");
                            $stmt->execute([$_GET['id']]);
                            $verifikasi = $stmt->fetch();
                            $status = $verifikasi ? $verifikasi['status_verifikasi'] : 'Belum Diverifikasi';
                            
                            if ($status === 'rejected') {
                                echo "<div class='alert alert-danger py-2 mt-2 mb-0 small'><i class='bi bi-x-circle-fill'></i> Ditolak - Periksa Kelengkapan</div>";
                            } elseif ($status === 'Verified') {
                                echo "<div class='alert alert-success py-2 mt-2 mb-0 small'><i class='bi bi-check-circle-fill'></i> Terverifikasi</div>";
                            } else {
                                echo "<div class='alert alert-warning py-2 mt-2 mb-0 small'><i class='bi bi-hourglass-split'></i> Belum Diverifikasi</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card shadow-sm mb-4 border-0 rounded-4">
                    <div class="card-header bg-primary text-white rounded-top-4 py-3">
                        <h5 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Biodata Siswa</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="label-text">NIK</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['nik'] ?? '-') ?></div>
                                
                                <div class="label-text">Tempat, Tanggal Lahir</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['tempat_lahir'] ?? '-') ?>, <?= htmlspecialchars($peserta['tanggal_lahir'] ?? '-') ?></div>
                                
                                <div class="label-text">Agama</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['agama_siswa'] ?? '-') ?></div>
                                
                                <div class="label-text">Jenis Kelamin</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['jenis_kelamin'] ?? '-') ?></div>
                            </div>
                            <div class="col-md-6">
                                <div class="label-text">Asal Sekolah</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['asal_sekolah'] ?? '-') ?></div>
                                
                                <div class="label-text">Tahun Lulus</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['tahun_lulus'] ?? '-') ?></div>
                                
                                <div class="label-text">No. WhatsApp</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['no_wa_siswa'] ?? '-') ?></div>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-light rounded-3">
                            <h6 class="text-primary mb-2"><i class="bi bi-geo-alt me-2"></i>Alamat Siswa</h6>
                            <p class="mb-0">
                                <?= htmlspecialchars($peserta['alamat_siswa_jalan'] ?? '-') ?><br>
                                <span class="text-muted small">
                                    RT <?= htmlspecialchars($peserta['alamat_siswa_rt'] ?? '-') ?> / RW <?= htmlspecialchars($peserta['alamat_siswa_rw'] ?? '-') ?>, 
                                    Kel. <?= htmlspecialchars($peserta['alamat_siswa_kelurahan'] ?? '-') ?>, 
                                    Kec. <?= htmlspecialchars($peserta['alamat_siswa_kecamatan'] ?? '-') ?><br>
                                    <?= htmlspecialchars($peserta['alamat_siswa_kota'] ?? '-') ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-0 rounded-4">
                    <div class="card-header bg-success text-white rounded-top-4 py-3">
                        <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Biodata Orangtua</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="label-text">No. Kartu Keluarga (KK)</div>
                                <div class="value-text fs-5 text-success"><?= htmlspecialchars($peserta['no_kk'] ?? '-') ?></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6 class="text-success mb-3 border-bottom pb-2">Data Ayah</h6>
                                <div class="label-text">Nama Ayah</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['nama_ayah'] ?? '-') ?></div>
                                
                                <div class="label-text">Pekerjaan</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['pekerjaan_ayah'] ?? '-') ?></div>
                                
                                <div class="label-text">Agama</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['agama_ayah'] ?? '-') ?></div>
                            </div>
                            
                            <div class="col-md-6 ps-md-4">
                                <h6 class="text-success mb-3 border-bottom pb-2">Data Ibu</h6>
                                <div class="label-text">Nama Ibu</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['nama_ibu'] ?? '-') ?></div>
                                
                                <div class="label-text">Pekerjaan</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['pekerjaan_ibu'] ?? '-') ?></div>
                                
                                <div class="label-text">Agama</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['agama_ibu'] ?? '-') ?></div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="p-3 bg-light rounded-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                    <div>
                                        <h6 class="text-success mb-2"><i class="bi bi-house-door me-2"></i>Alamat Orangtua</h6>
                                        <p class="mb-0 small">
                                            <?= htmlspecialchars($peserta['alamat_ortu_jalan'] ?? '-') ?>, 
                                            RT <?= htmlspecialchars($peserta['alamat_ortu_rt'] ?? '-') ?>/RW <?= htmlspecialchars($peserta['alamat_ortu_rw'] ?? '-') ?>, 
                                            Kel. <?= htmlspecialchars($peserta['alamat_ortu_kelurahan'] ?? '-') ?>, 
                                            Kec. <?= htmlspecialchars($peserta['alamat_ortu_kecamatan'] ?? '-') ?>, 
                                            <?= htmlspecialchars($peserta['alamat_ortu_kota'] ?? '-') ?>
                                        </p>
                                    </div>
                                    <div class="mt-3 mt-md-0 text-md-end border-start ps-md-3">
                                        <div class="label-text">No. Telepon</div>
                                        <div class="fw-bold text-dark"><i class="bi bi-telephone-fill text-success me-1"></i> <?= htmlspecialchars($peserta['no_telp_ortu'] ?? '-') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-0 rounded-4">
                    <div class="card-header bg-info text-white rounded-top-4 py-3">
                        <h5 class="mb-0"><i class="bi bi-info-circle-fill me-2"></i>Informasi & Prestasi</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4 bg-light p-3 rounded-3 mx-0">
                            <div class="col-md-6 ps-md-4">
                                <div class="label-text">Jarak ke Sekolah</div>
                                <div class="value-text text-info mb-0"><?= htmlspecialchars($peserta['jarak_ke_sekolah'] ?? '-') ?> km</div>
                            </div>
                        </div>

                        <h6 class="text-info border-bottom pb-2 mb-3"><i class="bi bi-trophy-fill me-2"></i>Daftar Prestasi</h6>
                        <?php if(empty($prestasi)): ?>
                            <div class="text-center py-4 bg-light rounded-3 text-muted">
                                <i class="bi bi-stars fs-3 d-block mb-2"></i>
                                Belum ada prestasi yang ditambahkan
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Bidang Prestasi</th>
                                            <th>Judul / Nama Prestasi</th>
                                            <th>Peringkat</th>
                                            <th>Tingkat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($prestasi as $p): ?>
                                            <tr>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['bidang_prestasi']) ?></span></td>
                                                <td class="fw-medium"><?= htmlspecialchars($p['judul_prestasi']) ?></td>
                                                <td><?= htmlspecialchars($p['peringkat']) ?></td>
                                                <td><?= htmlspecialchars($p['tingkat']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-4 border-0 rounded-4">
                    <div class="card-header bg-secondary text-white rounded-top-4 py-3">
                        <h5 class="mb-0"><i class="bi bi-folder-fill me-2"></i>Berkas Dokumen & Raport</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <h6 class="mb-3 text-secondary border-bottom pb-2">Dokumen Utama</h6>
                        <div class="row g-3 mb-5">
                            <div class="col-md-4">
                                <div class="card h-100 doc-card shadow-sm bg-light border-0">
                                    <div class="card-body text-center d-flex flex-column align-items-center">
                                        <i class="bi bi-file-earmark-pdf fs-1 text-danger mb-2"></i>
                                        <h6 class="card-title">Kartu Keluarga</h6>
                                        <?php if(!empty($peserta['file_kk']) && file_exists("../File/{$folder_name}/{$peserta['file_kk']}")): ?>
                                            <p class="small text-muted text-truncate w-100 mb-3" title="<?= htmlspecialchars($peserta['file_kk']) ?>"><?= htmlspecialchars($peserta['file_kk']) ?></p>
                                            <a href="download_admin.php?type=kk&id=<?= $peserta['id'] ?>" class="btn btn-sm btn-outline-danger mt-auto w-100" target="_blank">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mt-auto py-2 w-100">Belum Diunggah</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card h-100 doc-card shadow-sm bg-light border-0">
                                    <div class="card-body text-center d-flex flex-column align-items-center">
                                        <i class="bi bi-file-earmark-text fs-1 text-primary mb-2"></i>
                                        <h6 class="card-title">Akte Kelahiran</h6>
                                        <?php if(!empty($peserta['file_akte']) && file_exists("../File/{$folder_name}/{$peserta['file_akte']}")): ?>
                                            <p class="small text-muted text-truncate w-100 mb-3" title="<?= htmlspecialchars($peserta['file_akte']) ?>"><?= htmlspecialchars($peserta['file_akte']) ?></p>
                                            <a href="download_admin.php?type=akte&id=<?= $peserta['id'] ?>" class="btn btn-sm btn-outline-primary mt-auto w-100" target="_blank">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mt-auto py-2 w-100">Belum Diunggah</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card h-100 doc-card shadow-sm bg-light border-0">
                                    <div class="card-body text-center d-flex flex-column align-items-center">
                                        <h6 class="card-title mb-2">Pas Foto</h6>
                                        <?php if(!empty($peserta['file_photo']) && file_exists("../File/{$folder_name}/{$peserta['file_photo']}")): ?>
                                            <img src="<?= htmlspecialchars($photo_path . $timestamp) ?>" class="img-thumbnail shadow-sm mb-2" style="width: 80px; height: 100px; object-fit: cover;" alt="Preview Foto">
                                            <p class="small text-muted text-truncate w-100 mb-3" title="<?= htmlspecialchars($peserta['file_photo']) ?>"><?= htmlspecialchars($peserta['file_photo']) ?></p>
                                            <a href="download_admin.php?type=photo&id=<?= $peserta['id'] ?>" class="btn btn-sm btn-outline-success mt-auto w-100" target="_blank">
                                                <i class="bi bi-download"></i> Download
                                            </a>
                                        <?php else: ?>
                                            <i class="bi bi-file-earmark-image fs-1 text-success mb-2"></i>
                                            <span class="badge bg-secondary mt-auto py-2 w-100">Belum Diunggah</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="mb-3 text-secondary border-bottom pb-2">Raport Semester 1 - 5</h6>
                        <div class="row g-3">
                            <?php for($i = 1; $i <= 5; $i++): 
                                $field_name = "file_raport_" . $i;
                            ?>
                                <div class="col-md-4">
                                    <div class="card h-100 doc-card shadow-sm border-0 bg-white" style="border-left: 4px solid #6c757d !important;">
                                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                                            <div>
                                                <h6 class="mb-1 text-dark">Semester <?= $i ?></h6>
                                                <?php if(!empty($peserta[$field_name]) && file_exists("../File/{$folder_name}/{$peserta[$field_name]}")): ?>
                                                    <small class="text-success"><i class="bi bi-check-circle-fill"></i> Diunggah</small>
                                                <?php else: ?>
                                                    <small class="text-danger"><i class="bi bi-x-circle-fill"></i> Kosong</small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if(!empty($peserta[$field_name]) && file_exists("../File/{$folder_name}/{$peserta[$field_name]}")): ?>
                                                <a href="download_admin.php?type=raport&sem=<?= $i ?>&id=<?= $peserta['id'] ?>" class="btn btn-sm btn-secondary rounded-circle" target="_blank" title="Download Raport Sem <?= $i ?>">
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light rounded-circle text-muted" disabled>
                                                    <i class="bi bi-dash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                    </div>
                </div>

                <div class="text-end mb-5">
                    <a href="peserta.php" class="btn btn-secondary px-4 rounded-pill shadow-sm">
                        <i class="bi bi-arrow-left me-2"></i> Kembali ke Daftar Peserta
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>