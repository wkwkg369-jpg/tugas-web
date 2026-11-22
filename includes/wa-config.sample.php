<?php
/**
 * Contoh konfigurasi WhatsApp (OpenWA — https://open-wa.org).
 * SALIN menjadi includes/wa-config.php, atau isi lewat menu
 * Admin → Pengaturan WhatsApp (hanya Super Admin).
 * File wa-config.php TIDAK boleh masuk repo publik.
 *
 * Cara memasang OpenWA di server (ringkas):
 *   1. docker run -d --name openwa-api -p 127.0.0.1:2785:2785 \
 *        -e AUTO_START_SESSIONS=true -v ./data:/app/data rmyndharis/openwa:latest
 *   2. docker exec openwa-api cat /app/data/.api-key   → isi 'api_key'
 *   3. Buat sesi: POST /api/sessions {"name":"sip-barang"}
 *   4. Start + scan QR dengan nomor pengirim (085199816361):
 *      POST /api/sessions/{id}/start, lalu GET /api/sessions/{id}/qr
 *   5. Salin id sesi ke 'session_id'.
 */

return [
    // 'off' = hanya notifikasi in-app (default), 'openwa' = kirim juga ke WhatsApp
    'mode'       => 'openwa',

    // OpenWA berjalan di server yang sama; jangan dibuka ke internet.
    'base_url'   => 'http://127.0.0.1:2785',
    'api_key'    => 'owa_k1_...',
    'session_id' => 'uuid-sesi-openwa',

    // Nomor pengirim: 085199816361 (dinormalisasi otomatis ke 6285199816361)
    'sender'     => '6285199816361',

    'timeout'    => 8,

    // Laporan/klaim baru juga diteruskan ke WhatsApp staf pos.
    'notify_admins' => true,

    // Dipakai untuk membuat tautan lengkap di pesan WhatsApp.
    'public_base_url' => 'https://aralfian.my.id',
];
