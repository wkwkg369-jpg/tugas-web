<?php
$page_title = 'Pengaturan Email - SIP-Barang Admin';
$active_tab = 'email';
require_once __DIR__ . '/includes/admin_header.php';

// Halaman ini menyentuh kredensial sistem, jadi hanya Super Admin.
// Petugas pos dan admin pusat ditolak di sini walau mengetik URL langsung.
require_super_admin('index.php');

require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/../includes/otp.php';

$testResult = null;
$saveError = '';
$saveOk = false;

// Simpan konfigurasi SMTP dari panel admin.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan') {
    if (!can_manage_mail_config()) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
    $res = sip_mail_config_write([
        'mode'       => $_POST['mode'] ?? 'demo',
        'host'       => $_POST['host'] ?? '',
        'port'       => $_POST['port'] ?? 587,
        'secure'     => $_POST['secure'] ?? 'tls',
        'username'   => $_POST['username'] ?? '',
        'password'   => $_POST['password'] ?? '',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name'  => $_POST['from_name'] ?? 'SIP-Barang Kampus',
        'allowed_email_domains' => $_POST['allowed_email_domains'] ?? [],
    ]);
    if ($res['ok']) {
        $saveOk = true;
    } else {
        $saveError = $res['error'];
    }
}

$cfg = sip_mail_config(true);
$ready = sip_mail_ready();

// Kirim email uji ke alamat admin yang sedang login.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test') {
    $to = (string) ($currentUser['email'] ?? '');
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $testResult = ['ok' => false, 'error' => 'Akun admin ini tidak punya alamat email yang valid.'];
    } else {
        $html = sip_otp_email_body('123456', 'register', $currentUser['nama'] ?? '');
        $testResult = sip_send_mail($to, 'Uji Pengiriman Email - SIP-Barang Kampus', $html, "Ini email uji dari SIP-Barang Kampus.\nJika kamu menerima ini, pengiriman email sudah berjalan.");
    }
}

// Kredensial tidak pernah ditampilkan utuh.
$mask = function (string $v): string {
    $v = trim($v);
    if ($v === '') return '—';
    if (strlen($v) <= 4) return str_repeat('•', strlen($v));
    return substr($v, 0, 2) . str_repeat('•', max(4, strlen($v) - 4)) . substr($v, -2);
};

$lastError = '';
$errFile = __DIR__ . '/../logs/mail-error.log';
if (is_file($errFile)) {
    $lines = @file($errFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lastError = $lines ? (string) end($lines) : '';
}

if (!function_exists('sip_otp_ensure_schema')) {
    sip_otp_ensure_schema();
}
$stats = db_fetch_one(
    "SELECT
        SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) AS terpakai,
        SUM(CASE WHEN is_used = 0 THEN 1 ELSE 0 END) AS aktif,
        COUNT(*) AS total
     FROM otp_codes"
) ?: ['terpakai' => 0, 'aktif' => 0, 'total' => 0];
$unverified = db_fetch_one("SELECT COUNT(*) AS c FROM users WHERE email_verified_at IS NULL")['c'] ?? 0;
$domains = sip_allowed_email_domains();

// Cadangan saat SMTP belum aktif: petugas bisa menyampaikan kode secara manual.
$pendingCodes = [];
if (!$ready) {
    $pendingCodes = db_fetch_all(
        "SELECT email, code, type, expires_at, attempts FROM otp_codes
         WHERE is_used = 0 AND (expires_at IS NULL OR expires_at > ?)
         ORDER BY id DESC LIMIT 15",
        [date('Y-m-d H:i:s')]
    );
}
?>
<div class="flex-1 overflow-y-auto">
    <div class="px-8 py-6 max-w-4xl">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Pengaturan Email</h1>
            <p class="text-sm text-slate-500 mt-1">Atur pengiriman kode verifikasi dan kode atur ulang kata sandi.</p>
        </div>

        <?php if ($saveOk): ?>
            <div class="mb-5 p-3.5 rounded-btn border text-sm bg-emerald-50 border-emerald-200 text-emerald-800">
                Konfigurasi email tersimpan. Status di bawah sudah diperbarui.
            </div>
        <?php elseif ($saveError !== ''): ?>
            <div class="mb-5 p-3.5 rounded-btn border text-sm bg-red-50 border-red-200 text-red-800">
                <?= htmlspecialchars($saveError) ?>
            </div>
        <?php endif; ?>

        <!-- Status -->
        <div class="bg-white rounded-card border border-slate-200 p-6 mb-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Status pengiriman</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        <?php if ($ready): ?>
                            SMTP aktif. Kode dikirim langsung ke kotak masuk email pengguna.
                        <?php else: ?>
                            Mode <strong>demo</strong>: kode dibuat dan disimpan, tetapi email tidak terkirim.
                        <?php endif; ?>
                    </p>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 <?= $ready ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                    <?= $ready ? 'SMTP AKTIF' : 'MODE DEMO' ?>
                </span>
            </div>

            <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-xs">
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Mode</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars((string) $cfg['mode']) ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Server</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($cfg['host'] !== '' ? $cfg['host'] : '—') ?>:<?= (int) $cfg['port'] ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Keamanan</dt><dd class="font-semibold text-slate-800"><?= $cfg['secure'] === 'ssl' ? 'TLS langsung (465)' : ($cfg['secure'] === 'none' ? 'Tanpa TLS' : 'STARTTLS (587)') ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Akun SMTP</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($mask((string) $cfg['username'])) ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Kata sandi aplikasi</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($mask((string) $cfg['password'])) ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Pengirim</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($cfg['from_email'] !== '' ? $cfg['from_email'] : '—') ?></dd>
                </div>
            </dl>

            <form method="POST" class="mt-5 flex flex-wrap items-center gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="test">
                <button type="submit" class="btn-primary h-10 px-4 rounded-btn text-sm font-semibold" <?= $ready ? '' : 'disabled style="opacity:.5;cursor:not-allowed"' ?>>
                    Kirim Email Uji ke <?= htmlspecialchars((string) ($currentUser['email'] ?? 'akun saya')) ?>
                </button>
                <span class="text-xs text-slate-500">Memakai template yang sama dengan kode verifikasi.</span>
            </form>

            <?php if ($testResult !== null): ?>
                <div class="mt-4 p-3 rounded-btn text-xs <?= $testResult['ok'] ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
                    <?= $testResult['ok'] ? 'Email uji berhasil dikirim. Periksa kotak masuk (dan folder spam).' : 'Gagal mengirim: ' . htmlspecialchars((string) $testResult['error']) ?>
                </div>
            <?php endif; ?>

            <?php if ($lastError !== ''): ?>
                <div class="mt-3 p-3 rounded-btn bg-slate-50 border border-slate-200 text-[11px] text-slate-600 font-mono break-all">
                    Kesalahan terakhir: <?= htmlspecialchars($lastError) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Form konfigurasi -->
        <div class="bg-white rounded-card border border-slate-200 p-6 mb-5">
            <h2 class="text-sm font-bold text-slate-900">Konfigurasi SMTP</h2>
            <p class="text-xs text-slate-500 mt-1 mb-4">
                Untuk Gmail gunakan <code class="bg-slate-100 px-1 rounded">smtp.gmail.com</code>, port 587, STARTTLS,
                dan <strong>App Password</strong> 16 karakter (bukan kata sandi Gmail biasa).
            </p>

            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="simpan">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Mode</label>
                        <select name="mode" class="input-default">
                            <option value="demo" <?= $cfg['mode'] === 'demo' ? 'selected' : '' ?>>Demo (tanpa kirim email)</option>
                            <option value="smtp" <?= $cfg['mode'] === 'smtp' ? 'selected' : '' ?>>SMTP (kirim sungguhan)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Keamanan</label>
                        <select name="secure" class="input-default">
                            <option value="tls" <?= $cfg['secure'] === 'tls' ? 'selected' : '' ?>>STARTTLS (port 587)</option>
                            <option value="ssl" <?= $cfg['secure'] === 'ssl' ? 'selected' : '' ?>>TLS langsung (port 465)</option>
                            <option value="none" <?= $cfg['secure'] === 'none' ? 'selected' : '' ?>>Tanpa TLS (relay lokal)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Server SMTP</label>
                        <input type="text" name="host" value="<?= htmlspecialchars((string) $cfg['host']) ?>" placeholder="smtp.gmail.com" class="input-default">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Port</label>
                        <input type="number" name="port" value="<?= (int) $cfg['port'] ?>" min="1" max="65535" class="input-default">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Akun SMTP (email)</label>
                        <input type="email" name="username" value="<?= htmlspecialchars((string) $cfg['username']) ?>" placeholder="sipkampus@gmail.com" class="input-default">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">App Password</label>
                        <input type="password" name="password" value="" autocomplete="new-password"
                               placeholder="<?= $cfg['password'] !== '' ? 'Tersimpan — isi hanya untuk mengganti' : '16 karakter dari Google' ?>"
                               class="input-default">
                        <p class="text-[11px] text-slate-500 mt-1">Kosongkan bila tidak ingin mengubah.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat pengirim</label>
                        <input type="email" name="from_email" value="<?= htmlspecialchars((string) $cfg['from_email']) ?>" placeholder="sipkampus@gmail.com" class="input-default">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama pengirim</label>
                        <input type="text" name="from_name" value="<?= htmlspecialchars((string) $cfg['from_name']) ?>" class="input-default">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Domain email kampus yang boleh daftar</label>
                    <input type="text" name="allowed_email_domains" value="<?= htmlspecialchars(implode(', ', $domains)) ?>" placeholder="ui.ac.id" class="input-default">
                    <p class="text-[11px] text-slate-500 mt-1">Pisahkan dengan koma bila lebih dari satu.</p>
                </div>

                <div class="pt-1">
                    <button type="submit" class="btn-primary h-10 px-5 rounded-btn text-sm font-semibold">Simpan Konfigurasi</button>
                </div>
            </form>
        </div>

        <!-- Cadangan: kode aktif bisa disampaikan manual saat SMTP belum aktif -->
        <?php if (!$ready): ?>
        <div class="bg-white rounded-card border border-slate-200 p-6 mb-5">
            <h2 class="text-sm font-bold text-slate-900">Kode yang menunggu verifikasi</h2>
            <p class="text-xs text-slate-500 mt-1 mb-4">
                Karena pengiriman email belum aktif, kode di bawah belum sampai ke pengguna.
                Sampaikan lewat WhatsApp/telepon kampus, atau minta pengguna memilih
                &ldquo;Kirim Ulang Kode&rdquo; setelah SMTP dikonfigurasi.
            </p>
            <?php if (empty($pendingCodes)): ?>
                <p class="text-xs text-slate-500 italic">Tidak ada kode aktif saat ini.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3 font-semibold">Email</th>
                                <th class="py-2 pr-3 font-semibold">Kode</th>
                                <th class="py-2 pr-3 font-semibold">Jenis</th>
                                <th class="py-2 pr-3 font-semibold">Berlaku sampai</th>
                                <th class="py-2 font-semibold">Percobaan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pendingCodes as $row): ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-3 text-slate-700"><?= htmlspecialchars((string) $row['email']) ?></td>
                                <td class="py-2 pr-3 font-mono font-bold text-slate-900 tracking-wider"><?= htmlspecialchars((string) $row['code']) ?></td>
                                <td class="py-2 pr-3 text-slate-600"><?= $row['type'] === 'reset_password' ? 'Atur ulang sandi' : 'Verifikasi daftar' ?></td>
                                <td class="py-2 pr-3 text-slate-600"><?= htmlspecialchars((string) ($row['expires_at'] ?? '—')) ?></td>
                                <td class="py-2 text-slate-600"><?= (int) ($row['attempts'] ?? 0) ?>/5</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-btn p-2.5">
                    Halaman ini hanya untuk admin. Setelah SMTP aktif, bagian ini tidak ditampilkan lagi.
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="bg-white rounded-card border border-slate-200 p-6">
            <h2 class="text-sm font-bold text-slate-900">Statistik kode verifikasi</h2>
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="p-3 rounded-btn bg-slate-50 border border-slate-200">
                    <div class="text-xl font-bold text-slate-900"><?= (int) $stats['total'] ?></div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Total kode</div>
                </div>
                <div class="p-3 rounded-btn bg-emerald-50 border border-emerald-200">
                    <div class="text-xl font-bold text-emerald-700"><?= (int) $stats['terpakai'] ?></div>
                    <div class="text-[11px] text-emerald-700/70 mt-0.5">Sudah dipakai</div>
                </div>
                <div class="p-3 rounded-btn bg-amber-50 border border-amber-200">
                    <div class="text-xl font-bold text-amber-700"><?= (int) $stats['aktif'] ?></div>
                    <div class="text-[11px] text-amber-700/70 mt-0.5">Belum dipakai</div>
                </div>
                <div class="p-3 rounded-btn bg-blue-50 border border-blue-200">
                    <div class="text-xl font-bold text-blue-700"><?= (int) $unverified ?></div>
                    <div class="text-[11px] text-blue-700/70 mt-0.5">Email belum terverifikasi</div>
                </div>
            </div>
            <p class="mt-4 text-[11px] text-slate-500 leading-relaxed">
                Database: <?= htmlspecialchars(Database::getDriver()) ?>. Kode berlaku 10 menit, hanya bisa dipakai sekali,
                dan permintaan kode baru otomatis membatalkan kode lama.
            </p>
        </div>

    </div>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>