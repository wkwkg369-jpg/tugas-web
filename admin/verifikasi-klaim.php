<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php';

// Handle Claim Decisions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claimId = intval($_POST['claim_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $notes = trim($_POST['catatan_petugas'] ?? '');

    $claim = db_fetch_one("SELECT c.*, i.nama as item_nama, i.id as item_id FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?", [$claimId]);
    if ($claim) {
        if ($decision === 'approve') {
            db_execute("UPDATE claims SET status = 'Disetujui', catatan_petugas = ?, verified_at = CURRENT_TIMESTAMP WHERE id = ?", [$notes, $claimId]);
            db_execute("UPDATE items SET status = 'Dikembalikan' WHERE id = ?", [$claim['item_id']]);
            // Send user notification
            if ($claim['user_id']) {
                db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread) VALUES (?, ?, ?, 'check-circle', 1)", [
                    $claim['user_id'],
                    'Klaim Kepemilikan Barang Disetujui!',
                    "Selamat! Klaim Anda untuk '{$claim['item_nama']}' telah disetujui petugas. Silakan ambil barang di pos keamanan dengan menunjukkan KTM dan KTP."
                ]);
            }
            set_flash_message('success', "Klaim #{$claim['kode_klaim']} berhasil disetujui! Status barang diperbarui menjadi 'Dikembalikan'.");
        } elseif ($decision === 'reject') {
            db_execute("UPDATE claims SET status = 'Ditolak', catatan_petugas = ?, verified_at = CURRENT_TIMESTAMP WHERE id = ?", [$notes, $claimId]);
            db_execute("UPDATE items SET status = 'Ditemukan' WHERE id = ?", [$claim['item_id']]);
            if ($claim['user_id']) {
                db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread) VALUES (?, ?, ?, 'x-circle', 1)", [
                    $claim['user_id'],
                    'Klaim Kepemilikan Ditolak',
                    "Klaim Anda untuk '{$claim['item_nama']}' belum dapat disetujui karena bukti verifikasi tidak cocok. Catatan petugas: {$notes}"
                ]);
            }
            set_flash_message('info', "Klaim #{$claim['kode_klaim']} telah ditolak.");
        }
    }
    header('Location: verifikasi-klaim.php');
    exit;
}

$page_title = 'Verifikasi Klaim - SIP-Barang Admin';
$active_tab = 'verifikasi_klaim';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Fetch Pending Claim (or selected claim)
$claimId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($claimId > 0) {
    $claim = db_fetch_one("SELECT c.*, i.nama as item_nama, i.kode as item_kode, i.lokasi as item_lokasi, i.waktu_kejadian as item_waktu, i.ciri_khusus as item_ciri_khusus, i.ciri_umum as item_ciri_umum, i.tempat_simpan as item_tempat_simpan, i.pelapor_nama as item_penemu FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?", [$claimId]);
} else {
    $claim = db_fetch_one("SELECT c.*, i.nama as item_nama, i.kode as item_kode, i.lokasi as item_lokasi, i.waktu_kejadian as item_waktu, i.ciri_khusus as item_ciri_khusus, i.ciri_umum as item_ciri_umum, i.tempat_simpan as item_tempat_simpan, i.pelapor_nama as item_penemu FROM claims c JOIN items i ON c.item_id = i.id WHERE c.status = 'Dalam Verifikasi' ORDER BY c.id DESC LIMIT 1");
    if (!$claim) {
        $claim = db_fetch_one("SELECT c.*, i.nama as item_nama, i.kode as item_kode, i.lokasi as item_lokasi, i.waktu_kejadian as item_waktu, i.ciri_khusus as item_ciri_khusus, i.ciri_umum as item_ciri_umum, i.tempat_simpan as item_tempat_simpan, i.pelapor_nama as item_penemu FROM claims c JOIN items i ON c.item_id = i.id ORDER BY c.id DESC LIMIT 1");
    }
}

// All claims for tab listing
$allClaims = db_fetch_all("SELECT c.*, i.nama as item_nama FROM claims c JOIN items i ON c.item_id = i.id ORDER BY c.id DESC");
?>

<div class="flex-1 flex flex-col overflow-y-auto">
    <!-- Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-6 sm:px-8 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Verifikasi Klaim Kepemilikan</h1>
            <p class="text-xs text-slate-500">Validasi klaim mahasiswa dengan membandingkan data rahasia penemu vs jawaban pengklaim</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500">Pilih Klaim:</span>
            <select onchange="location.href='verifikasi-klaim.php?id=' + this.value" class="input-default text-xs py-1.5 px-3 bg-white">
                <?php foreach ($allClaims as $ac): ?>
                    <option value="<?= $ac['id'] ?>" <?= ($claim && $claim['id'] == $ac['id']) ? 'selected' : '' ?>>
                        #<?= htmlspecialchars($ac['kode_klaim']) ?> - <?= htmlspecialchars($ac['nama_pengklaim']) ?> (<?= htmlspecialchars($ac['status']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl">
        
        <?php if (!$claim): ?>
            <div class="card-default p-12 bg-white rounded-card border border-slate-200 text-center text-slate-500">
                <i data-lucide="shield-check" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                <h3 class="font-bold text-slate-800">Tidak ada pengajuan klaim saat ini</h3>
                <p class="text-xs text-slate-400 mt-1">Saat mahasiswa mengklaim barang temuan, permohonan akan muncul di sini untuk verifikasi.</p>
            </div>
        <?php else: ?>
            <!-- Banner Barang & Status Klaim (Figma: Barang Banner) -->
            <div class="card-default p-5 bg-white rounded-card border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-primary flex items-center justify-center shrink-0 text-xl font-bold">
                        <i data-lucide="package" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs text-slate-500 font-bold">#<?= htmlspecialchars($claim['kode_klaim']) ?></span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="font-mono text-xs text-slate-400">Kode Barang: #<?= htmlspecialchars($claim['item_kode']) ?></span>
                        </div>
                        <h2 class="text-lg sm:text-xl font-bold text-slate-900 mt-0.5">
                            <?= htmlspecialchars($claim['item_nama']) ?>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Lokasi: <?= htmlspecialchars($claim['item_lokasi']) ?> &bull; <?= htmlspecialchars($claim['item_waktu']) ?>
                        </p>
                    </div>
                </div>

                <div>
                    <?= render_status_badge($claim['status']) ?>
                </div>
            </div>

            <!-- 2-Columns Side by Side Comparison matching Figma ID 1436 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Left Column: Data Barang Temuan (Laporan Rahasia) -->
                <div class="card-default p-6 bg-white rounded-card border border-slate-200 shadow-sm space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-amber-700">
                        <i data-lucide="lock" class="w-4 h-4"></i>
                        <h3 class="font-bold text-sm uppercase tracking-wide">Data Barang Temuan (Laporan Rahasia)</h3>
                    </div>
                    <p class="text-xs text-slate-500 -mt-2">
                        Informasi rahasia yang hanya dapat dilihat oleh admin untuk validasi pengklaim.
                    </p>

                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            CIRI FISIK KHUSUS DARI PENEMU / PETUGAS
                        </span>
                        <div class="p-3.5 rounded-xl bg-amber-50/60 border border-amber-200/80 text-xs text-slate-700 leading-relaxed font-medium">
                            <?= htmlspecialchars($claim['item_ciri_khusus'] ?: 'Terdapat stiker unik pada bagian bawah barang dan sedikit goresan halus di dekat sudut tutup.') ?>
                        </div>
                    </div>

                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            CIRI UMUM BARANG (TERCATAT DI SISTEM)
                        </span>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed">
                            <?= htmlspecialchars($claim['item_ciri_umum'] ?: 'Kondisi barang baik dan terawat.') ?>
                        </div>
                    </div>

                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            DATA PENEMU & LOKASI PENYIMPANAN
                        </span>
                        <div class="space-y-1.5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700">
                            <p><strong>Penemu:</strong> <?= htmlspecialchars($claim['item_penemu']) ?></p>
                            <p><strong>Tempat Fisik Barang:</strong> <?= htmlspecialchars($claim['item_tempat_simpan'] ?: 'Pos Keamanan Rektorat Gedung A') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Jawaban & Bukti Pengklaim -->
                <div class="card-default p-6 bg-white rounded-card border border-slate-200 shadow-sm space-y-5">
                    <div class="flex items-center gap-2 pb-3 border-b border-slate-100 text-blue-700">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                        <h3 class="font-bold text-sm uppercase tracking-wide">Jawaban & Bukti Pengklaim</h3>
                    </div>
                    
                    <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-200 text-xs text-blue-900 flex items-center justify-between">
                        <div>
                            <span class="font-bold block"><?= htmlspecialchars($claim['nama_pengklaim']) ?></span>
                            <span class="text-[11px] text-blue-700"><?= htmlspecialchars($claim['email_pengklaim']) ?> &bull; <?= htmlspecialchars($claim['phone_pengklaim']) ?></span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-blue-200 font-bold text-[10px] text-blue-800">Pemohon Klaim</span>
                    </div>

                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            DESKRIPSI CIRI KHUSUS MENURUT PENGKLAIM
                        </span>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 leading-relaxed font-medium">
                            <?= htmlspecialchars($claim['jawaban_verifikasi']) ?>
                        </div>
                    </div>

                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            FOTO BUKTI KEPEMILIKAN YANG DIUNGGAH
                        </span>
                        <?php if (!empty($claim['bukti_foto']) && file_exists(__DIR__ . '/../assets/uploads/' . $claim['bukti_foto'])): ?>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                                <a href="../assets/uploads/<?= htmlspecialchars($claim['bukti_foto']) ?>" target="_blank" class="block w-16 h-16 rounded-lg overflow-hidden border">
                                    <img src="../assets/uploads/<?= htmlspecialchars($claim['bukti_foto']) ?>" class="w-full h-full object-cover">
                                </a>
                                <div class="text-xs">
                                    <span class="font-semibold text-slate-800 block">Lampiran Bukti Pengklaim</span>
                                    <a href="../assets/uploads/<?= htmlspecialchars($claim['bukti_foto']) ?>" target="_blank" class="text-primary hover:underline text-[11px] font-medium">Buka File Lampiran &rarr;</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                <span>Verifikasi deskripsi ciri rahasia cocok &bull; <strong>Tingkat Validitas Tinggi</strong></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Action Box: Keputusan Petugas matching Figma -->
            <div class="card-default p-6 bg-white rounded-card border border-slate-200 shadow-sm">
                <form action="verifikasi-klaim.php" method="POST" class="space-y-4">
                    <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Catatan Keputusan Petugas Keamanan:
                        </label>
                        <textarea 
                            name="catatan_petugas" 
                            rows="2" 
                            placeholder="Tuliskan catatan verifikasi (contoh: Ciri stiker dan goresan cocok 100% dengan fisik barang yang diamankan di posko)..."
                            class="input-default text-xs md:text-sm"
                        ><?= htmlspecialchars($claim['catatan_petugas'] ?: 'Ciri khusus rahasia cocok dengan fisik barang yang diamankan petugas di pos.') ?></textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                        <div class="text-xs text-slate-400">
                            ID Klaim: #<?= htmlspecialchars($claim['kode_klaim']) ?>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" name="decision" value="reject" onclick="return confirm('Apakah Anda yakin ingin menolak klaim ini?')" class="h-10 px-4 rounded-btn bg-red-100 hover:bg-red-200 text-red-700 text-xs font-bold flex items-center gap-1.5 transition-colors">
                                <i data-lucide="x" class="w-4 h-4"></i>
                                <span>Tolak Klaim</span>
                            </button>
                            <button type="submit" name="decision" value="approve" class="h-10 px-6 rounded-btn bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center gap-1.5 shadow-sm transition-colors">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                <span>Setujui Klaim Kepemilikan</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
