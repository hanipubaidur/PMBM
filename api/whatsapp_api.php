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

function sendRegistrationSuccess($nisn, $password, $phone) {
    $message = "📚 *PPDB MAN 1 Musi Rawas* 📚\n\n"
             . "Halo calon siswa!\n\n"
             . "Berikut kredensial login Anda:\n"
             . "➤ NISN: *$nisn*\n"
             . "➤ Password: *$password*\n\n"
             . "🔐 *Simpan baik-baik informasi ini!*\n"
             . "🔗 Login di: https://testppdb.wuaze.com\n\n"
             . "Untuk bantuan hubungi:\n"
             . "📞 0813-6810-2412 (Admin)";

    return sendWhatsAppMessage($phone, $message);
}
?>