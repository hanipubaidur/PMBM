<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/config/db.php';
require_once __DIR__.'/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$csrf_token = generateCsrfToken();
date_default_timezone_set('Asia/Jakarta');

$now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$current_date = $now->format('Y-m-d H:i:s');
$current_year = (int)$now->format('Y');
$current_month = (int)$now->format('m');
$academic_year_start = ($current_month > 6) ? $current_year + 1 : $current_year;
$academic_year_end = $academic_year_start + 1;

$query = "SELECT j.*, 
          DATE_FORMAT(j.tanggal_buka, '%d %b %Y') AS formatted_tanggal_buka, 
          DATE_FORMAT(j.tanggal_tutup, '%d %b %Y') AS formatted_tanggal_tutup,
          DATE_FORMAT(j.tanggal_pengumuman, '%d %b %Y') AS formatted_tanggal_pengumuman,
          j.tanggal_buka as real_tanggal_buka,
          j.tanggal_tutup as real_tanggal_tutup,
          (SELECT COUNT(*) FROM peserta WHERE jalur_id = j.id) as total_pendaftar
          FROM jalur_pendaftaran j 
          ORDER BY j.nama_jalur ASC";

$jalur_pendaftaran = [];
$stmt = $pdo->query($query);
$semua_jalur = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($semua_jalur as $jalur) {
    $jalur['sisa_kuota'] = $jalur['kuota'] - $jalur['total_pendaftar'];
    if ($current_date < $jalur['real_tanggal_buka']) {
        $jalur['status_jalur'] = 'Belum Dibuka';
    } elseif ($current_date >= $jalur['real_tanggal_tutup']) {
        $jalur['status_jalur'] = 'Ditutup';
    } elseif ($jalur['sisa_kuota'] <= 0) {
        $jalur['status_jalur'] = 'Kuota Penuh';
    } else {
        $jalur['status_jalur'] = 'Dibuka';
    }
    $jalur_pendaftaran[] = $jalur;
}

$active_jalur = array_filter($jalur_pendaftaran, function($jalur) use ($current_date) {
    return $jalur['status_jalur'] === 'Dibuka' && $jalur['sisa_kuota'] > 0;
});

$registration_message = isset($_SESSION['registration_success']) ? $_SESSION['registration_success'] : '';
unset($_SESSION['registration_success']);
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMBM MAN 1 Musi Rawas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { padding-top: 80px; }
        .navbar { z-index: 1030; }
        .accordion-button:not(.collapsed) { background-color: #f8f9fa; color: #0d6efd; }
        .accordion-item { border: none !important; border-radius: 1rem !important; overflow: hidden; margin-bottom: 10px; }
        .timeline-card { border-left: 4px solid #0d6efd; transition: transform 0.2s; }
        .timeline-card:hover { transform: translateX(5px); }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-white shadow-sm border-bottom fixed-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center text-primary" href="#">
                <img src="https://cdn.postimage.me/2026/04/11/logo-kemenag.png" alt="Logo" height="45" class="me-3 drop-shadow-sm">
                <div class="d-flex flex-column">
                    <span class="fw-bold text-dark" style="letter-spacing: 0.5px;">PMBM MAN 1 MUSI RAWAS</span>
                    <small class="text-primary fw-medium" style="font-size: 0.75rem;">Tahun Ajaran <?= $academic_year_start . '/' . $academic_year_end ?></small>
                </div>
            </a>
            <button class="navbar-toggler border-0 shadow-none text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list fs-1"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto fw-medium">
                    <li class="nav-item"><a class="nav-link text-dark active" href="index.php"><i class="bi bi-house text-primary me-1"></i> Beranda</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#alur"><i class="bi bi-diagram-3 text-primary me-1"></i> Alur</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#jadwal"><i class="bi bi-calendar-event text-primary me-1"></i> Jadwal</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="#faq"><i class="bi bi-question-circle text-primary me-1"></i> FAQ</a></li>
                </ul>
                <div class="d-flex gap-2 mt-3 mt-lg-0">
                    <?php if(isset($_SESSION['user'])): ?>
                        <a href="dashboard.php" class="btn btn-outline-primary rounded-pill px-4 fw-medium shadow-sm"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        <a href="profile.php" class="btn btn-success rounded-pill px-4 fw-medium shadow-sm"><i class="bi bi-person"></i> Profile</a>
                    <?php else: ?>
                        <button class="btn btn-outline-primary rounded-pill px-4 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-box-arrow-in-right"></i> Login</button>
                        <button class="btn btn-primary rounded-pill px-4 fw-medium shadow-sm" data-bs-toggle="modal" data-bs-target="#daftarModal"><i class="bi bi-person-plus"></i> Daftar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if($registration_message): ?>
            <div class="alert alert-success alert-dismissible fade show mt-4 mb-4 shadow-sm border-0 rounded-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><strong><?= htmlspecialchars($registration_message) ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-4 mb-4 shadow-sm border-0 rounded-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><strong><?= htmlspecialchars($error_message) ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <section class="mb-5 mt-4" id="alur">
            <div class="text-center mb-5">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill mb-2">Panduan</span>
                <h2 class="fw-bold">Alur Pendaftaran</h2>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary text-white d-inline-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 70px; height: 70px; border-radius: 20px; transform: rotate(5deg);">
                                <i class="bi bi-person-plus fs-2" style="transform: rotate(-5deg);"></i>
                            </div>
                            <h5 class="card-title fw-bold mb-3">1. Daftar Akun</h5>
                            <p class="card-text text-muted small">Buat akun dengan mengisi NISN dan data pribadi awal Anda.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body text-center p-4">
                            <div class="bg-success text-white d-inline-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 70px; height: 70px; border-radius: 20px; transform: rotate(5deg);">
                                <i class="bi bi-file-text fs-2" style="transform: rotate(-5deg);"></i>
                            </div>
                            <h5 class="card-title fw-bold mb-3">2. Lengkapi Data</h5>
                            <p class="card-text text-muted small">Isi formulir biodata siswa dan orangtua dengan lengkap & benar.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body text-center p-4">
                            <div class="bg-info text-white d-inline-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 70px; height: 70px; border-radius: 20px; transform: rotate(5deg);">
                                <i class="bi bi-upload fs-2" style="transform: rotate(-5deg);"></i>
                            </div>
                            <h5 class="card-title fw-bold mb-3">3. Upload Berkas</h5>
                            <p class="card-text text-muted small">Upload hasil scan/foto dokumen persyaratan yang diminta.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4">
                        <div class="card-body text-center p-4">
                            <div class="bg-warning text-white d-inline-flex justify-content-center align-items-center mb-4 shadow-sm" style="width: 70px; height: 70px; border-radius: 20px; transform: rotate(5deg);">
                                <i class="bi bi-clock-history fs-2" style="transform: rotate(-5deg);"></i>
                            </div>
                            <h5 class="card-title fw-bold mb-3">4. Pantau Hasil</h5>
                            <p class="card-text text-muted small">Cek status verifikasi dan hasil pengumuman di Dashboard.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-5 pt-4" id="jadwal">
            <div class="text-center mb-5">
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill mb-2">Informasi</span>
                <h2 class="fw-bold">Jadwal Pendaftaran</h2>
            </div>
            <div class="row">
                <div class="col-lg-8">
                    <div class="timeline pe-lg-4">
                        <?php foreach ($jalur_pendaftaran as $jalur): ?>
                        <div class="card mb-4 border-0 shadow-sm rounded-4 timeline-card">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light p-2 rounded-3 me-3 text-primary"><i class="bi bi-calendar3 fs-5"></i></div>
                                    <h5 class="card-title mb-0 fw-bold"><?= htmlspecialchars($jalur['nama_jalur']) ?></h5>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 h-100">
                                            <div class="small text-muted mb-1"><i class="bi bi-play-circle text-success me-1"></i> Masa Pendaftaran</div>
                                            <div class="fw-medium text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($jalur['formatted_tanggal_buka']) ?> <br>s/d<br> <?= htmlspecialchars($jalur['formatted_tanggal_tutup']) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 bg-light rounded-3 h-100">
                                            <div class="small text-muted mb-1"><i class="bi bi-megaphone text-info me-1"></i> Pengumuman</div>
                                            <div class="fw-medium text-dark mt-2 fs-6"><?= htmlspecialchars($jalur['formatted_tanggal_pengumuman']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                                    <span class="badge bg-<?= $jalur['status_jalur'] === 'Dibuka' ? 'success' : ($jalur['status_jalur'] === 'Kuota Penuh' ? 'danger' : ($jalur['status_jalur'] === 'Ditutup' ? 'secondary' : 'warning')) ?> px-3 py-2 rounded-pill">
                                        Status: <?= htmlspecialchars($jalur['status_jalur']) ?>
                                    </span>
                                    <?php if ($jalur['status_jalur'] !== 'Ditutup'): ?>
                                        <span class="badge bg-<?= $jalur['sisa_kuota'] <= 0 ? 'danger' : 'primary' ?> bg-opacity-10 text-<?= $jalur['sisa_kuota'] <= 0 ? 'danger' : 'primary' ?> px-3 py-2 rounded-pill border">
                                            Sisa Kuota: <?= htmlspecialchars($jalur['sisa_kuota']) ?> / <?= htmlspecialchars($jalur['kuota']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="sticky-top" style="top: 100px;">
                        
                        <div class="card border-0 shadow-sm mb-4 rounded-4">
                            <div class="card-header bg-primary text-white rounded-top-4 py-3">
                                <h5 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2"></i> Persyaratan Umum</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush rounded-bottom-4">
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Fotokopi/Scan Ijazah/SKL</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Fotokopi/Scan Akta Kelahiran</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Fotokopi/Scan Kartu Keluarga</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Pas Foto 3x4 (3 lembar)</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Raport Semester 1-5</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Fotokopi KIP/PIP (Jika Ada)</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4 rounded-4">
                            <div class="card-header bg-success text-white rounded-top-4 py-3">
                                <h5 class="card-title mb-0"><i class="bi bi-star-fill me-2"></i> Program Unggulan</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush rounded-bottom-4">
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Kelas Tahfidz</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Kelas Bahasa Asing</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Kelas Digital</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Pembiasaan Diri (Mengaji, Sholat Dhuha)</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Ekstrakulikuler</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mb-4 rounded-4">
                            <div class="card-header bg-success text-white rounded-top-4 py-3">
                                <h5 class="card-title mb-0"><i class="bi bi-star-fill me-2"></i> Fasilitas Yang Diterima</h5>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush rounded-bottom-4">
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Pendaftaran Gratis (Tidak Ada Biaya/Pungutan Lainnya)</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Mendapat Bantuan 3 Stel Seragam Madrasah (Batik, Muslim, Olahraga)</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Ruang Kelas Ber-AC</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Kelas Digital</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Lapangan Olahraga Yang Luas</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> UKS, Lab. Komputer, Lab IPA, Perpustakaan</li>
                                    <li class="list-group-item px-4 py-3"><i class="bi bi-caret-right-fill text-success me-2"></i> Kantin Sehat</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <section class="mb-5 mt-5 pt-4" id="faq">
            <div class="text-center mb-5">
                <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill mb-2">Bantuan</span>
                <h2 class="fw-bold">Tanya Jawab (FAQ)</h2>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion shadow-sm rounded-4" id="faqAccordion">
                        <div class="accordion-item shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Bagaimana cara mendaftar PMBM Online?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Klik tombol "Daftar" di atas, isi form dengan NISN valid. Setelah berhasil login, lengkapi seluruh form biodata (Siswa & Orang Tua) dan unggah dokumen pada Dashboard Anda.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Apa saja jalur pendaftaran yang tersedia?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Kami menyediakan jalur: <strong><?= implode(', ', array_column($jalur_pendaftaran, 'nama_jalur')) ?></strong>. Silakan pilih jalur yang paling sesuai pada saat mengisi form pendaftaran awal.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item shadow-sm">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed fw-medium" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Kapan dan bagaimana saya bisa melihat hasil kelulusan?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted">
                                    Hasil kelulusan akan diumumkan sesuai jadwal masing-masing jalur (lihat tabel jadwal). Anda dapat melihat hasilnya langsung dengan cara login dan membuka menu <strong>Pengumuman</strong> di dalam Dashboard Anda.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="bg-white border-top mt-5 py-5 shadow-sm">
        <div class="container">
            <div class="row gy-4">
                <div class="col-md-6">
                    <h5 class="fw-bold text-primary mb-3">MAN 1 MUSI RAWAS</h5>
                    <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger me-2"></i> Jalan Provinsi Rt. 06 Kel. Muara Kelingi, Kec. Muara Kelingi, Kab. Musi Rawas.</p>
                    <p class="text-muted small mb-2"><i class="bi bi-telephone-fill text-success me-2"></i> +62 813-6810-2412</p>
                    <p class="text-muted small mb-0"><i class="bi bi-envelope-fill text-primary me-2"></i> syafii.imam317@gmail.com</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5 class="fw-bold text-dark mb-3">Media Sosial</h5>
                    <div class="d-flex justify-content-md-end gap-2">
                        <a href="https://www.facebook.com/share/1AwDBZ3tTB/" class="btn btn-light rounded-circle text-primary shadow-sm"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/man1musirawas_sy" class="btn btn-light rounded-circle text-danger shadow-sm"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.man1mura.sch.id" class="btn btn-light rounded-circle text-info shadow-sm"><i class="bi bi-globe2"></i></a>
                        <a href="https://www.youtube.com/@imamsyafii-man1musirawas954" class="btn btn-light rounded-circle text-danger shadow-sm"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted small fw-medium">
                &copy; <?= date('Y') ?> PMBM MAN 1 Musi Rawas. All rights reserved.
            </div>
        </div>
    </footer>

    <div class="modal fade" id="daftarModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i>Daftar Akun Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/register_process.php" method="POST" id="formDaftar">
                    <div class="modal-body pt-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <?php $reg_tx_token = generateTransactionToken('register'); ?>
                        <input type="hidden" name="tx_token" id="regTxToken" value="<?= $reg_tx_token ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">Nama Lengkap</label>
                            <input type="text" class="form-control rounded-3" name="nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">NISN</label>
                            <input type="number" class="form-control rounded-3" name="nisn" required pattern="[0-9]{10}" title="NISN harus 10 digit angka">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">No. WhatsApp</label>
                            <input type="number" class="form-control rounded-3" name="no_wa_siswa" required pattern="^08\d{9,12}$" placeholder="08xxx">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">Jalur Pendaftaran</label>
                            <select class="form-select rounded-3" name="jalur_id" required>
                                <option value="">Pilih Jalur</option>
                                <?php foreach($active_jalur as $jalur): ?>
                                    <option value="<?= $jalur['id'] ?>"><?= htmlspecialchars($jalur['nama_jalur']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="registerPassword" required>
                                <button class="btn btn-light border" type="button" onclick="togglePassword('registerPassword')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">Konfirmasi Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirm_password" id="registerConfirmPassword" required>
                                <button class="btn btn-light border" type="button" onclick="togglePassword('registerConfirmPassword')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3 bg-light p-3 rounded-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-medium text-muted">Kode Keamanan (CAPTCHA)</span>
                                <div>
                                    <span id="captcha-timer" class="badge bg-danger">05:00</span>
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none" onclick="refreshCaptcha('captcha-code', 'captcha-timer', 'captcha-input', 'register')">
                                        <i class="bi bi-arrow-clockwise"></i> Ganti Kode
                                    </button>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold fs-5 text-primary" id="captcha-code" style="letter-spacing: 2px;"></span>
                                <input type="text" class="form-control" name="captcha" id="captcha-input" required pattern="[A-Za-z0-9]{6}" placeholder="Ketik kode di kiri">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">Daftar Sekarang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-arrow-in-right text-primary me-2"></i>Login Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/login_process.php" method="POST" id="formLogin">
                    <div class="modal-body pt-4">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <?php $login_tx_token = generateTransactionToken('login'); ?>
                        <input type="hidden" name="tx_token" id="loginTxToken" value="<?= $login_tx_token ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">NISN</label>
                            <input type="number" class="form-control rounded-3" name="nisn" required pattern="[0-9]{10}" placeholder="Masukkan NISN">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-muted">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="loginPassword" required placeholder="Masukkan Password">
                                <button class="btn btn-light border" type="button" onclick="togglePassword('loginPassword')"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="mb-3 bg-light p-3 rounded-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-medium text-muted">CAPTCHA</span>
                                <div>
                                    <span id="login-captcha-timer" class="badge bg-danger">05:00</span>
                                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 ms-1" onclick="refreshCaptcha('login-captcha-code', 'login-captcha-timer', 'login-captcha-input', 'login')"><i class="bi bi-arrow-clockwise"></i></button>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-primary" id="login-captcha-code" style="letter-spacing: 2px;"></span>
                                <input type="text" class="form-control" name="captcha" id="login-captcha-input" required pattern="[A-Za-z0-9]{6}">
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                            <label class="form-check-label small text-muted" for="rememberMe">Ingat Saya di perangkat ini</label>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-medium">Masuk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        <?= getJavaScript() ?>

        // AUTO TRIGGER CAPTCHA KETIKA MODAL DIBUKA
        document.addEventListener('DOMContentLoaded', function() {
            const daftarModal = document.getElementById('daftarModal');
            if (daftarModal) {
                daftarModal.addEventListener('show.bs.modal', function () {
                    const token = document.getElementById('regTxToken')?.value;
                    if(token) refreshCaptcha('captcha-code', 'captcha-timer', 'captcha-input', 'register', token);
                });
            }

            const loginModal = document.getElementById('loginModal');
            if (loginModal) {
                loginModal.addEventListener('show.bs.modal', function () {
                    const token = document.getElementById('loginTxToken')?.value;
                    if(token) refreshCaptcha('login-captcha-code', 'login-captcha-timer', 'login-captcha-input', 'login', token);
                });
            }

            // CEK JIKA ADA PARAMETER ERROR, OTOMATIS BUKA MODAL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('show')) {
                const target = urlParams.get('show');
                if (target === 'daftar' && daftarModal) {
                    new bootstrap.Modal(daftarModal).show();
                } else if (target === 'login' && loginModal) {
                    new bootstrap.Modal(loginModal).show();
                }
            }
        });
    </script>
</body>
</html>