<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php';

// Wajib login admin/petugas SEBELUM handler apa pun dijalankan.
require_admin('../masuk.php');

$successReceipt = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = intval($_POST['item_id'] ?? 0);
    $namaPenerima = trim($_POST['nama_penerima'] ?? '');
    $nimPenerima = trim($_POST['nim_penerima'] ?? '');
    $kontakPenerima = trim($_POST['kontak_penerima'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');
    $petugas = trim($_POST['petugas_penyerah'] ?? 'Petugas Pos Keamanan');

    $item = get_item_by_id($itemId);
    if ($item && !empty($namaPenerima)) {
        // Update item status to 'Dikembalikan'
        db_execute("UPDATE items SET status = 'Dikembalikan', tempat_simpan = ? WHERE id = ?", [
            "Sudah diserahkan ke pemilik ({$namaPenerima}) oleh {$petugas}",
            $itemId
        ]);

        // Close any associated claims as 'Selesai'
        db_execute("UPDATE claims SET status = 'Selesai', catatan_petugas = ? WHERE item_id = ?", [
            "Barang telah resmi diserahkan kepada {$namaPenerima} ({$nimPenerima}) pada " . date('d M Y H:i'),
            $itemId
        ]);

        $noBa = 'BA-' . date('Ymd') . '-' . random_int(100, 999);

        // Send notifications to user(s)
        $notifiedUsers = [];

        // 1. Check associated claims for user IDs
        $claimUsers = db_fetch_all("SELECT DISTINCT user_id FROM claims WHERE item_id = ? AND user_id IS NOT NULL", [$itemId]);
        foreach ($claimUsers as $cu) {
            $uid = intval($cu['user_id']);
            if ($uid > 0 && !in_array($uid, $notifiedUsers)) {
                $notifiedUsers[] = $uid;
            }
        }

        // 2. Check user by NIM
        if (!empty($nimPenerima)) {
            $u = db_fetch_one("SELECT id FROM users WHERE nim = ?", [$nimPenerima]);
            if ($u && !in_array(intval($u['id']), $notifiedUsers)) {
                $notifiedUsers[] = intval($u['id']);
            }
        }

        // 3. Check user by Nama
        if (!empty($namaPenerima)) {
            $u = db_fetch_one("SELECT id FROM users WHERE LOWER(nama) = LOWER(?)", [$namaPenerima]);
            if ($u && !in_array(intval($u['id']), $notifiedUsers)) {
                $notifiedUsers[] = intval($u['id']);
            }
        }

        // Send notification to recipient / claimant
        foreach ($notifiedUsers as $uid) {
            db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread, link) VALUES (?, ?, ?, 'check-circle', 1, 'riwayat.php?tab=klaim')", [
                $uid,
                'Barang Anda Telah Diserahkan!',
                "Barang '{$item['nama']}' (#{$item['kode']}) telah resmi diserahkan kepada {$namaPenerima}" . (!empty($nimPenerima) ? " ({$nimPenerima})" : "") . " oleh petugas {$petugas}. No. Berita Acara: {$noBa}. Terima kasih telah menggunakan layanan SIP-Barang Kampus."
            ]);
        }

        // 4. Send notification to reporter (pelapor temuan)
        if (!empty($item['pelapor_id']) && !in_array(intval($item['pelapor_id']), $notifiedUsers)) {
            db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread, link) VALUES (?, ?, ?, 'package-check', 1, 'riwayat.php?tab=laporan')", [
                $item['pelapor_id'],
                'Barang Temuan Telah Diserahkan ke Pemilik!',
                "Kabar baik! Barang temuan yang Anda laporkan '{$item['nama']}' (#{$item['kode']}) telah resmi diserahkan kembali kepada pemilik sah ({$namaPenerima}). Terima kasih banyak atas integritas dan kepedulian Anda!"
            ]);
        }

        // 5. Notifikasi untuk admin & petugas: barang sudah diserahkan
        notify_admins(
            'Penyerahan Barang Dicatat',
            "Barang '{$item['nama']}' (#{$item['kode']}) telah diserahkan kepada {$namaPenerima}" . (!empty($nimPenerima) ? " ({$nimPenerima})" : "") . " oleh {$petugas}. No. Berita Acara: {$noBa}.",
            'hand-helping',
            'admin/index.php'
        );

        $successReceipt = [
            'no_ba' => $noBa,
            'item_nama' => $item['nama'],
            'item_kode' => $item['kode'],
            'penerima' => $namaPenerima,
            'nim' => $nimPenerima,
            'kontak' => $kontakPenerima,
            'petugas' => $petugas,
            'catatan' => $catatan,
            'waktu' => date('d F Y, H:i') . ' WIB',
            'notified_count' => count($notifiedUsers) + (!empty($item['pelapor_id']) ? 1 : 0)
        ];

        set_flash_message('success', "Berita acara penyerahan barang '{$item['nama']}' berhasil dicatat dan notifikasi telah dikirimkan ke pengguna!");
    }
}

$page_title = 'Catat Penyerahan Barang - SIP-Barang Admin';
$active_tab = 'penyerahan';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Items available for handover
$availableItems = db_fetch_all("SELECT * FROM items WHERE status IN ('Ditemukan', 'Dalam Verifikasi') ORDER BY id DESC");
?>

<div class="flex-1 flex flex-col overflow-y-auto">
    <!-- Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-6 sm:px-8 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Catat Penyerahan Barang (Serah Terima)</h1>
            <p class="text-xs text-slate-500">Dokumentasikan pengembalian fisik barang temuan ke pemilik sah & kirim notifikasi otomatis</p>
        </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-4xl mx-auto w-full">
        
        <?php if ($successReceipt): ?>
            <!-- Receipt Banner matching Figma -->
            <div class="card-default p-6 bg-white rounded-card border-2 border-emerald-300 shadow-sm space-y-4">
                <div class="flex items-center gap-3 text-emerald-700">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center font-bold">
                        <i data-lucide="check" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Berita Acara Serah Terima Resmi Terbit</h2>
                        <p class="text-xs text-slate-500">Nomor Registrasi: <span class="font-mono font-bold text-emerald-700"><?= htmlspecialchars($successReceipt['no_ba']) ?></span></p>
                    </div>
                </div>

                <!-- Notifikasi Alert Info -->
                <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2.5">
                    <i data-lucide="bell-ring" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span><strong>Notifikasi Terkirim:</strong> Sistem telah otomatis mengirim pemberitahuan penyerahan ke akun pengguna terkait (penerima dan pelapor).</span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <div><span class="text-slate-400">Barang:</span> <strong class="text-slate-800"><?= htmlspecialchars($successReceipt['item_nama']) ?> (#<?= htmlspecialchars($successReceipt['item_kode']) ?>)</strong></div>
                        <div><span class="text-slate-400">Penerima:</span> <strong class="text-slate-800"><?= htmlspecialchars($successReceipt['penerima']) ?> (<?= htmlspecialchars($successReceipt['nim']) ?>)</strong></div>
                        <div><span class="text-slate-400">Petugas Penyerah:</span> <strong class="text-slate-800"><?= htmlspecialchars($successReceipt['petugas']) ?></strong></div>
                        <div><span class="text-slate-400">Waktu:</span> <strong class="text-slate-800"><?= htmlspecialchars($successReceipt['waktu']) ?></strong></div>
                    </div>
                    <?php if (!empty($successReceipt['catatan'])): ?>
                        <div class="pt-2 border-t border-slate-200 text-slate-600">
                            <strong>Catatan:</strong> <?= htmlspecialchars($successReceipt['catatan']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="window.print()" class="btn-secondary h-9 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1.5">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                        <span>Cetak Bukti Serah Terima</span>
                    </button>
                    <a href="catat-penyerahan.php" class="btn-primary h-9 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1.5">
                        <span>Catat Lagi</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Serah Terima (Figma: Form Serah Terima) -->
        <div class="card-default p-6 sm:p-8 bg-white rounded-card border border-slate-200 shadow-sm">
            <form action="catat-penyerahan.php" method="POST" class="space-y-5">
                <?= csrf_field() ?>
                
                <!-- Pilih Barang -->
                <div>
                    <label class="block text-xs font-bold text-slate-900 mb-1.5">
                        Pilih Barang Temuan yang Diserahkan <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select name="item_id" required class="input-default text-xs md:text-sm bg-white">
                            <option value="" disabled selected>Pilih Barang Temuan</option>
                            <?php foreach ($availableItems as $it): ?>
                                <option value="<?= $it['id'] ?>">
                                    #<?= htmlspecialchars($it['kode']) ?> - <?= htmlspecialchars($it['nama']) ?> (Status: <?= htmlspecialchars($it['status']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 2 Kolom: Nama Penerima & Identitas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-900 mb-1.5">
                            Nama Lengkap Penerima (Pemilik Sah) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nama_penerima" required placeholder="Budi Pratama" class="input-default text-xs md:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-900 mb-1.5">
                            NIM / No. Identitas Penerima <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nim_penerima" required placeholder="2024080123 / No. KTP" class="input-default text-xs md:text-sm">
                    </div>
                </div>

                <!-- 2 Kolom: Kontak & Petugas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-900 mb-1.5">
                            Nomor WhatsApp / HP Penerima <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" name="kontak_penerima" required placeholder="081234567890" class="input-default text-xs md:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-900 mb-1.5">
                            Petugas yang Menyerahkan <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="petugas_penyerah" value="<?= htmlspecialchars($currentUser['nama'] ?? 'Petugas Pos Keamanan') ?>" required class="input-default text-xs md:text-sm bg-slate-50">
                    </div>
                </div>

                <!-- Catatan Berita Acara -->
                <div>
                    <label class="block text-xs font-bold text-slate-900 mb-1.5">
                        Catatan & Berita Acara Penyerahan
                    </label>
                    <textarea name="catatan" rows="3" placeholder="Penerima menunjukkan KTM asli dan membuktikan kepemilikan. Fisik barang diserahkan dalam kondisi baik..." class="input-default text-xs md:text-sm">Penerima telah menunjukkan KTM asli serta mencocokkan ciri khusus. Fisik barang telah diserahkan dalam kondisi lengkap dan baik.</textarea>
                </div>

                <!-- Info Notifikasi Otomatis -->
                <div class="p-3 bg-blue-50/70 border border-blue-200/70 rounded-xl flex items-center gap-2.5 text-xs text-blue-800">
                    <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0"></i>
                    <span>Notifikasi penyelesaian serah terima akan otomatis dikirimkan ke akun <strong>Penerima / Pemilik</strong> dan <strong>Pelapor Temuan</strong>.</span>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="index.php" class="btn-secondary h-11 px-5 rounded-btn text-xs md:text-sm font-medium">Batal</a>
                    <button type="submit" class="btn-primary h-11 px-6 rounded-btn text-xs md:text-sm font-semibold flex items-center gap-2 shadow-sm">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Terbitkan Berita Acara & Kirim Notifikasi</span>
                    </button>
                </div>

            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
