<?php
require_once __DIR__.'/../config/db.php';

// ==========================================
// SECURITY: CSRF & TRANSACTION TOKENS (ANTI-BOT)
// ==========================================
function generateCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function generateTransactionToken($action) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = bin2hex(random_bytes(32));
    $_SESSION['tx_token'][$action] = $token; 
    return $token;
}

function validateAndConsumeTxToken($action, $token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['tx_token'][$action]) || empty($token)) return false;
    
    if (hash_equals($_SESSION['tx_token'][$action], $token)) {
        unset($_SESSION['tx_token'][$action]); 
        return true;
    }
    return false;
}

function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ==========================================
// CAPTCHA LOGIC
// ==========================================
function validateCaptcha($input, $type = 'register') {
    if (empty($_SESSION['captcha'][$type]) || empty($input)) return false;

    if (time() - $_SESSION['captcha'][$type]['time'] > 90) {
        unset($_SESSION['captcha'][$type]);
        return false;
    }

    if ($_SESSION['captcha'][$type]['attempts'] >= 3) {
        unset($_SESSION['captcha'][$type]);
        return false;
    }

    $_SESSION['captcha'][$type]['attempts']++;

    $isValid = (strtoupper($input) === strtoupper($_SESSION['captcha'][$type]['code']));
    if ($isValid) unset($_SESSION['captcha'][$type]);
    
    return $isValid;
}

// ==========================================
// FILE UPLOAD HELPERS (YANG SEMPAT TERHAPUS)
// ==========================================
function getFormattedFileName($original_name, $type, $nama_lengkap) {
    // Bersihkan nama dari spasi dan karakter aneh untuk nama file
    $safe_nama = preg_replace('/[^A-Za-z0-9]/', '', $nama_lengkap);
    
    switch($type) {
        case 'file_photo': 
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION)); 
            return 'FOTO_' . $safe_nama . '.' . $ext;
        case 'file_kk': 
            return 'KK_' . $safe_nama . '.pdf';
        case 'file_akte': 
            return 'AKTE_' . $safe_nama . '.pdf';
        case 'file_ijazah_skl': 
            return 'IJAZAH_' . $safe_nama . '.pdf';
        case (preg_match('/^file_raport_[1-5]$/', $type) ? true : false): 
            $semester = substr($type, -1); 
            return 'RAPORT_SEM' . $semester . '_' . $safe_nama . '.pdf';
        default: 
            return null;
    }
}

// ==========================================
// JALUR & PENGUMUMAN LOGIC
// ==========================================
function getActiveJalur($pdo) {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT j.* FROM jalur_pendaftaran j
        WHERE ? BETWEEN j.tanggal_buka AND j.tanggal_tutup
        AND (SELECT COUNT(*) FROM peserta WHERE jalur_id = j.id) < j.kuota
    ");
    $stmt->execute([$now]);
    return $stmt->fetchAll();
}

function getAnnouncementStatus($userId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.jalur_id, j.tanggal_pengumuman, v.status_verifikasi, pg.status_penerimaan
            FROM peserta p
            LEFT JOIN jalur_pendaftaran j ON p.jalur_id = j.id
            LEFT JOIN verifikasi_peserta v ON p.id = v.peserta_id
            LEFT JOIN pengumuman pg ON p.id = pg.peserta_id
            WHERE p.id = ?
        ");
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) return ['status' => 'error', 'message' => 'Data tidak ditemukan'];
        if ($data['status_verifikasi'] !== 'Verified') return ['status' => 'unverified', 'message' => 'Data Anda belum diverifikasi', 'canViewResult' => false];

        $now = new DateTime();
        $announceDate = new DateTime($data['tanggal_pengumuman']);

        if ($now < $announceDate) return ['status' => 'scheduled', 'message' => 'Pengumuman akan dirilis pada ' . $announceDate->format('d F Y'), 'canViewResult' => false, 'date' => $data['tanggal_pengumuman']];
        if ($data['status_penerimaan']) return ['status' => 'announced', 'message' => 'Hasil pengumuman sudah tersedia', 'canViewResult' => true, 'result' => $data['status_penerimaan'], 'date' => $data['tanggal_pengumuman']];

        return ['status' => 'pending', 'message' => 'Hasil pengumuman sedang diproses', 'canViewResult' => false];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Terjadi kesalahan sistem'];
    }
}

// ==========================================
// JAVASCRIPT GENERATOR
// ==========================================
function getJavaScript() {
    return <<<'JS'
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function initializeCaptcha(captchaId, timerId, inputId, type, txToken) {
    refreshCaptcha(captchaId, timerId, inputId, type, txToken);
}

async function refreshCaptcha(captchaId, timerId, inputId, type, txToken) {
    const input = document.getElementById(inputId);
    input.addEventListener('paste', e => e.preventDefault());
    input.addEventListener('contextmenu', e => e.preventDefault());
    
    if (window[`${timerId}_interval`]) clearInterval(window[`${timerId}_interval`]);
    input.value = ''; input.disabled = false;
    
    try {
        let basePath = window.location.pathname.includes('/admin/') ? '../includes/' : 'includes/';
        const response = await fetch(`${basePath}captcha.php?type=${type}&tx_auth=${encodeURIComponent(txToken)}&t=${new Date().getTime()}`, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        });
        
        if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);
        
        const captcha = await response.text();
        const captchaElement = document.getElementById(captchaId);
        
        captchaElement.innerHTML = Array.from(captcha).map(char => {
            const rotation = Math.random() * 20 - 10; 
            const color = `hsl(${Math.random() * 360}, 70%, 40%)`; 
            return `<span style="color: ${color}; display: inline-block; transform: rotate(${rotation}deg);">${char}</span>`;
        }).join('');
        
        const noise = Array(3).fill().map(() => `<span class="noise" style="left: ${Math.random() * 100}%"></span>`).join('');
        captchaElement.insertAdjacentHTML('beforeend', noise);
        
        startCaptchaTimer(timerId, captchaId, inputId, type, txToken);
        
    } catch (error) {
        console.error('Captcha Error:', error);
        document.getElementById(captchaId).innerHTML = `<span class="text-danger" style="font-size: 1rem;">ERROR</span>`;
        document.getElementById(timerId).textContent = "00:00";
        input.disabled = true;
    }
}

function startCaptchaTimer(timerId, captchaId, inputId, type, txToken) {
    let timeLeft = 90;
    const timerElement = document.getElementById(timerId);
    
    if (window[`${timerId}_interval`]) clearInterval(window[`${timerId}_interval`]);
    
    function updateDisplay() {
        timerElement.textContent = `${Math.floor(timeLeft / 60).toString().padStart(2, '0')}:${(timeLeft % 60).toString().padStart(2, '0')}`;
        if (timeLeft <= 30) { timerElement.classList.replace('bg-warning', 'bg-danger'); } 
        else if (timeLeft <= 60) { timerElement.classList.replace('bg-danger', 'bg-warning'); }
    }
    
    window[`${timerId}_interval`] = setInterval(() => {
        timeLeft--; updateDisplay();
        if (timeLeft <= 0) {
            clearInterval(window[`${timerId}_interval`]);
            refreshCaptcha(captchaId, timerId, inputId, type, txToken); // Auto refresh
        }
    }, 1000);
    updateDisplay();
}
JS;
}
?>