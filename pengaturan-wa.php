<?php
$page_title = 'Nomor WhatsApp - SIP-Barang Kampus';
$current_page = 'wa';
require_once __DIR__ . '/includes/header.php';

$currentUser = get_logged_in_user();
if (!$currentUser) {
    header('Location: masuk.php');
    exit;
}

$userId = (int) $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $aksi = (string) ($_POST['action'] ?? '');

    if ($aksi === 'simpan') {
        $nomor = trim((string) ($_POST['phone'] ?? ''));
        if ($nomor === '') {
            // Mengosongkan nomor = berhenti menerima WhatsApp.
            db_execute("UPDATE users SET phone = '' WHERE id = ?", [$userId]);
            set_flash_message('success', 'Nomor WhatsApp dihapus. Kamu tidak akan menerima notifikasi WhatsApp.');
        } elseif (sip_wa_normalize_number($nomor) === '') {
            set_flash_message('error', 'Nomor tidak valid. Contoh: 085199816361 atau +62 851-9981-6361.');
        } else {
            db_execute("UPDATE users SET phone = ? WHERE id = ?", [sip_wa_normalize_number($nomor), $userId]);
            set_flash_message('success', 'Nomor WhatsApp disimpan. Notifikasi laporan dan klaim akan dikirim ke nomor ini.');
        }
    } elseif ($aksi === 'test') {
        $hasil = sip_wa_send_text((string) ($currentUser['phone'] ?? ''), sip_wa_format_message(
            sip_wa_config(),
            'Uji Notifikasi WhatsApp',
            'Ini pesan uji dari SIP-Barang Kampus. Kalau kamu membaca ini, notifikasi WhatsApp sudah berjalan.',
            'notifikasi.php'
        ));
        if ($hasil['skipped']) {
            set_flash_message('info', 'Pengiriman WhatsApp belum diaktifkan oleh admin.');
        } elseif ($hasil['ok']) {
            set_flash_message('success', 'Pesan uji terkirim ke WhatsApp kamu.');
        } else {
            set_flash_message('error', 'Pesan uji gagal: ' . $hasil['error']);
        }
    }

    header('Location: pengaturan-wa.php');
    exit;
}

$terdaftar = trim((string) ($currentUser['phone'] ?? ''));
$waAktif = sip_wa_ready();

require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-10">
    <div class="max-w-[640px] mx-auto px-4 sm:px-6">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Notifikasi WhatsApp</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Dapatkan pemberitahuan status laporan barang hilang, temuan, dan klaim langsung di WhatsApp.
            </p>
        </div>

        <div class="card-default rounded-card bg-white border border-slate-200 shadow-sm p-6 space-y-5">
            <?php if (!$waAktif): ?>
                <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800">
                    Pengiriman WhatsApp belum diaktifkan admin. Nomor tetap bisa disimpan sekarang dan langsung dipakai begitu fitur dinyalakan.
                </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 text-xs text-slate-500">
                <i data-lucide="info" class="w-4 h-4 text-slate-400"></i>
                <span>Nomor WhatsApp hanya dipakai untuk notifikasi resmi SIP-Barang Kampus.</span>
            </div>

            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="simpan">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nomor WhatsApp</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($terdaftar) ?>" placeholder="085199816361" class="input-default w-full">
                    <p class="text-[11px] text-slate-400 mt-1">Format bebas: 08…, 62…, atau +62…. Kosongkan untuk berhenti menerima WhatsApp.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn-primary h-10 px-5 text-sm font-semibold rounded-btn">Simpan nomor</button>
                    <?php if ($waAktif && sip_wa_normalize_number($terdaftar) !== ''): ?>
                        <button type="submit" name="action" value="test" class="btn-secondary h-10 px-5 text-sm font-semibold rounded-btn">Kirim pesan uji</button>
                    <?php endif; ?>
                </div>
            </form>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Nomor resmi pengirim: <strong class="text-slate-700">085199816361</strong></span>
                <a href="notifikasi.php" class="text-primary font-semibold hover:underline">Buka pusat notifikasi</a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
