<?php
require_once __DIR__.'/config/db.php';
require_once 'includes/functions.php';

if(empty($_SESSION['user'])) {
    $_SESSION['error'] = "Silakan login terlebih dahulu!";
    header("Location: index.php");
    exit;
}

try {
    // Get peserta and announcement date data
    $stmt = $pdo->prepare("
        SELECT p.*, v.status_verifikasi, pg.status_penerimaan, j.tanggal_pengumuman 
        FROM peserta p 
        LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id
        LEFT JOIN pengumuman pg ON p.id = pg.peserta_id 
        LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id
        WHERE p.id = ?
    ");
    $stmt->execute([$_SESSION['user']['id']]);
    $peserta = $stmt->fetch();

    if (!$peserta) {
        throw new Exception("Data peserta tidak ditemukan");
    }

    $tanggal_pengumuman = $peserta['tanggal_pengumuman'];
    
    if (!$tanggal_pengumuman) {
        throw new Exception("Tanggal pengumuman belum diatur untuk jalur pendaftaran Anda");
    }

    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $current_year = (int)$now->format('Y');
    $current_month = (int)$now->format('m');
    $academic_year_start = ($current_month > 6) ? $current_year + 1 : $current_year;
    $academic_year_end = $academic_year_start + 1;
    
    $announcement_time = new DateTime($tanggal_pengumuman, new DateTimeZone('Asia/Jakarta'));
    
    // Perbandingan menggunakan timestamp lengkap (Hari, Jam, Menit, Detik)
    $showAnnouncement = $now->getTimestamp() >= $announcement_time->getTimestamp();
    
    // Format tanggal untuk JavaScript Countdown (Format: Jan 5, 2026 15:37:25)
    $js_countdown_date = $announcement_time->format('M d, Y H:i:s');

} catch(Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Seleksi - PMBM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { padding-top: 90px; background-color: #f8f9fa; }
        .label-text { font-size: 0.85rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .value-text { font-weight: 600; font-size: 1.1rem; color: #212529; }
        /* Style tambahan untuk timer */
        .timer-box { background: white; padding: 15px 10px; border-radius: 1rem; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075); border: 1px solid #e9ecef; }
        .timer-number { font-size: 2rem; font-weight: 800; color: #0d6efd; line-height: 1; margin-bottom: 5px; }
        .timer-label { font-size: 0.8rem; color: #6c757d; font-weight: 600; text-transform: uppercase; }
        @media (max-width: 576px) { .timer-number { font-size: 1.5rem; } .timer-label { font-size: 0.7rem; } }
    </style>
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
                <div class="d-flex gap-2 ms-auto mt-3 mt-lg-0">
                    <a href="profile.php" class="btn btn-outline-success rounded-pill px-4 fw-medium shadow-sm"><i class="bi bi-person me-1"></i> Profile</a>
                    <a href="logout.php" class="btn btn-danger rounded-pill px-4 fw-medium shadow-sm"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark">Hasil Seleksi PMBM</h2>
                    <p class="text-muted">Cek status penerimaan Anda secara berkala.</p>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white border-bottom p-4">
                        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-person-vcard me-2"></i> Identitas Calon Siswa</h5>
                    </div>
                    <div class="card-body p-4 bg-light">
                        <div class="row g-4">
                            <div class="col-md-6 border-end-md">
                                <div class="label-text">Nama Lengkap</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['nama_lengkap'] ?? '') ?></div>
                                <div class="label-text mt-3">NISN</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['nisn'] ?? '') ?></div>
                            </div>
                            <div class="col-md-6 ps-md-4">
                                <div class="label-text">Asal Sekolah</div>
                                <div class="value-text"><?= htmlspecialchars($peserta['asal_sekolah'] ?? '') ?></div>
                                <div class="label-text mt-3">Tanggal Lahir</div>
                                <div class="value-text"><?= !empty($peserta['tanggal_lahir']) ? date('d F Y', strtotime($peserta['tanggal_lahir'])) : '-' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger shadow-sm rounded-4 border-0 text-center p-4">
                        <i class="bi bi-exclamation-triangle-fill fs-1 d-block mb-3 text-danger"></i>
                        <h5 class="fw-bold"><?= $error_message ?></h5>
                    </div>
                
                <?php elseif(!$showAnnouncement): ?>
                    <div class="card shadow-sm rounded-4 border-0 text-center p-4 p-md-5 bg-white">
                        <i class="bi bi-hourglass-split text-warning mb-3" style="font-size: 3rem; animation: swing 2s infinite ease-in-out;"></i>
                        <h4 class="fw-bold text-dark mb-2">Harap Bersabar...</h4>
                        <p class="text-muted mb-4">Pengumuman hasil seleksi akan dibuka secara otomatis dalam:</p>
                        
                        <div class="row g-2 g-md-3 justify-content-center px-2">
                            <div class="col-3 col-sm-2">
                                <div class="timer-box">
                                    <div class="timer-number" id="cd-days">00</div>
                                    <div class="timer-label">Hari</div>
                                </div>
                            </div>
                            <div class="col-3 col-sm-2">
                                <div class="timer-box">
                                    <div class="timer-number" id="cd-hours">00</div>
                                    <div class="timer-label">Jam</div>
                                </div>
                            </div>
                            <div class="col-3 col-sm-2">
                                <div class="timer-box">
                                    <div class="timer-number" id="cd-minutes">00</div>
                                    <div class="timer-label">Menit</div>
                                </div>
                            </div>
                            <div class="col-3 col-sm-2">
                                <div class="timer-box">
                                    <div class="timer-number text-danger" id="cd-seconds">00</div>
                                    <div class="timer-label text-danger">Detik</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <small class="text-muted"><i class="bi bi-calendar-event me-1"></i> Terjadwal: <strong class="text-dark"><?= $announcement_time->format('d F Y - H:i') ?> WIB</strong></small>
                        </div>
                    </div>

                    <script>
                        // Ambil waktu dari PHP yang diconvert ke JS format
                        const countDownDate = new Date("<?= $js_countdown_date ?>").getTime();

                        // Update hitungan mundur setiap 1 detik
                        const x = setInterval(function() {
                            // Ambil waktu hari ini & jam sekarang
                            const now = new Date().getTime();

                            // Cari selisih waktu
                            const distance = countDownDate - now;

                            // Kalkulasi waktu untuk hari, jam, menit, detik
                            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                            // Output ke elemen HTML
                            document.getElementById("cd-days").innerHTML = days.toString().padStart(2, '0');
                            document.getElementById("cd-hours").innerHTML = hours.toString().padStart(2, '0');
                            document.getElementById("cd-minutes").innerHTML = minutes.toString().padStart(2, '0');
                            document.getElementById("cd-seconds").innerHTML = seconds.toString().padStart(2, '0');

                            // Jika hitungan mundur selesai, reload halaman
                            if (distance < 0) {
                                clearInterval(x);
                                document.getElementById("cd-days").innerHTML = "00";
                                document.getElementById("cd-hours").innerHTML = "00";
                                document.getElementById("cd-minutes").innerHTML = "00";
                                document.getElementById("cd-seconds").innerHTML = "00";
                                
                                // Tampilkan tulisan memuat & reload
                                Swal.fire({
                                    title: 'Waktu Pengumuman Tiba!',
                                    text: 'Memuat hasil seleksi Anda...',
                                    icon: 'info',
                                    showConfirmButton: false,
                                    allowOutsideClick: false,
                                    timerProgressBar: true,
                                    didOpen: () => { Swal.showLoading(); }
                                });
                                setTimeout(() => { window.location.reload(); }, 2000);
                            }
                        }, 1000);
                    </script>

                    <style>
                        @keyframes swing {
                            0% { transform: rotate(0deg); }
                            25% { transform: rotate(15deg); }
                            50% { transform: rotate(0deg); }
                            75% { transform: rotate(-15deg); }
                            100% { transform: rotate(0deg); }
                        }
                    </style>

                <?php else: ?>
                    <?php if($peserta['status_penerimaan'] === 'diterima'): ?>
                        <div class="card shadow border-0 rounded-4 bg-success text-white overflow-hidden text-center position-relative">
                            <div class="position-absolute opacity-10 w-100 h-100" style="background: radial-gradient(circle, white 10%, transparent 10%), radial-gradient(circle, white 10%, transparent 10%); background-size: 20px 20px; background-position: 0 0, 10px 10px; top:0; left:0;"></div>
                            
                            <div class="card-body p-5 position-relative z-1">
                                <i class="bi bi-patch-check-fill text-warning drop-shadow" style="font-size: 4.5rem;"></i>
                                <h2 class="fw-bold mt-3 mb-1">SELAMAT!</h2>
                                <h4 class="mb-4 fw-light">Anda dinyatakan <span class="fw-bold px-2 py-1 bg-white text-success rounded-3 mx-1">DITERIMA</span></h4>
                                <p class="mb-0 fs-5">Sebagai Calon Peserta Didik Baru<br><strong>MAN 1 MUSI RAWAS</strong></p>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 rounded-4 mt-4">
                            <div class="card-body p-4">
                                <h5 class="fw-bold text-success mb-4"><i class="bi bi-list-check me-2"></i>Langkah Selanjutnya:</h5>
                                
                                <div class="d-flex mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; min-width: 35px;">1</div>
                                    <div>
                                        <strong class="d-block text-dark">Pertemuan Wali & Daftar Ulang</strong> 
                                        Hadir bersama orang tua/wali ke MAN 1 Musi Rawas untuk melakukan Daftar Ulang sekaligus Pertemuan Wali pada tanggal <strong>1 - 7 Juli 2026</strong>.
                                    </div>
                                </div>
                                
                                <div class="d-flex mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 35px; height: 35px; min-width: 35px;">2</div>
                                    <div>
                                        <strong class="d-block text-dark">Siapkan Berkas Fisik</strong>
                                        Membawa berkas pendaftaran berikut saat jadwal Daftar Ulang di atas:
                                        <ul class="text-muted small mt-1 mb-0 ps-3">
                                            <li>Surat Keterangan Lulus (SKL) Asli</li>
                                            <li>Fotokopi KK & Akte Kelahiran (masing-masing 2 lembar)</li>
                                            <li>Pas Foto 3x4 (4 lembar)</li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="alert alert-warning border-0 rounded-3 small mt-4 mb-0">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Penting:</strong> Apabila tidak hadir melakukan daftar ulang bersama wali sesuai jadwal, maka kelulusan dianggap <strong>GUGUR</strong>.
                                </div>
                            </div>
                        </div>

                    <?php elseif($peserta['status_penerimaan'] === 'ditolak'): ?>
                        <div class="card shadow border-0 rounded-4 bg-danger text-white overflow-hidden text-center">
                            <div class="card-body p-5">
                                <i class="bi bi-envelope-paper-x-fill text-white opacity-75" style="font-size: 4rem;"></i>
                                <h3 class="fw-bold mt-4 mb-2">Mohon Maaf</h3>
                                <p class="fs-5 mb-0 fw-light">Anda dinyatakan <strong>TIDAK DITERIMA</strong><br>pada seleksi tahun ini.</p>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <p class="text-muted">Jangan berkecil hati. Terus semangat dan semoga sukses meraih mimpi di sekolah impian lainnya!</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary shadow-sm rounded-4 border-0 text-center p-5 bg-white">
                            <div class="spinner-border text-secondary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                            <h4 class="fw-bold text-dark">Menunggu Keputusan</h4>
                            <p class="text-muted mb-0">Proses seleksi sedang berlangsung. Hasil akhir penerimaan belum diputuskan oleh panitia.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="text-center mt-5">
                    <a href="profile.php" class="btn btn-light rounded-pill px-4 shadow-sm fw-medium text-secondary border">
                        <i class="bi bi-arrow-left me-2"></i> Kembali ke Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>