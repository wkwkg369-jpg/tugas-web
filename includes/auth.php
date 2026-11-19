<?php
/**
 * Authentication & Session Management
 * SIP-Barang Kampus
 */

if (!ob_get_level()) {
    ob_start();
}

require_once __DIR__ . '/db.php';

// Safe session save path for portable / XAMPP / sandbox environments
$sessionDir = __DIR__ . '/../sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0777, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir) && session_status() === PHP_SESSION_NONE) {
    @session_save_path($sessionDir);
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
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
 * Check if user is an admin or officer
 */
function is_admin(): bool {
    $user = get_logged_in_user();
    return $user !== null && in_array($user['role'], ['admin', 'petugas']);
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
 * Attempt to log in a user with email or NIM
 */
function login_user(string $identifier, string $password): array {
    $identifier = trim($identifier);
    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'message' => 'Email/NIM dan kata sandi wajib diisi.'];
    }

    $user = db_fetch_one("SELECT * FROM users WHERE email = ? OR nim = ?", [$identifier, $identifier]);
    if (!$user) {
        return ['success' => false, 'message' => 'Akun dengan email atau NIM tersebut tidak ditemukan.'];
    }

    // Verify password (supports hash and plain text fallback for testing)
    $passwordValid = password_verify($password, $user['password']) || ($user['password'] === $password);
    if (!$passwordValid) {
        return ['success' => false, 'message' => 'Kata sandi yang Anda masukkan salah.'];
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

    // Generate random 6-digit OTP
    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    db_execute("INSERT INTO otp_codes (email, code, type) VALUES (?, ?, 'register')", [$email, $otp]);
    $_SESSION['pending_otp'] = [
        'email' => $email,
        'code' => $otp,
        'user_id' => $newId
    ];

    // Otomatis login sementara
    $_SESSION['user_id'] = $newId;
    $_SESSION['user'] = $newUser;

    return ['success' => true, 'user' => $newUser, 'otp' => $otp];
}

/**
 * Log out user
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['user_id'], $_SESSION['user']);
    session_destroy();
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
