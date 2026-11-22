<?php
$page_title = 'Tautkan WhatsApp - SIP-Barang Admin';
$active_tab = 'whatsapp';

// Autentikasi HARUS dimuat sebelum header: handler QR mengirim gambar,
// jadi tidak boleh ada HTML yang sudah tercetak lebih dulu.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/wa.php';

// Hanya Super Admin: halaman ini menampilkan QR yang bisa dipakai menautkan HP.
require_super_admin('index.php');

$cfg = sip_wa_config();
$sessionId = trim((string) ($cfg['session_id'] ?? ''));
$aksi = (string) ($_GET['action'] ?? '');

// Mulai sesi (bila belum jalan) — dipakai tombol "Muat ulang QR".
if ($aksi === 'start' && $sessionId !== '') {
    require_csrf();
    sip_wa_request('POST', '/api/sessions/' . rawurlencode($sessionId) . '/start');
    header('Location: tautkan-wa.php');
    exit;
}

// Ambil QR terkini langsung dari gateway (API key tidak pernah sampai ke browser).
if ($aksi === 'qr' && $sessionId !== '') {
    require_csrf();
    $res = sip_wa_request('GET', '/api/sessions/' . rawurlencode($sessionId) . '/qr');
    $dataUrl = is_array($res['body']) ? (string) ($res['body']['qrCode'] ?? '') : '';
    if (preg_match('#^data:image/png;base64,#', $dataUrl)) {
        header('Content-Type: image/png');
        header('Cache-Control: no-store');
        echo base64_decode(substr($dataUrl, strlen('data:image/png;base64,')));
        exit;
    }
    http_response_code(404);
    exit('QR belum tersedia.');
}

// Minta kode pairing 8 karakter (alternatif QR) untuk nomor pengirim.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pairing' && $sessionId !== '') {
    require_csrf();
    $nomor = sip_wa_normalize_number((string) ($_POST['phone'] ?? ($cfg['sender'] ?? '')));
    if ($nomor === '') {
        $pairError = 'Nomor tidak valid. Contoh: 085199816361.';
    } else {
        $res = sip_wa_request('POST', '/api/sessions/' . rawurlencode($sessionId) . '/pairing-code', ['phoneNumber' => $nomor]);
        if ($res['ok'] && is_array($res['body']) && !empty($res['body']['pairingCode'])) {
            $pairCode = (string) $res['body']['pairingCode'];
            $pairAt = time();
        } else {
            $pairError = $res['error'] !== '' ? $res['error'] : 'Kode pairing tidak bisa dibuat.';
        }
    }
}

$status = $sessionId !== '' ? sip_wa_session_status() : ['ok' => false, 'error' => 'ID sesi belum diisi.', 'status' => '', 'phone' => ''];
$siap = ($status['status'] ?? '') === 'ready';
$qrTersedia = ($status['status'] ?? '') === 'qr_ready';

// Baru sekarang cetak HTML halaman.
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';
?>
<div class="flex-1 overflow-y-auto">
    <div class="px-8 py-6 max-w-3xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Tautkan WhatsApp</h1>
            <p class="text-sm text-slate-500 mt-1">Hubungkan nomor <strong>085199816361</strong> ke gateway OpenWA dengan memindai QR.</p>
        </div>

        <div class="bg-white rounded-card border border-slate-200 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Status sesi</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        <?php if ($siap): ?>
                            Terhubung<?= ($status['phone'] ?? '') !== '' ? ' sebagai ' . htmlspecialchars((string) $status['phone']) : '' ?>. Notifikasi WhatsApp aktif.
                        <?php elseif ($qrTersedia): ?>
                            Menunggu pindai QR. Kode berganti sendiri kira-kira tiap 20 detik.
                        <?php else: ?>
                            Sesi belum siap — <?= htmlspecialchars((string) ($status['error'] ?: $status['status'] ?: 'tidak diketahui')) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <span class="text-[11px] font-bold px-2.5 py-1 rounded-full shrink-0 <?= $siap ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                    <?= $siap ? 'TERHUBUNG' : strtoupper(htmlspecialchars((string) ($status['status'] ?: 'OFFLINE'))) ?>
                </span>
            </div>

            <?php if ($siap): ?>
                <div class="mt-5 p-3 rounded-btn bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">
                    Nomor sudah tertaut. Pesan uji bisa dikirim dari halaman <a href="pengaturan-wa.php" class="font-semibold underline">Pengaturan WhatsApp</a>.
                </div>
            <?php else: ?>
                <!-- Kode pairing: lebih cepat daripada scan QR -->
                <div class="mt-5 p-4 rounded-xl border border-slate-200 bg-slate-50">
                    <p class="text-xs font-bold text-slate-800">Cara cepat: kode pairing (tanpa kamera)</p>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Minta kode, lalu di HP buka <em>WhatsApp → Setelan → Perangkat tertaut → Tautkan perangkat →
                        Tautkan dengan nomor telepon</em>, dan masukkan kode itu. Kode berlaku singkat (±1 menit).
                    </p>

                    <?php if (!empty($pairCode)): ?>
                        <div class="mt-3 p-3 rounded-lg bg-white border border-emerald-300 text-center">
                            <p class="text-[11px] text-slate-500">Kode pairing untuk <?= htmlspecialchars((string) ($cfg['sender'] ?? '')) ?></p>
                            <p class="font-mono text-3xl font-bold tracking-[0.25em] text-slate-900 mt-1"><?= htmlspecialchars($pairCode) ?></p>
                            <p class="text-[11px] text-slate-400 mt-1">Dibuat <?= date('H:i:s', $pairAt) ?> — kalau sudah lewat, minta kode baru.</p>
                        </div>
                    <?php elseif (!empty($pairError)): ?>
                        <div class="mt-3 p-2.5 rounded-lg bg-red-50 border border-red-200 text-[11px] text-red-800">
                            <?= htmlspecialchars($pairError) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="mt-3 flex flex-wrap items-center gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="pairing">
                        <input type="tel" name="phone" value="<?= htmlspecialchars((string) ($cfg['sender'] ?? '')) ?>" class="input-default h-9 px-3 text-xs w-44" placeholder="085199816361">
                        <button type="submit" class="btn-primary h-9 px-4 rounded-btn text-xs font-semibold">Minta kode pairing</button>
                    </form>
                </div>

                <div class="mt-5 flex flex-col sm:flex-row items-center gap-6">
                    <div class="shrink-0 p-3 rounded-xl border border-slate-200 bg-white">
                        <img id="qr" src="<?= csrf_link('tautkan-wa.php?action=qr') ?>&t=<?= time() ?>" alt="QR WhatsApp" class="w-56 h-56 object-contain" onerror="this.src='<?= csrf_link('tautkan-wa.php?action=qr') ?>&t=' + Date.now()">
                    </div>
                    <div class="text-xs text-slate-600 space-y-2">
                        <p class="font-semibold text-slate-800">Cara menautkan</p>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Buka WhatsApp di HP yang memakai nomor <strong>085199816361</strong>.</li>
                            <li>Masuk ke <em>Setelan → Perangkat tertaut → Tautkan perangkat</em>.</li>
                            <li>Arahkan kamera ke QR di samping.</li>
                        </ol>
                        <p class="text-[11px] text-slate-400">QR ini berisi kode masuk ke akun. Jangan dibagikan ke orang lain.</p>
                        <div class="flex gap-2 pt-1">
                            <a href="<?= csrf_link('tautkan-wa.php?action=start') ?>" class="btn-primary h-9 px-3 rounded-btn text-xs font-semibold">Muat ulang sesi</a>
                            <button type="button" onclick="document.getElementById('qr').src='<?= csrf_link('tautkan-wa.php?action=qr') ?>&t=' + Date.now()" class="btn-secondary h-9 px-3 rounded-btn text-xs font-semibold">Segarkan QR</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>