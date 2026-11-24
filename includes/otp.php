<?php
/**
 * Layanan OTP: pembuatan kode, pengiriman email, verifikasi, dan pembatasan.
 *
 * Aturan:
 *   - Kode 6 digit acak, berlaku 10 menit (expires_at).
 *   - Mengirim kode baru otomatis membatalkan kode lama yang belum terpakai.
 *   - Percobaan salah dibatasi 5 kali per kode, disimpan di database
 *     sehingga tidak bisa diakali dengan menghapus cookie sesi.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

const SIP_OTP_TTL_SECONDS = 600;      // 10 menit
const SIP_OTP_MAX_ATTEMPTS = 5;
const SIP_OTP_RESEND_COOLDOWN = 60;   // detik

/**
 * Domain email kampus yang boleh mendaftar.
 * Ubah lewat mail-config.php: 'allowed_email_domains' => ['ui.ac.id', ...]
 * atau env SIP_ALLOWED_EMAIL_DOMAINS="ui.ac.id,staff.ui.ac.id".
 */
function sip_allowed_email_domains(): array {
    $cfg = sip_mail_config();
    $list = $cfg['allowed_email_domains'] ?? [];
    if (!is_array($list)) {
        $list = explode(',', (string) $list);
    }

    $env = getenv('SIP_ALLOWED_EMAIL_DOMAINS');
    if (is_string($env) && trim($env) !== '') {
        $list = explode(',', $env);
    }

    if (empty($list)) {
        $list = ['ui.ac.id'];
    }

    $out = [];
    foreach ($list as $d) {
        $d = strtolower(trim((string) $d, " \t\n\r\0\x0B@."));
        if ($d !== '' && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $d)) {
            $out[] = $d;
        }
    }
    return array_values(array_unique($out));
}

/** Apakah email memakai domain kampus yang diizinkan? */
function sip_email_domain_allowed(string $email): bool {
    $email = strtolower(trim($email));
    $at = strrpos($email, '@');
    if ($at === false) {
        return false;
    }
    $domain = substr($email, $at + 1);
    foreach (sip_allowed_email_domains() as $allowed) {
        if ($domain === $allowed || str_ends_with($domain, '.' . $allowed)) {
            return true;
        }
    }
    return false;
}

/** Pastikan kolom baru tersedia (migrasi ringan, idempoten). */
function sip_otp_ensure_schema(): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $db = get_db();
    $isSqlite = (Database::getDriver() === 'sqlite');

    try {
        if ($isSqlite) {
            $cols = [];
            foreach ($db->query("PRAGMA table_info(otp_codes)") as $c) {
                $cols[] = $c['name'];
            }
            if (!in_array('expires_at', $cols, true)) {
                $db->exec("ALTER TABLE otp_codes ADD COLUMN expires_at DATETIME NULL");
            }
            if (!in_array('attempts', $cols, true)) {
                $db->exec("ALTER TABLE otp_codes ADD COLUMN attempts INTEGER NOT NULL DEFAULT 0");
            }

            $ucols = [];
            foreach ($db->query("PRAGMA table_info(users)") as $c) {
                $ucols[] = $c['name'];
            }
            if (!in_array('email_verified_at', $ucols, true)) {
                $db->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL");
            }
        } else {
            $cols = [];
            foreach ($db->query("SHOW COLUMNS FROM otp_codes") as $c) {
                $cols[] = $c["Field"];
            }
            if (!in_array("expires_at", $cols, true)) {
                $db->exec("ALTER TABLE otp_codes ADD COLUMN expires_at DATETIME NULL");
            }
            if (!in_array("attempts", $cols, true)) {
                $db->exec("ALTER TABLE otp_codes ADD COLUMN attempts INT NOT NULL DEFAULT 0");
            }

            $ucols = [];
            foreach ($db->query("SHOW COLUMNS FROM users") as $c) {
                $ucols[] = $c["Field"];
            }
            if (!in_array("email_verified_at", $ucols, true)) {
                $db->exec("ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL");
            }
        }
    } catch (Throwable $e) {
        // Kolom sudah ada (MySQL tidak punya IF NOT EXISTS untuk ADD COLUMN): abaikan.
    }

    // Backfill sekali saja: akun yang sudah ada sebelum fitur verifikasi aktif
    // dianggap terverifikasi, supaya pengguna lama tidak tiba-tiba terkunci.
    // Penanda berupa berkas agar tidak mengulang (dan tidak ikut memverifikasi
    // akun baru yang memang belum memverifikasi email).
    $marker = __DIR__ . '/.otp-backfill-done';
    if (!is_file($marker)) {
        try {
            db_execute(
                "UPDATE users SET email_verified_at = COALESCE(created_at, ?) WHERE email_verified_at IS NULL",
                [date('Y-m-d H:i:s')]
            );
            @file_put_contents($marker, date('c'));
        } catch (Throwable $e) {
            // Biarkan; akan dicoba lagi pada permintaan berikutnya.
        }
    }
}

/** Buat kode 6 digit acak. */
function sip_otp_generate(): string {
    return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Terbitkan kode baru untuk sebuah email.
 * Mengembalikan ['ok' => bool, 'code' => string, 'error' => string].
 */
function sip_otp_issue(string $email, string $type = 'register'): array {
    sip_otp_ensure_schema();
    $email = strtolower(trim($email));

    // Batalkan semua kode lama agar hanya satu yang berlaku.
    db_execute("UPDATE otp_codes SET is_used = 1 WHERE email = ? AND is_used = 0", [$email]);

    $code = sip_otp_generate();
    $expiresAt = date('Y-m-d H:i:s', time() + SIP_OTP_TTL_SECONDS);

    db_execute(
        "INSERT INTO otp_codes (email, code, type, is_used, attempts, expires_at) VALUES (?, ?, ?, 0, 0, ?)",
        [$email, $code, $type, $expiresAt]
    );

    return ['ok' => true, 'code' => $code, 'error' => ''];
}

/** Kode yang masih berlaku (belum terpakai dan belum kedaluwarsa). */
function sip_otp_latest_valid(string $email, string $type = 'register'): ?array {
    sip_otp_ensure_schema();
    $email = strtolower(trim($email));

    $row = db_fetch_one(
        "SELECT * FROM otp_codes WHERE email = ? AND type = ? AND is_used = 0 ORDER BY id DESC LIMIT 1",
        [$email, $type]
    );
    if (!$row) {
        return null;
    }

    $expires = $row['expires_at'] ?? null;
    if (!empty($expires) && strtotime((string) $expires) < time()) {
        return null; // kedaluwarsa
    }
    return $row;
}

/**
 * Verifikasi kode. Mengembalikan ['ok' => bool, 'error' => string, 'expired' => bool].
 */
function sip_otp_verify(string $email, string $code, string $type = 'register'): array {
    sip_otp_ensure_schema();
    $email = strtolower(trim($email));
    $code = preg_replace('/\D/', '', $code);

    if (strlen((string) $code) !== 6) {
        return ['ok' => false, 'error' => 'Kode harus 6 angka.', 'expired' => false];
    }

    $row = db_fetch_one(
        "SELECT * FROM otp_codes WHERE email = ? AND type = ? AND is_used = 0 ORDER BY id DESC LIMIT 1",
        [$email, $type]
    );

    if (!$row) {
        return ['ok' => false, 'error' => 'Kode tidak ditemukan. Minta kode baru.', 'expired' => true];
    }

    // Kedaluwarsa?
    $expires = $row['expires_at'] ?? null;
    if (!empty($expires) && strtotime((string) $expires) < time()) {
        return ['ok' => false, 'error' => 'Kode sudah kedaluwarsa. Minta kode baru.', 'expired' => true];
    }

    // Batas percobaan di level database.
    $attempts = (int) ($row['attempts'] ?? 0);
    if ($attempts >= SIP_OTP_MAX_ATTEMPTS) {
        return ['ok' => false, 'error' => 'Terlalu banyak percobaan untuk kode ini. Minta kode baru.', 'expired' => true];
    }

    if (!hash_equals((string) $row['code'], (string) $code)) {
        db_execute("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?", [$row['id']]);
        $left = max(0, SIP_OTP_MAX_ATTEMPTS - ($attempts + 1));
        return ['ok' => false, 'error' => "Kode salah. Sisa percobaan: {$left}.", 'expired' => false];
    }

    db_execute("UPDATE otp_codes SET is_used = 1 WHERE id = ?", [$row['id']]);
    return ['ok' => true, 'error' => '', 'expired' => false];
}

/** Tandai email pengguna sudah terverifikasi. */
function sip_mark_email_verified(string $email): void {
    sip_otp_ensure_schema();
    db_execute(
        "UPDATE users SET email_verified_at = ? WHERE LOWER(email) = ? AND (email_verified_at IS NULL OR email_verified_at = '')",
        [date('Y-m-d H:i:s'), strtolower(trim($email))]
    );
}

/** Apakah email pengguna sudah terverifikasi? Akun lama tanpa kolom dianggap terverifikasi. */
function sip_email_is_verified(array $user): bool {
    if (!array_key_exists('email_verified_at', $user)) {
        return true; // kolom belum ada / data lama
    }
    return !empty($user['email_verified_at']);
}

/** Batas kirim ulang: apakah kode terakhir masih terlalu baru? */
function sip_otp_resend_blocked(string $email, string $type = 'register'): int {
    sip_otp_ensure_schema();
    $row = db_fetch_one(
        "SELECT created_at FROM otp_codes WHERE email = ? AND type = ? ORDER BY id DESC LIMIT 1",
        [strtolower(trim($email)), $type]
    );
    if (!$row || empty($row['created_at'])) {
        return 0;
    }
    $elapsed = time() - strtotime((string) $row['created_at']);
    $left = SIP_OTP_RESEND_COOLDOWN - $elapsed;
    return $left > 0 ? (int) $left : 0;
}

/** Template email OTP. */
function sip_otp_email_body(string $code, string $purpose = 'register', string $nama = ''): string {
    $isReset = ($purpose === 'reset_password');
    $title = $isReset ? 'Atur Ulang Kata Sandi' : 'Verifikasi Email Kamu';
    $intro = $isReset
        ? 'Kami menerima permintaan untuk mengatur ulang kata sandi akun SIP-Barang Kampus kamu.'
        : 'Terima kasih sudah mendaftar di SIP-Barang Kampus. Masukkan kode berikut untuk mengaktifkan akunmu.';
    $sapaan = $nama !== '' ? 'Halo ' . htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') . ',' : 'Halo,';

    return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 0;">
    <tr><td align="center">
      <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
        <tr><td style="background:#2563eb;padding:22px 28px;">
          <div style="color:#ffffff;font-size:17px;font-weight:700;">SIP-Barang Kampus</div>
        </td></tr>
        <tr><td style="padding:28px;">
          <h1 style="margin:0 0 12px;font-size:20px;color:#0f172a;">' . $title . '</h1>
          <p style="margin:0 0 14px;font-size:14px;color:#475569;line-height:1.6;">' . $sapaan . '</p>
          <p style="margin:0 0 20px;font-size:14px;color:#475569;line-height:1.6;">' . $intro . '</p>
          <div style="background:#eff6ff;border:1px dashed #93c5fd;border-radius:10px;padding:18px;text-align:center;">
            <div style="font-size:11px;letter-spacing:1.5px;color:#1d4ed8;font-weight:700;margin-bottom:6px;">KODE VERIFIKASI</div>
            <div style="font-size:34px;letter-spacing:8px;font-weight:700;color:#1e3a8a;font-family:Consolas,monospace;">' . $code . '</div>
            <div style="font-size:12px;color:#1d4ed8;margin-top:8px;">Salin kode: <span style="font-family:Consolas,monospace;font-weight:700;">' . $code . '</span></div>
          </div>
          <p style="margin:16px 0 0;font-size:13px;color:#334155;line-height:1.6;">Masukkan kode berikut di halaman verifikasi: <strong style="font-family:Consolas,monospace;font-size:15px;color:#0f172a;">' . $code . '</strong></p>
          <p style="margin:20px 0 0;font-size:13px;color:#64748b;line-height:1.6;">Kode ini berlaku <strong>10 menit</strong> dan hanya bisa dipakai sekali.</p>
          <p style="margin:8px 0 0;font-size:13px;color:#64748b;line-height:1.6;">Jika kamu tidak merasa melakukan pendaftaran, abaikan email ini.</p>
        </td></tr>
        <tr><td style="padding:16px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;">
          <p style="margin:0;font-size:11px;color:#94a3b8;">Email otomatis dari SIP-Barang Kampus. Jangan balas email ini.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>';
}

/** Kirim kode OTP ke email. */
function sip_otp_send(string $email, string $code, string $purpose = 'register', string $nama = ''): array {
    $subject = ($purpose === 'reset_password')
        ? 'Kode Atur Ulang Kata Sandi - SIP-Barang Kampus'
        : 'Kode Verifikasi Akun - SIP-Barang Kampus';

    $html = sip_otp_email_body($code, $purpose, $nama);
    $teksSapaan = $nama !== '' ? "Halo {$nama}," : 'Halo,';
    $teksAksi = ($purpose === 'reset_password')
        ? 'Kami menerima permintaan mengatur ulang kata sandi akun SIP-Barang Kampus kamu.'
        : 'Terima kasih sudah mendaftar di SIP-Barang Kampus.';
    $teks = "SIP-Barang Kampus\n\n"
          . "{$teksSapaan}\n\n"
          . "{$teksAksi}\n\n"
          . "KODE VERIFIKASI: {$code}\n\n"
          . "Masukkan kode 6 digit di atas pada halaman verifikasi.\n"
          . "Kode berlaku 10 menit dan hanya bisa dipakai sekali.\n\n"
          . "Jika kamu tidak merasa melakukan pendaftaran, abaikan email ini.\n"
          . "Email otomatis, jangan dibalas.\n";

    return sip_send_mail($email, $subject, $html, $teks);
}