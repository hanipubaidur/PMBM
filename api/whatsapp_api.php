<?php
require_once __DIR__ . '/../config/db.php';

function sendWhatsAppMessage($phone, $message) {
    // Format nomor telepon
    $phone = preg_replace('/^0/', '62', $phone);
    $phone = preg_replace('/[^0-9]/', '', $phone);

    $data = [
        'target' => $phone,
        'message' => $message,
        'delay' => '5-10'
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => FONNTE_API,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . FONNTE_TOKEN,
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    // Logging
    error_log("Fonnte Response: " . $response);
    error_log("Fonnte Error: " . $error);

    return $error ? false : true;
}

function sendRegistrationSuccess($nisn, $password, $phone, $nama_lengkap = "Calon Siswa") {
    // Note: Aku tambahkan parameter $nama_lengkap biar bisa dipanggil namanya
    
    $message = "✨ *SELAMAT DATANG DI PMBM MAN 1 MUSI RAWAS* ✨\n\n"
             . "Halo *" . trim($nama_lengkap) . "*! 👋\n"
             . "Terima kasih telah melakukan pendaftaran awal. Akun PMBM kamu telah berhasil dibuat.\n\n"
             . "Berikut adalah detail akses akun kamu:\n"
             . "👤 *NISN*        : $nisn\n"
             . "🔑 *Password* : $password\n\n"
             . "⚠️ *PENTING:*\n"
             . "_Mohon simpan dan jangan berikan informasi login ini kepada siapapun._\n\n"
             . "Langkah selanjutnya, silakan *Login* untuk melengkapi formulir biodata dan mengunggah berkas persyaratan di tautan berikut:\n"
             . "🌐 https://ppdb-man1musirawas.wuaze.com\n\n"
             . "Jika kamu mengalami kendala atau memiliki pertanyaan, jangan ragu untuk menghubungi panitia:\n"
             . "📞 *Admin:* 081368102412/085725128427\n\n"
             . "Terima kasih,\n"
             . "*Panitia PMBM MAN 1 Musi Rawas* 🎓";

    return sendWhatsAppMessage($phone, $message);
}
?>