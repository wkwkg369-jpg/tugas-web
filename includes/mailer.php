<?php
/**
 * Klien SMTP sederhana tanpa dependensi (tanpa Composer/PHPMailer).
 * Mendukung:
 *   - STARTTLS pada port 587 (secure = 'tls')
 *   - TLS langsung pada port 465 (secure = 'ssl')
 *   - AUTH LOGIN (dipakai Gmail App Password, Brevo, Mailtrap, SMTP kampus)
 *
 * Konfigurasi dibaca dari includes/mail-config.php (tidak masuk repo).
 */

require_once __DIR__ . '/db.php';

/**
 * Tulis konfigurasi email ke includes/mail-config.php.
 * Dipakai halaman admin agar kredensial SMTP bisa diisi tanpa akses SSH.
 * Nilai string ditulis lewat var_export (aman dari injeksi kode PHP).
 */
function sip_mail_config_write(array $data): array {
    $file = __DIR__ . '/mail-config.php';

    // Pastikan salinan di opcache tidak dipakai saat membaca nilai lama
    // (kata sandi yang harus dipertahankan).
    if (is_file($file) && function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);
    }

    $allowed = [
        'mode' => ['smtp', 'demo'],
        'secure' => ['tls', 'ssl', 'none'],
    ];

    $mode = in_array((string) ($data['mode'] ?? ''), $allowed['mode'], true) ? (string) $data['mode'] : 'demo';
    $secure = in_array((string) ($data['secure'] ?? ''), $allowed['secure'], true) ? (string) $data['secure'] : 'tls';

    $host = strtolower(trim((string) ($data['host'] ?? '')));
    if ($host !== '' && !preg_match('/^[a-z0-9.\-]{3,190}$/', $host)) {
        return ['ok' => false, 'error' => 'Alamat server SMTP tidak valid.'];
    }

    $port = (int) ($data['port'] ?? 0);
    if ($port < 1 || $port > 65535) {
        return ['ok' => false, 'error' => 'Port SMTP harus antara 1 dan 65535.'];
    }

    $username = trim((string) ($data['username'] ?? ''));
    $fromEmail = trim((string) ($data['from_email'] ?? ''));
    if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Alamat pengirim tidak valid.'];
    }

    $password = (string) ($data['password'] ?? '');

    // Kata sandi kosong = pertahankan yang sudah tersimpan.
    if ($password === '') {
        // Baca langsung dari berkas (bukan lewat cache statis) supaya nilai
        // terbaru selalu dipakai.
        $password = '';
        if (is_file($file)) {
            $included = @include $file;
            if (is_array($included) && isset($included['password'])) {
                $password = (string) $included['password'];
            }
        }
    } else {
        // App password Google biasanya berisi 16 huruf tanpa spasi.
        $password = str_replace(' ', '', $password);
    }

    if ($mode === 'smtp') {
        if ($host === '' || $username === '' || $password === '' || $fromEmail === '') {
            return ['ok' => false, 'error' => 'Mode SMTP butuh server, akun, kata sandi aplikasi, dan alamat pengirim.'];
        }
    }

    $domains = $data['allowed_email_domains'] ?? [];
    if (is_string($domains)) {
        $domains = preg_split('/[,;\s]+/', $domains) ?: [];
    }
    $cleanDomains = [];
    foreach ((array) $domains as $d) {
        $d = strtolower(trim((string) $d, " \t\n\r\0\x0B@."));
        if ($d !== '' && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $d)) {
            $cleanDomains[] = $d;
        }
    }
    if (empty($cleanDomains)) {
        $cleanDomains = ['ui.ac.id'];
    }

    $config = [
        'mode'       => $mode,
        'host'       => $host,
        'port'       => $port,
        'secure'     => $secure,
        'username'   => $username,
        'password'   => $password,
        'from_email' => $fromEmail !== '' ? $fromEmail : $username,
        'from_name'  => trim((string) ($data['from_name'] ?? 'SIP-Barang Kampus')) ?: 'SIP-Barang Kampus',
        'timeout'    => 20,
        'allowed_email_domains' => array_values(array_unique($cleanDomains)),
    ];

    $php = "<?php\n"
        . "// Dibuat lewat panel admin SIP-Barang Kampus. Jangan di-commit ke repo.\n"
        . "return " . var_export($config, true) . ";\n";

    $file = __DIR__ . '/mail-config.php';
    $file = __DIR__ . '/mail-config.php';
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';

    if (@file_put_contents($tmp, $php) === false) {
        return ['ok' => false, 'error' => 'Gagal menulis berkas konfigurasi. Periksa izin tulis folder includes.'];
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return ['ok' => false, 'error' => 'Gagal menyimpan konfigurasi email.'];
    }
    @chmod($file, 0640);

    // Muat ulang konfigurasi pada permintaan berikutnya.
    sip_mail_config(true);
    return ['ok' => true, 'error' => ''];
}

/** Baca konfigurasi email; aman walau file konfigurasi belum ada. */
function sip_mail_config(bool $reload = false): array {
    static $cfg = null;
    if ($cfg !== null && !$reload) {
        return $cfg;
    }

    $file = __DIR__ . '/mail-config.php';

    $defaults = [
        'mode'       => 'demo',      // 'smtp' untuk kirim sungguhan, 'demo' untuk uji tanpa email
        'host'       => '',
        'port'       => 587,
        'secure'     => 'tls',       // 'tls' (STARTTLS/587) atau 'ssl' (465)
        'username'   => '',
        'password'   => '',
        'from_email' => '',
        'from_name'  => 'SIP-Barang Kampus',
        'timeout'    => 20,
        // Hanya untuk demo di lingkungan lokal: tampilkan kode di halaman
        // verifikasi bila SMTP belum aktif. JANGAN aktifkan di server publik.
        'demo_show_code' => false,
    ];

    $file = __DIR__ . '/mail-config.php';
    if (is_file($file)) {
        $loaded = require $file;
        if (is_array($loaded)) {
            $loaded = array_merge($defaults, $loaded);
        } else {
            $loaded = $defaults;
        }
    } else {
        $loaded = $defaults;
    }

    // Variabel lingkungan boleh menimpa (memudahkan deploy).
    foreach (['host', 'username', 'password', 'from_email', 'from_name', 'mode', 'secure'] as $k) {
        $env = getenv('SIP_SMTP_' . strtoupper($k));
        if (is_string($env) && $env !== '') {
            $loaded[$k] = $env;
        }
    }

    if ($loaded['from_email'] === '' && $loaded['username'] !== '') {
        $loaded['from_email'] = $loaded['username'];
    }

    $cfg = $loaded;
    return $cfg;
}

/** Apakah pengiriman email sungguhan sudah siap dipakai? */
function sip_mail_ready(): bool {
    $c = sip_mail_config();
    return $c['mode'] === 'smtp'
        && $c['host'] !== ''
        && $c['username'] !== ''
        && $c['password'] !== ''
        && $c['from_email'] !== '';
}

/**
 * Kirim email HTML. Mengembalikan ['ok' => bool, 'error' => string].
 * Pada mode demo, email tidak dikirim tetapi pesan dicatat ke logs/mail-demo.log.
 */
function sip_send_mail(string $to, string $subject, string $htmlBody, string $textBody = ''): array {
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Alamat email tujuan tidak valid.'];
    }

    $cfg = sip_mail_config();

    if (!sip_mail_ready()) {
        sip_log_demo_mail($to, $subject, $textBody !== '' ? $textBody : strip_tags($htmlBody));
        return ['ok' => false, 'error' => 'SMTP belum dikonfigurasi (mode demo).'];
    }

    try {
        $mailer = new SipSmtpMailer($cfg);
        $mailer->send($to, $subject, $htmlBody, $textBody);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        sip_log_mail_error($to, $msg);
        return ['ok' => false, 'error' => $msg];
    }
}

function sip_log_demo_mail(string $to, string $subject, string $body): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    $line = sprintf("[%s] DEMO -> %s | %s\n%s\n%s\n",
        date('Y-m-d H:i:s'), $to, $subject, $body, str_repeat('-', 60));
    @file_put_contents($dir . '/mail-demo.log', $line, FILE_APPEND);
}

function sip_log_mail_error(string $to, string $error): void {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    $line = sprintf("[%s] GAGAL -> %s | %s\n", date('Y-m-d H:i:s'), $to, $error);
    @file_put_contents($dir . '/mail-error.log', $line, FILE_APPEND);
}

class SipSmtpMailer {
    private array $cfg;
    /** @var resource|null */
    private $sock = null;
    private string $log = '';

    public function __construct(array $cfg) {
        $this->cfg = $cfg;
    }

    public function send(string $to, string $subject, string $htmlBody, string $textBody = ''): void {
        $host = $this->cfg['host'];
        $port = (int) $this->cfg['port'];
        $secure = $this->cfg['secure'];
        $timeout = (int) ($this->cfg['timeout'] ?: 20);

        $transport = ($secure === 'ssl') ? 'ssl://' : 'tcp://';
        $errno = 0;
        $errstr = '';
        $sock = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, $timeout);
        if (!$sock) {
            throw new RuntimeException("Tidak bisa terhubung ke {$host}:{$port} ({$errstr})");
        }
        $this->sock = $sock;
        stream_set_timeout($this->sock, $timeout);

        $this->expect(220, 'salam server');

        $ehloName = $this->ehloName();
        $this->cmd('EHLO ' . $ehloName, 250, 'EHLO');

        if ($secure === 'tls') {
            $this->cmd('STARTTLS', 220, 'STARTTLS');
            $ok = @stream_socket_enable_crypto(
                $this->sock,
                true,
                STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
            );
            if ($ok !== true) {
                throw new RuntimeException('Gagal memulai TLS (STARTTLS ditolak server).');
            }
            $this->cmd('EHLO ' . $ehloName, 250, 'EHLO setelah TLS');
        }

        // AUTH LOGIN — dilewati hanya bila username kosong (relay lokal tanpa auth).
        if ((string) $this->cfg['username'] !== '') {
            $this->cmd('AUTH LOGIN', 334, 'AUTH LOGIN');
            $this->cmd(base64_encode($this->cfg['username']), 334, 'AUTH username');
            $this->cmd(base64_encode($this->cfg['password']), 235, 'AUTH password');
        }

        $from = $this->cfg['from_email'];
        $this->cmd('MAIL FROM:<' . $from . '>', 250, 'MAIL FROM');
        $this->cmd('RCPT TO:<' . $to . '>', [250, 251], 'RCPT TO');
        $this->cmd('DATA', 354, 'DATA');

        $data = $this->buildMessage($to, $subject, $htmlBody, $textBody);
        // Dot-stuffing: baris yang diawali '.' harus digandakan.
        $data = preg_replace('/^\./m', '..', $data);
        $this->writeRaw($data . "\r\n.\r\n");
        $this->expect(250, 'kirim isi pesan');

        $this->cmd('QUIT', 221, 'QUIT', true);
        $this->close();
    }

    private function ehloName(): string {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = preg_replace('/:\d+$/', '', (string) $host);
        if (!preg_match('/^[A-Za-z0-9.\-]+$/', $host)) {
            return 'localhost';
        }
        return $host !== '' ? $host : 'localhost';
    }

    private function buildMessage(string $to, string $subject, string $htmlBody, string $textBody): string {
        $fromName = $this->cfg['from_name'];
        $fromEmail = $this->cfg['from_email'];
        $boundary = 'sip-' . bin2hex(random_bytes(12));

        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>';
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $this->ehloName() . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        if ($textBody === '') {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $htmlBody));
        }

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($textBody), 76, "\r\n");
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody), 76, "\r\n");
        $body .= '--' . $boundary . "--\r\n";

        return $body;
    }

    private function encodeHeader(string $value): string {
        if (preg_match('/^[\x20-\x7E]*$/', $value)) {
            return $value;
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function cmd(string $command, $expected, string $label, bool $lenient = false): string {
        $this->writeRaw($command . "\r\n");
        try {
            return $this->expect($expected, $label);
        } catch (Throwable $e) {
            if ($lenient) {
                return '';
            }
            throw $e;
        }
    }

    private function expect($expected, string $label): string {
        $expected = (array) $expected;
        $response = '';
        $code = 0;

        // Balasan multi-baris: baca sampai baris "NNN " (spasi, bukan dash).
        for ($i = 0; $i < 25; $i++) {
            $line = $this->readLine();
            if ($line === '') {
                break;
            }
            $response .= $line . "\n";
            if (preg_match('/^(\d{3})([ -])/', $line, $m)) {
                $code = (int) $m[1];
                if ($m[2] === ' ') {
                    break;
                }
            }
        }

        if (!in_array($code, $expected, true)) {
            throw new RuntimeException("SMTP {$label} gagal (kode {$code}): " . trim($response));
        }
        return $response;
    }

    private function readLine(): string {
        $line = @fgets($this->sock, 2048);
        if ($line === false) {
            $meta = @stream_get_meta_data($this->sock);
            if (!empty($meta['timed_out'])) {
                throw new RuntimeException('Timeout menunggu balasan server SMTP.');
            }
            throw new RuntimeException('Koneksi SMTP terputus.');
        }
        return rtrim($line, "\r\n");
    }

    private function writeRaw(string $data): void {
        if (@fwrite($this->sock, $data) === false) {
            throw new RuntimeException('Gagal menulis ke koneksi SMTP.');
        }
    }

    private function close(): void {
        if (is_resource($this->sock)) {
            @fclose($this->sock);
        }
        $this->sock = null;
    }

    public function __destruct() {
        $this->close();
    }
}