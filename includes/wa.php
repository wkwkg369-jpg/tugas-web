<?php
/**
 * Klien OpenWA (open-wa.org) — gateway WhatsApp self-hosted.
 * Tanpa dependensi eksternal: cukup ekstensi curl PHP.
 *
 * Alur kirim (sesuai dokumentasi Quick Start OpenWA v0.31):
 *   POST {base_url}/api/sessions/{session_id}/messages/send-text
 *   header: X-API-Key: <api_key>
 *   body  : {"chatId": "6281…@c.us", "text": "…"}
 *
 * Konfigurasi dibaca dari includes/wa-config.php (tidak masuk repo).
 * Bila berkas itu belum ada atau mode belum 'openwa', notifikasi WhatsApp
 * dilewati tanpa mengganggu notifikasi in-app/email yang sudah ada.
 */

require_once __DIR__ . '/db.php';

/**
 * Nomor pengirim default: 085199816361 — nomor WhatsApp yang akan
 * di-scan ke OpenWA. Disimpan ternormalisasi (format internasional).
 */
const SIP_WA_DEFAULT_SENDER = '6285199816361';

/**
 * Batas atas penerima per satu notifikasi admin, agar tidak membanjiri
 * nomor pengirim (OpenWA memberi peringatan dini ban).
 */
const SIP_WA_MAX_ADMIN_RECIPIENTS = 5;

/** Baca konfigurasi WA. Tidak melempar error bila berkas tidak ada. */
function sip_wa_config(): array {
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }

    $defaults = [
        'mode'           => 'off',            // 'off' | 'openwa'
        'base_url'       => 'http://127.0.0.1:2785',
        'api_key'        => '',
        'session_id'     => '',
        'sender'         => SIP_WA_DEFAULT_SENDER,
        'timeout'        => 8,
        'notify_admins'  => true,
        'admin_number'   => '',               // nomor WA khusus untuk peringatan staf
        'public_base_url'=> '',
    ];

    $file = __DIR__ . '/wa-config.php';
    if (is_file($file)) {
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
        $loaded = @include $file;
        if (is_array($loaded)) {
            $defaults = array_merge($defaults, $loaded);
        }
    }

    $cfg = $defaults;
    return $cfg;
}

/**
 * Tulis konfigurasi WhatsApp ke includes/wa-config.php.
 * Nilai string ditulis lewat var_export agar aman dari injeksi kode PHP.
 * API key kosong = pertahankan yang sudah tersimpan.
 */
function sip_wa_config_write(array $data): array {
    $file = __DIR__ . '/wa-config.php';
    $old = sip_wa_config();

    $mode = in_array((string) ($data['mode'] ?? ''), ['off', 'openwa'], true) ? (string) $data['mode'] : 'off';

    $baseUrl = rtrim(trim((string) ($data['base_url'] ?? '')), '/');
    if ($baseUrl === '') {
        $baseUrl = 'http://127.0.0.1:2785';
    }
    if (!preg_match('#^https?://[a-z0-9.\-]+(:\d+)?(/\S*)?$#i', $baseUrl)) {
        return ['ok' => false, 'error' => 'Alamat OpenWA tidak valid (contoh: http://127.0.0.1:2785).'];
    }

    $apiKey = trim((string) ($data['api_key'] ?? ''));
    if ($apiKey === '') {
        $apiKey = (string) ($old['api_key'] ?? '');
    }

    $sessionId = trim((string) ($data['session_id'] ?? ''));
    if ($sessionId !== '' && !preg_match('/^[A-Za-z0-9\-]{1,128}$/', $sessionId)) {
        return ['ok' => false, 'error' => 'ID sesi OpenWA hanya boleh huruf, angka, dan tanda minus.'];
    }

    $sender = (string) ($data['sender'] ?? '');
    $senderNorm = sip_wa_normalize_number($sender === '' ? SIP_WA_DEFAULT_SENDER : $sender);
    if ($senderNorm === '') {
        return ['ok' => false, 'error' => 'Nomor pengirim tidak valid. Gunakan format 08… atau 62…'];
    }

    $timeout = (int) ($data['timeout'] ?? 8);
    if ($timeout < 3 || $timeout > 60) {
        $timeout = 8;
    }

    $publicBase = trim((string) ($data['public_base_url'] ?? ''));
    if ($publicBase !== '' && !preg_match('#^https?://\S+$#i', $publicBase)) {
        return ['ok' => false, 'error' => 'Alamat situs (untuk tautan di WhatsApp) tidak valid.'];
    }

    $notifyAdmins = !empty($data['notify_admins']);

    $adminNumber = trim((string) ($data['admin_number'] ?? ''));
    if ($adminNumber !== '') {
        $adminNumber = sip_wa_normalize_number($adminNumber);
        if ($adminNumber === '') {
            return ['ok' => false, 'error' => 'Nomor WhatsApp admin tidak valid. Gunakan format 08… atau 62…'];
        }
    }

    if ($mode === 'openwa' && ($apiKey === '' || $sessionId === '')) {
        return ['ok' => false, 'error' => 'Mode OpenWA butuh ID sesi dan API key. API key bisa dibaca dari ./data/.api-key di server OpenWA.'];
    }

    $cfg = [
        'mode'            => $mode,
        'base_url'        => $baseUrl,
        'api_key'         => $apiKey,
        'session_id'      => $sessionId,
        'sender'          => $senderNorm,
        'timeout'         => $timeout,
        'notify_admins'   => $notifyAdmins,
        'admin_number'    => $adminNumber,
        'public_base_url' => rtrim($publicBase, '/'),
    ];

    $php = "<?php\n"
        . "// DIKELOLA DARI PANEL ADMIN (Pengaturan WhatsApp). Jangan commit ke repo.\n"
        . "// Dibuat otomatis " . date('Y-m-d H:i:s') . ".\n"
        . "return " . var_export($cfg, true) . ";\n";

    $tmp = $file . '.tmp';
    if (@file_put_contents($tmp, $php) === false) {
        return ['ok' => false, 'error' => 'Gagal menulis wa-config.php (periksa izin folder includes/).'];
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return ['ok' => false, 'error' => 'Gagal menyimpan wa-config.php.'];
    }
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);
    }

    return ['ok' => true, 'error' => ''];
}

/** Nomor pengirim sudah diatur dan mode aktif. */
function sip_wa_ready(): bool {
    $cfg = sip_wa_config();
    return ($cfg['mode'] ?? 'off') === 'openwa'
        && trim((string) ($cfg['api_key'] ?? '')) !== ''
        && trim((string) ($cfg['session_id'] ?? '')) !== '';
}

/**
 * Ubah nomor Indonesia apa pun menjadi format OpenWA.
 *   0851-9981-6361 -> 6285199816361
 *   +62 851 9981 6361 -> 6285199816361
 *   sudaryono@c.us (sudah chatId) -> null (bukan nomor)
 */
function sip_wa_normalize_number(string $raw): string {
    $d = preg_replace('/\D+/', '', $raw) ?? '';
    if ($d === '') {
        return '';
    }
    if (str_starts_with($d, '0')) {
        $d = '62' . substr($d, 1);
    } elseif (str_starts_with($d, '8')) {
        $d = '62' . $d;
    }
    // Nomor Indonesia: 62 diikuti 9–13 angka tanpa awalan 0.
    if (!preg_match('/^62[1-9]\d{7,11}$/', $d)) {
        return '';
    }
    return $d;
}

/** chatId yang dipakai OpenWA. */
function sip_wa_chat_id(string $number): string {
    return sip_wa_normalize_number($number) . '@c.us';
}

/**
 * Panggil REST API OpenWA.
 * @return array{ok: bool, status: int, body: array|string, error: string}
 */
function sip_wa_request(string $method, string $path, ?array $payload = null, int $timeoutOverride = 0): array {
    $cfg = sip_wa_config();
    $url = rtrim((string) $cfg['base_url'], '/') . $path;
    $timeout = $timeoutOverride > 0 ? $timeoutOverride : (int) ($cfg['timeout'] ?? 8);

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Ekstensi PHP curl tidak tersedia di server.'];
    }

    $ch = curl_init($url);
    $headers = [
        'Accept: application/json',
        'X-API-Key: ' . (string) $cfg['api_key'],
    ];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        // OpenWA berjalan lokal via HTTP; jangan ikut proxy sistem.
        CURLOPT_PROXY          => '',
        CURLOPT_NOPROXY        => '*',
    ]);

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Tidak bisa menghubungi OpenWA: ' . $curlErr];
    }

    $decoded = json_decode((string) $raw, true);
    $body = is_array($decoded) ? $decoded : (string) $raw;
    $ok = $status >= 200 && $status < 300;

    $error = '';
    if (!$ok) {
        $msg = is_array($body) ? (string) ($body['message'] ?? '') : '';
        if (is_array($msg)) {
            $msg = implode(', ', array_map('strval', $msg));
        }
        $error = 'HTTP ' . $status . ($msg !== '' ? ': ' . $msg : '');
    }

    return ['ok' => $ok, 'status' => $status, 'body' => $body, 'error' => $error];
}

/** Status sesi WhatsApp (health per akun). */
function sip_wa_session_status(): array {
    $cfg = sip_wa_config();
    $sid = trim((string) ($cfg['session_id'] ?? ''));
    if ($sid === '') {
        return ['ok' => false, 'error' => 'ID sesi belum diatur.', 'status' => '', 'phone' => ''];
    }
    $res = sip_wa_request('GET', '/api/sessions/' . rawurlencode($sid));
    if (!$res['ok'] || !is_array($res['body'])) {
        return ['ok' => false, 'error' => $res['error'] !== '' ? $res['error'] : 'Respons tidak dikenali.', 'status' => '', 'phone' => ''];
    }
    return [
        'ok'     => true,
        'error'  => '',
        'status' => (string) ($res['body']['status'] ?? ''),
        'phone'  => (string) ($res['body']['phone'] ?? ''),
        'name'   => (string) ($res['body']['pushName'] ?? ''),
    ];
}

/**
 * Kirim satu pesan teks.
 * @return array{ok: bool, error: string, messageId: string, skipped: bool}
 */
function sip_wa_send_text(string $number, string $text): array {
    $hasil = ['ok' => false, 'error' => '', 'messageId' => '', 'skipped' => false];

    if (!sip_wa_ready()) {
        $hasil['skipped'] = true;
        return $hasil;
    }

    $chatId = sip_wa_chat_id($number);
    if ($chatId === '@c.us') {
        $hasil['error'] = 'Nomor tidak valid.';
        return $hasil;
    }
    if (trim($text) === '') {
        $hasil['error'] = 'Teks kosong.';
        return $hasil;
    }

    $cfg = sip_wa_config();
    $sessionId = (string) $cfg['session_id'];
    $res = sip_wa_request('POST', '/api/sessions/' . rawurlencode($sessionId) . '/messages/send-text', [
        'chatId' => $chatId,
        'text'   => $text,
    ]);

    if ($res['ok']) {
        $hasil['ok'] = true;
        $hasil['messageId'] = is_array($res['body']) ? (string) ($res['body']['messageId'] ?? '') : '';
        return $hasil;
    }

    // 409 ENGINE_PAGE_RELOADING: WhatsApp Web sedang memuat ulang. Coba sekali lagi.
    $retry = is_array($res['body']) ? (int) ($res['body']['retryAfterSeconds'] ?? 0) : 0;
    if ($res['status'] === 409 && $retry > 0 && $retry <= 30) {
        sleep(min($retry, 10));
        $res = sip_wa_request('POST', '/api/sessions/' . rawurlencode($sessionId) . '/messages/send-text', [
            'chatId' => $chatId,
            'text'   => $text,
        ]);
        if ($res['ok']) {
            $hasil['ok'] = true;
            $hasil['messageId'] = is_array($res['body']) ? (string) ($res['body']['messageId'] ?? '') : '';
            return $hasil;
        }
    }

    $hasil['error'] = $res['error'] !== '' ? $res['error'] : 'Pengiriman gagal.';
    return $hasil;
}

/** Catat kegagalan tanpa pernah memblokir alur utama aplikasi. */
function sip_wa_log_error(string $line): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/wa-error.log', '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n", FILE_APPEND);
}

/**
 * Nomor yang dilaporkan WhatsApp "not registered" disimpan sebentar agar
 * nomor contoh/palsu di database tidak dikirimi pesan berulang kali.
 * @return array<string,int>
 */
function sip_wa_unregistered_map(): array {
    $file = __DIR__ . '/../logs/wa-unregistered.json';
    if (!is_file($file)) {
        return [];
    }
    $data = json_decode((string) @file_get_contents($file), true);
    if (!is_array($data)) {
        return [];
    }
    $batas = time() - 7 * 86400;
    return array_filter($data, static fn($ts) => (int) $ts >= $batas);
}

function sip_wa_mark_unregistered(string $number): void {
    $norm = sip_wa_normalize_number($number);
    if ($norm === '') {
        return;
    }
    $map = sip_wa_unregistered_map();
    $map[$norm] = time();
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/wa-unregistered.json', json_encode($map), LOCK_EX);
}

/**
 * Apakah user ini berhak menerima notifikasi WhatsApp.
 * Notifikasi WhatsApp hanya untuk staf (admin/petugas/super admin) dan
 * pemilik notifikasi pribadi; isi pesan admin tidak pernah dibocorkan.
 */
function sip_wa_user_phone(int $userId): string {
    if ($userId <= 0) {
        return '';
    }
    $u = db_fetch_one("SELECT phone FROM users WHERE id = ?", [$userId]);
    return $u ? trim((string) ($u['phone'] ?? '')) : '';
}

/**
 * Susun teks WhatsApp dari notifikasi in-app.
 * Link relatif diubah ke URL penuh bila `public_base_url` diisi.
 */
function sip_wa_format_message(array $cfg, string $judul, string $pesan, string $link = ''): string {
    $base = rtrim((string) ($cfg['public_base_url'] ?? ''), '/');
    $teks = "*SIP-Barang Kampus*\n"
        . $judul . "\n\n"
        . $pesan;
    if ($link !== '' && $link !== '#' && $base !== '') {
        $url = preg_match('#^https?://#i', $link) ? $link : $base . '/' . ltrim($link, '/');
        $teks .= "\n\nBuka: " . $url;
    }
    return $teks . "\n\n(Pesan otomatis, tidak perlu dibalas.)";
}

/**
 * Kirim notifikasi WhatsApp ke satu user (dipanggil dari notify_user).
 * Diam-diam dilewati bila user belum punya nomor atau mode nonaktif.
 */
function sip_wa_notify_user_id(int $userId, string $judul, string $pesan, string $link = '#'): bool {
    if (!sip_wa_ready() || $userId <= 0) {
        return false;
    }
    $phone = sip_wa_user_phone($userId);
    if (sip_wa_normalize_number($phone) === '') {
        return false;
    }
    $cfg = sip_wa_config();
    $teks = sip_wa_format_message($cfg, $judul, $pesan, $link);
    try {
        $res = sip_wa_send_text($phone, $teks);
        if (!$res['ok'] && !$res['skipped']) {
            sip_wa_log_error('user#' . $userId . ' ' . $res['error']);
        }
        return $res['ok'];
    } catch (Throwable $e) {
        sip_wa_log_error('user#' . $userId . ' exception: ' . $e->getMessage());
        return false;
    }
}

/**
 * Kirim ke nomor tertentu sekaligus (dipakai untuk pesan admin).
 * Dipakai oleh notify_admins untuk mengirim satu WhatsApp ke pemilik
 * nomor pengirim (staf pos) alih-alih satu pesan per admin.
 */
function sip_wa_notify_number(string $number, string $judul, string $pesan, string $link = '#'): bool {
    $norm = sip_wa_normalize_number($number);
    if (!sip_wa_ready() || $norm === '') {
        return false;
    }

    // Nomor yang sudah terbukti tidak punya WhatsApp dilewati selama 7 hari
    // (banyak nomor contoh di database, mis. 08123456789).
    if (isset(sip_wa_unregistered_map()[$norm])) {
        return false;
    }

    $cfg = sip_wa_config();
    try {
        $res = sip_wa_send_text($norm, sip_wa_format_message($cfg, $judul, $pesan, $link));
        if (!$res['ok'] && !$res['skipped']) {
            if (stripos($res['error'], 'not registered') !== false) {
                sip_wa_mark_unregistered($norm);
            }
            sip_wa_log_error('nomor ' . substr($norm, 0, 6) . '*** ' . $res['error']);
        }
        return $res['ok'];
    } catch (Throwable $e) {
        sip_wa_log_error('exception: ' . $e->getMessage());
        return false;
    }
}
