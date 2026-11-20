<?php
/**
 * Authentication & Session Management
 * SIP-Barang Kampus
 */

if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/otp.php';

// Safe session save path for portable / XAMPP / sandbox environments
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0770, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir) && session_status() === PHP_SESSION_NONE) {
    @session_save_path($sessionDir);
}

if (session_status() === PHP_SESSION_NONE) {
    // Cookie sesi tidak bisa dibaca JavaScript, tidak dikirim lintas situs,
    // dan hanya lewat HTTPS saat permintaan memang HTTPS (di belakang proxy).
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    @session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $isHttps,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

// Header keamanan dasar (dikirim sekali, sebelum ada output).
if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// ---------------------------------------------------------------------------
// Proteksi CSRF
// ---------------------------------------------------------------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    return (string) $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_link(string $url): string {
    return $url . (strpos($url, '?') === false ? '?' : '&') . 'csrf_token=' . urlencode(csrf_token());
}

function csrf_valid(?string $token): bool {
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

/**
 * Tolak permintaan bila token tidak cocok. Setiap POST diverifikasi otomatis
 * di bawah; aksi yang memakai GET memanggil ini secara eksplisit.
 */
function require_csrf(): void {
    $token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
    if (!csrf_valid(is_string($token) ? $token : null)) {
        http_response_code(403);
        exit('Permintaan ditolak: token keamanan (CSRF) tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

// Semua perubahan data lewat POST wajib membawa token CSRF.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
}

/**
 * Get current logged in user array or null
 */
function get_logged_in_user(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    // Check if user session cache is still valid
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    $user = db_fetch_one("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    if ($user) {
        unset($user['password']);
        $_SESSION['user'] = $user;
        return $user;
    }

    // Invalid session
    unset($_SESSION['user_id'], $_SESSION['user']);
    return null;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return get_logged_in_user() !== null;
}

/**
 * Daftar peran yang boleh masuk panel admin.
 * super_admin sengaja ikut, karena ia peran paling tinggi.
 */
function admin_panel_roles(): array {
    return ['super_admin', 'admin', 'petugas'];
}

/**
 * Check if user is an admin or officer
 */
function is_admin(): bool {
    $user = get_logged_in_user();
    return $user !== null && in_array($user['role'], admin_panel_roles(), true);
}

/**
 * Hanya super admin yang boleh mengubah konfigurasi sistem,
 * misalnya kredensial SMTP.
 */
function is_super_admin(): bool {
    $user = get_logged_in_user();
    return $user !== null && ($user['role'] ?? '') === 'super_admin';
}

/**
 * Enforce login on protected pages
 */
function require_login(string $redirect = 'masuk.php'): void {
    if (!is_logged_in()) {
        set_flash_message('warning', 'Silakan masuk ke akun Anda terlebih dahulu.');
        header("Location: {$redirect}");
        exit;
    }
}

/**
 * Enforce admin access
 */
function require_admin(string $redirect = '../masuk.php'): void {
    if (!is_admin()) {
        set_flash_message('error', 'Akses ditolak. Halaman ini hanya untuk administrator/petugas kampus.');
        header("Location: {$redirect}");
        exit;
    }
}

/**
 * Enforce super admin access.
 * Dipakai halaman yang menyentuh konfigurasi sistem, seperti email/SMTP.
 * Admin pusat dan petugas pos ditolak di sini, bukan hanya disembunyikan menunya.
 */
function require_super_admin(string $redirect = '../admin/index.php'): void {
    if (!is_super_admin()) {
        set_flash_message('error', 'Akses ditolak. Halaman ini hanya untuk Super Admin.');
        header("Location: {$redirect}");
        exit;
    }
}

/**
 * Cek apakah pengguna yang login boleh mengubah konfigurasi SMTP.
 * Dipakai juga untuk menahan permintaan POST langsung, bukan hanya tampilan.
 */
function can_manage_mail_config(): bool {
    return is_super_admin();
}

/**
 * Batas percobaan login: maksimal 5 gagal per (identitas + IP) dalam 15 menit.
 * Disimpan di database agar tidak bisa dilewati hanya dengan menghapus cookie.
 */
function ensure_login_attempts_table(): void {
    try {
        get_db()->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            attempt_key TEXT PRIMARY KEY,
            attempts INTEGER NOT NULL DEFAULT 0,
            first_at INTEGER NOT NULL,
            last_at INTEGER NOT NULL
        )");
    } catch (Throwable $e) {
        // Tabel sudah ada pada engine lain; abaikan.
    }
}

function login_attempt_key(string $identifier): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $ip = trim(explode(',', (string) $ip)[0]);
    return hash('sha256', strtolower($identifier) . '|' . $ip);
}

function is_login_throttled(string $identifier): bool {
    ensure_login_attempts_table();
    $key = login_attempt_key($identifier);
    $row = db_fetch_one("SELECT attempts, first_at FROM login_attempts WHERE attempt_key = ?", [$key]);
    if (!$row) {
        return false;
    }
    if ((time() - (int) $row['first_at']) > 900) {
        db_execute("DELETE FROM login_attempts WHERE attempt_key = ?", [$key]);
        return false;
    }
    return (int) $row['attempts'] >= 5;
}

function record_login_failure(string $identifier): void {
    ensure_login_attempts_table();
    $key = login_attempt_key($identifier);
    $now = time();
    $row = db_fetch_one("SELECT attempts, first_at FROM login_attempts WHERE attempt_key = ?", [$key]);
    if (!$row) {
        db_execute("INSERT INTO login_attempts (attempt_key, attempts, first_at, last_at) VALUES (?, 1, ?, ?)", [$key, $now, $now]);
        return;
    }
    if (($now - (int) $row['first_at']) > 900) {
        db_execute("UPDATE login_attempts SET attempts = 1, first_at = ?, last_at = ? WHERE attempt_key = ?", [$now, $now, $key]);
        return;
    }
    db_execute("UPDATE login_attempts SET attempts = attempts + 1, last_at = ? WHERE attempt_key = ?", [$now, $key]);
}

function clear_login_failures(string $identifier): void {
    ensure_login_attempts_table();
    db_execute("DELETE FROM login_attempts WHERE attempt_key = ?", [login_attempt_key($identifier)]);
}

/**
 * Attempt to log in a user with email or NIM
 */
function login_user(string $identifier, string $password): array {
    $identifier = trim($identifier);
    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'message' => 'Email/NIM dan kata sandi wajib diisi.'];
    }

    if (is_login_throttled($identifier)) {
        return ['success' => false, 'message' => 'Terlalu banyak percobaan masuk. Coba lagi dalam 15 menit.'];
    }

    $user = db_fetch_one("SELECT * FROM users WHERE email = ? OR nim = ?", [$identifier, $identifier]);
    if (!$user) {
        record_login_failure($identifier);
        // Pesan seragam agar penyerang tidak bisa menebak akun mana yang ada.
        return ['success' => false, 'message' => 'Email/NIM atau kata sandi yang Anda masukkan salah.'];
    }

    // Hanya hash password yang diterima (fallback plaintext dihapus).
    if (!password_verify($password, (string) $user['password'])) {
        record_login_failure($identifier);
        return ['success' => false, 'message' => 'Email/NIM atau kata sandi yang Anda masukkan salah.'];
    }

    // Akun yang dinonaktifkan admin tidak boleh masuk.
    if (isset($user['is_active']) && (int) $user['is_active'] === 0) {
        return ['success' => false, 'message' => 'Akun ini dinonaktifkan. Hubungi petugas keamanan kampus.'];
    }

    // Mahasiswa wajib sudah memverifikasi email (kode OTP). Akun petugas/admin
    // dibuat oleh admin sehingga tidak melewati verifikasi email.
    $role = (string) ($user['role'] ?? 'mahasiswa');
    if ($role === 'mahasiswa'
        && array_key_exists('email_verified_at', $user)
        && empty($user['email_verified_at'])
    ) {
        // Terbitkan kode baru supaya pengguna bisa langsung menyelesaikan verifikasi.
        $issued = sip_otp_issue((string) $user['email'], 'register');
        sip_otp_send((string) $user['email'], $issued['code'], 'register', (string) ($user['nama'] ?? ''));
        $_SESSION['pending_otp'] = ['email' => $user['email'], 'code' => $issued['code']];

        return [
            'success' => false,
            'message' => 'Email belum diverifikasi. Kami sudah mengirim kode baru ke email kampus Anda.',
            'verify_email' => $user['email'],
        ];
    }

    clear_login_failures($identifier);

    // Cegah session fixation: ganti ID sesi setelah autentikasi berhasil.
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_regenerate_id(true);
    }

    // Login success
    $_SESSION['user_id'] = $user['id'];
    unset($user['password']);
    $_SESSION['user'] = $user;

    return ['success' => true, 'user' => $user];
}

/**
 * Register a new student user
 */
function register_user(array $data): array {
    $nama = trim($data['nama'] ?? '');
    $nim = trim($data['nim'] ?? '');
    $prodi = trim($data['prodi'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($nama) || empty($nim) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Semua kolom bertanda bintang (*) wajib diisi.'];
    }

    // Hanya email kampus yang boleh mendaftar: kanal verifikasi satu-satunya
    // adalah email, jadi alamat di luar domain resmi ditolak.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format email tidak valid.'];
    }
    if (!sip_email_domain_allowed($email)) {
        return [
            'success' => false,
            'message' => 'Gunakan email kampus (' . implode(', ', array_map(fn($d) => '@' . $d, sip_allowed_email_domains())) . ').'
        ];
    }

    // Check if email already used
    $existingEmail = db_fetch_one("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existingEmail) {
        return ['success' => false, 'message' => 'Email kampus tersebut sudah terdaftar. Silakan gunakan email lain atau masuk.'];
    }

    // Check if NIM already used
    $existingNim = db_fetch_one("SELECT id FROM users WHERE nim = ?", [$nim]);
    if ($existingNim) {
        return ['success' => false, 'message' => 'NIM tersebut sudah terdaftar dalam sistem.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ok = db_execute("INSERT INTO users (nama, nim, prodi, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, 'mahasiswa')", [
        $nama, $nim, $prodi, $email, $phone, $hash
    ]);

    if (!$ok) {
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem saat menyimpan akun. Silakan coba lagi.'];
    }

    $newId = db_last_insert_id();
    $newUser = db_fetch_one("SELECT * FROM users WHERE id = ?", [$newId]);
    unset($newUser['password']);

    // Terbitkan kode 6 digit (berlaku 10 menit) lalu kirim lewat email.
    // Kode disimpan sebagai OTP aktif; kode lama otomatis dibatalkan.
    $issued = sip_otp_issue($email, 'register');
    $sent = sip_otp_send($email, $issued['code'], 'register', $nama);
    $_SESSION['pending_otp'] = ['email' => $email, 'code' => $issued['code']];

    // Akun BELUM login. Login hanya diberikan setelah kode diverifikasi di
    // verifikasi-otp.php, supaya verifikasi email benar-benar mengunci akses.
    return [
        'success'   => true,
        'user'      => $newUser,
        'email_sent' => $sent['ok'],
        'mail_error' => $sent['ok'] ? '' : $sent['error'],
    ];
}

/**
 * Log out user
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Validasi berkas unggahan: ekstensi, ukuran, dan isi (MIME) harus cocok.
 * Ekstensi saja tidak cukup karena isi berkas bisa dipalsukan.
 */
function validate_upload(array $file, array $allowedExt, int $maxBytes): array {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Berkas wajib diunggah.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Gagal mengunggah berkas (kode ' . (int) $file['error'] . ').'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Berkas unggahan tidak valid.'];
    }
    if ((int) $file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'Ukuran berkas maksimal ' . (int) round($maxBytes / 1048576) . ' MB.'];
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'error' => 'Format berkas harus ' . strtoupper(implode(', ', $allowedExt)) . '.'];
    }
    $mimeMap = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'webp' => ['image/webp'], 'pdf' => ['application/pdf'],
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($file['tmp_name']);
    if (!in_array($mime, $mimeMap[$ext] ?? [], true)) {
        return ['ok' => false, 'error' => 'Isi berkas tidak cocok dengan ekstensinya.'];
    }
    if ($ext !== 'pdf' && @getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'Berkas gambar tidak dapat dibaca.'];
    }
    return ['ok' => true, 'ext' => $ext, 'error' => ''];
}

/** Pastikan folder unggahan ada dan bisa ditulis. */
function ensure_upload_dir(): string {
    $dir = __DIR__ . '/../assets/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    return $dir;
}

/**
 * Flash message helpers
 */
function set_flash_message(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'error', 'warning', 'info'
        'message' => $message
    ];
}

function get_flash_message(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}
