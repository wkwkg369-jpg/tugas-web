<?php
/**
 * Akun Super Admin — kata sandi TIDAK boleh ada di kode/repo.
 *
 * Sumber kata sandi: environment variable SUPER_ADMIN_PASSWORD.
 * - Disimpan sebagai hash bcrypt (password_hash / PASSWORD_BCRYPT) di kolom
 *   users.password; plaintext tidak pernah ditulis ke database, log, atau berkas.
 * - Dibaca ulang setiap login agar perubahan env langsung berlaku tanpa deploy ulang.
 * - Bila env kosong/terlalu pendek, akun super admin TIDAK dibuat dan TIDAK bisa
 *   login (gagal tertutup), bukan memakai kata sandi bawaan yang bisa ditebak.
 *
 * Identitas akun dapat diganti lewat SUPER_ADMIN_EMAIL / SUPER_ADMIN_NAME
 * tanpa menyentuh kode.
 */

require_once __DIR__ . '/db.php';

const SIP_SUPER_ADMIN_MIN_LENGTH = 12;

/** Nama env yang menampung kata sandi super admin. */
function sip_super_admin_password_env(): string {
    return 'SUPER_ADMIN_PASSWORD';
}

/** Nilai SUPER_ADMIN_PASSWORD, atau '' bila tidak diatur / tidak memenuhi syarat. */
function sip_super_admin_password(): string {
    $pass = getenv(sip_super_admin_password_env());
    if (!is_string($pass)) {
        $pass = '';
    }
    if (strlen($pass) < SIP_SUPER_ADMIN_MIN_LENGTH) {
        return '';
    }
    return $pass;
}

/** Email identitas super admin (default: akun yang berlaku saat ini). */
function sip_super_admin_email(): string {
    $email = trim((string) getenv('SUPER_ADMIN_EMAIL'));
    return $email !== '' ? $email : 'super.admin@ui.ac.id';
}

/** Nama tampilan super admin. */
function sip_super_admin_name(): string {
    $name = trim((string) getenv('SUPER_ADMIN_NAME'));
    return $name !== '' ? $name : 'Super Admin Sistem';
}

/** ID akun super_admin utama (satu-satunya yang kata sandinya dikelola dari env). */
function sip_super_admin_id(): int {
    $row = db_fetch_one("SELECT id FROM users WHERE email = ? AND role = 'super_admin'", [sip_super_admin_email()]);
    return $row ? (int) $row['id'] : 0;
}

/**
 * Samakan hash kata sandi akun super admin dengan isi SUPER_ADMIN_PASSWORD.
 *
 * Idempoten dan dibatasi biaya: password_verify() dijalankan dulu, sehingga
 * hash baru hanya dibuat bila hash lama memang tidak cocok lagi (bukan setiap
 * permintaan halaman). Hash lama otomatis tidak berlaku -> kata sandi lama mati.
 *
 * @return bool true bila hash akun sudah sesuai env
 */
function ensure_super_admin_password(): bool {
    $password = sip_super_admin_password();
    if ($password === '') {
        return false;
    }

    $row = db_fetch_one("SELECT id, password FROM users WHERE email = ? AND role = 'super_admin'", [sip_super_admin_email()]);
    if (!$row) {
        return false;
    }

    // Jangan pernah menyimpan hash yang sudah cocok (verify murah, re-hash tidak).
    if (password_verify($password, (string) $row['password'])) {
        return true;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    if ($hash === false) {
        return false;
    }

    return db_execute("UPDATE users SET password = ? WHERE id = ?", [$hash, (int) $row['id']]);
}

/**
 * Ganti kata sandi super admin berdasarkan env, atau hitung ulang hash-nya.
 * Dipanggil dari login sebelum password_verify() agar perubahan env langsung berlaku.
 */
function rotate_super_admin_password_if_needed(): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        ensure_super_admin_password();
    } catch (Throwable $e) {
        // Gagal menyiapkan akun tidak boleh memblokir login pengguna lain.
        error_log('sip_super_admin: ' . $e->getMessage());
    }
}

/**
 * Bolehkan login super admin memakai SUPER_ADMIN_PASSWORD meski hash akun belum
 * disinkronkan? Tidak — hash di database adalah satu-satunya sumber kebenaran,
 * jadi kedua jalur login tidak bisa berbeda membolehkan kata sandi yang sama.
 */
function super_admin_login_is_env_managed(?array $user): bool {
    return $user !== null && ($user['role'] ?? '') === 'super_admin';
}
