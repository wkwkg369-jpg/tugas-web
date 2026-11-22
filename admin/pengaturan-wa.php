<?php
$page_title = 'Pengaturan WhatsApp - SIP-Barang Admin';
$active_tab = 'whatsapp';
require_once __DIR__ . '/includes/admin_header.php';

// Menyentuh kredensial gateway, jadi sama seperti SMTP: hanya Super Admin.
require_super_admin('index.php');

require_once __DIR__ . '/includes/admin_sidebar.php';
require_once __DIR__ . '/../includes/wa.php';

$saveError = '';
$saveOk = false;
$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'simpan') {
    $res = sip_wa_config_write([
        'mode'            => $_POST['mode'] ?? 'off',
        'base_url'        => $_POST['base_url'] ?? '',
        'api_key'         => $_POST['api_key'] ?? '',
        'session_id'      => $_POST['session_id'] ?? '',
        'sender'          => $_POST['sender'] ?? '',
        'timeout'         => $_POST['timeout'] ?? 8,
        'notify_admins'   => isset($_POST['notify_admins']),
        'admin_number'    => $_POST['admin_number'] ?? '',
        'public_base_url' => $_POST['public_base_url'] ?? '',
    ]);
    if ($res['ok']) {
        $saveOk = true;
    } else {
        $saveError = $res['error'];
    }
}

$cfg = sip_wa_config();
$ready = sip_wa_ready();
$health = $ready ? sip_wa_session_status() : ['ok' => false, 'error' => 'Mode OpenWA belum aktif.', 'status' => '', 'phone' => ''];

// Kirim pesan uji ke nomor WhatsApp admin yang sedang login.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'test') {
    $to = (string) ($currentUser['phone'] ?? '');
    if (sip_wa_normalize_number($to) === '') {
        $testResult = ['ok' => false, 'error' => 'Akun admin ini belum punya nomor WhatsApp. Isi nomor dulu dari menu pengguna.'];
    } else {
        $testResult = sip_wa_send_text($to, sip_wa_format_message(
            $cfg,
            'Uji Pengiriman WhatsApp',
            'Ini pesan uji dari SIP-Barang Kampus. Bila kamu menerima ini, gateway OpenWA sudah terhubung.',
            'notifikasi.php'
        ));
        if ($testResult['skipped']) {
            $testResult = ['ok' => false, 'error' => 'Mode OpenWA belum aktif atau sesi belum diatur.'];
        }
    }
}

$mask = function (string $v): string {
    $v = trim($v);
    if ($v === '') return '—';
    if (strlen($v) <= 4) return str_repeat('•', strlen($v));
    return substr($v, 0, 6) . str_repeat('•', max(4, strlen($v) - 8)) . substr($v, -2);
};

$lastError = '';
$errFile = __DIR__ . '/../logs/wa-error.log';
if (is_file($errFile)) {
    $lines = @file($errFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $lastError = $lines ? (string) end($lines) : '';
}

$userStats = db_fetch_one(
    "SELECT COUNT(*) AS total,
            SUM(CASE WHEN phone IS NOT NULL AND phone <> '' THEN 1 ELSE 0 END) AS bernomor
     FROM users WHERE role IN ('admin', 'petugas', 'super_admin')"
) ?: ['total' => 0, 'bernomor' => 0];
?>
<div class="flex-1 overflow-y-auto">
    <div class="px-8 py-6 max-w-4xl">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Pengaturan WhatsApp</h1>
            <p class="text-sm text-slate-500 mt-1">Gateway <a href="https://open-wa.org" target="_blank" rel="noopener" class="text-primary hover:underline">OpenWA</a> untuk notifikasi status barang hilang, temuan, dan klaim.</p>
        </div>

        <?php if ($saveOk): ?>
            <div class="mb-5 p-3.5 rounded-btn border text-sm bg-emerald-50 border-emerald-200 text-emerald-800">
                Konfigurasi WhatsApp tersimpan. Status di bawah sudah diperbarui.
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
                    <h2 class="text-sm font-bold text-slate-900">Status gateway</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        <?php if ($ready): ?>
                            Notifikasi in-app juga dikirim ke WhatsApp nomor pengguna yang terdaftar.
                        <?php else: ?>
                            Nonaktif: hanya notifikasi in-app yang berjalan.
                        <?php endif; ?>
                    </p>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 <?= $ready ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                    <?= $ready ? 'OPENWA AKTIF' : 'NONAKTIF' ?>
                </span>
            </div>

            <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-xs">
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Alamat gateway</dt><dd class="font-semibold text-slate-800 break-all"><?= htmlspecialchars((string) $cfg['base_url']) ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">ID sesi</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars((string) ($cfg['session_id'] ?: '—')) ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">API key</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars($mask((string) $cfg['api_key'])) ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Nomor pengirim</dt><dd class="font-semibold text-slate-800"><?= htmlspecialchars((string) $cfg['sender']) ?></dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Status sesi</dt>
                    <dd class="font-semibold <?= ($health['status'] ?? '') === 'ready' ? 'text-emerald-700' : 'text-amber-700' ?>">
                        <?= htmlspecialchars(($health['status'] ?? '') !== '' ? (string) $health['status'] : ($health['error'] ?? '—')) ?>
                    </dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="text-slate-500">Staf dengan nomor WA</dt><dd class="font-semibold text-slate-800"><?= (int) ($userStats['bernomor'] ?? 0) ?> / <?= (int) ($userStats['total'] ?? 0) ?></dd>
                </div>
            </dl>

            <form method="POST" class="mt-5 flex flex-wrap items-center gap-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="test">
                <button type="submit" class="btn-primary h-10 px-4 rounded-btn text-sm font-semibold" <?= $ready ? '' : 'disabled style="opacity:.5;cursor:not-allowed"' ?>>
                    Kirim Pesan Uji ke <?= htmlspecialchars((string) (($currentUser['phone'] ?? '') ?: 'nomor saya')) ?>
                </button>
                <span class="text-xs text-slate-500">Memakai format pesan yang sama dengan notifikasi.</span>
            </form>

            <?php if ($testResult !== null): ?>
                <div class="mt-4 p-3 rounded-btn text-xs <?= $testResult['ok'] ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
                    <?= $testResult['ok'] ? 'Pesan uji diterima gateway OpenWA. Cek WhatsApp tujuan.' : 'Gagal mengirim: ' . htmlspecialchars((string) $testResult['error']) ?>
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
            <h2 class="text-sm font-bold text-slate-900">Konfigurasi OpenWA</h2>
            <p class="text-xs text-slate-500 mt-1 mb-4">
                Jalankan OpenWA di server yang sama, lalu isi API key dari <code class="bg-slate-100 px-1 rounded">data/.api-key</code>
                dan ID sesi hasil scan QR nomor <strong>085199816361</strong>.
            </p>

            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="simpan">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Mode</label>
                    <select name="mode" class="input-default w-full">
                        <option value="off" <?= ($cfg['mode'] ?? 'off') === 'off' ? 'selected' : '' ?>>Nonaktif (in-app saja)</option>
                        <option value="openwa" <?= ($cfg['mode'] ?? 'off') === 'openwa' ? 'selected' : '' ?>>OpenWA (kirim ke WhatsApp)</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Alamat gateway</label>
                        <input type="text" name="base_url" value="<?= htmlspecialchars((string) $cfg['base_url']) ?>" placeholder="http://127.0.0.1:2785" class="input-default w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nomor pengirim</label>
                        <input type="text" name="sender" value="<?= htmlspecialchars((string) $cfg['sender']) ?>" placeholder="085199816361" class="input-default w-full">
                        <p class="text-[11px] text-slate-400 mt-1">Harus sama dengan nomor yang di-scan di OpenWA.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">API key</label>
                    <input type="password" name="api_key" value="" placeholder="<?= $mask((string) $cfg['api_key']) === '—' ? 'owa_k1_…' : 'Biarkan kosong untuk mempertahankan yang tersimpan' ?>" class="input-default w-full font-mono" autocomplete="off">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">ID sesi</label>
                        <input type="text" name="session_id" value="<?= htmlspecialchars((string) $cfg['session_id']) ?>" placeholder="8f3c2b1a-…" class="input-default w-full font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Batas waktu (detik)</label>
                        <input type="number" name="timeout" min="3" max="60" value="<?= (int) $cfg['timeout'] ?>" class="input-default w-full">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Alamat situs (tautan di pesan)</label>
                    <input type="text" name="public_base_url" value="<?= htmlspecialchars((string) $cfg['public_base_url']) ?>" placeholder="https://aralfian.my.id" class="input-default w-full">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nomor WhatsApp admin (opsional)</label>
                    <input type="tel" name="admin_number" value="<?= htmlspecialchars((string) ($cfg['admin_number'] ?? '')) ?>" placeholder="085199816361" class="input-default w-full">
                    <p class="text-[11px] text-slate-500 mt-1">Bila diisi, notifikasi staf hanya dikirim ke nomor ini, bukan ke nomor admin di database.</p>
                </div>

                <label class="flex items-start gap-2.5 text-xs text-slate-600">
                    <input type="checkbox" name="notify_admins" value="1" class="mt-0.5" <?= !empty($cfg['notify_admins']) ? 'checked' : '' ?>>
                    <span>Teruskan juga notifikasi laporan/klaim baru ke WhatsApp staf pos (maks. <?= SIP_WA_MAX_ADMIN_RECIPIENTS ?> nomor unik).</span>
                </label>

                <div class="pt-2">
                    <button type="submit" class="btn-primary h-10 px-5 rounded-btn text-sm font-semibold">Simpan konfigurasi</button>
                </div>
            </form>
        </div>

        <div class="bg-slate-50 rounded-card border border-slate-200 p-5 text-xs text-slate-600 leading-relaxed">
            <p class="font-semibold text-slate-800 mb-2">Catatan operasional</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Kirim dari nomor khusus, tidak dipakai sehari-hari. OpenWA tidak resmi dari WhatsApp dan tidak bisa menjamin akun bebas dari larangan.</li>
                <li>Sesi harus berstatus <code class="bg-white px-1 rounded">ready</code>. Bila terputus, buka dashboard OpenWA dan scan ulang QR.</li>
                <li>Notifikasi hanya terkirim ke nomor yang diisi pengguna di halaman <em>Notifikasi WhatsApp</em>.</li>
            </ol>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
