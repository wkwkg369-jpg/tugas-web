<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php';

// Handle Action (Approve / Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $itemId = intval($_GET['id']);
    $item = get_item_by_id($itemId);

    if ($item) {
        if ($action === 'approve') {
            $newStatus = ($item['jenis_laporan'] === 'kehilangan') ? 'Hilang' : 'Ditemukan';
            db_execute("UPDATE items SET is_verified = 1, status = ? WHERE id = ?", [$newStatus, $itemId]);
            
            // Send notification to reporter
            if ($item['pelapor_id']) {
                db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread, link) VALUES (?, ?, ?, 'check-circle', 1, 'riwayat.php?tab=laporan')", [
                    $item['pelapor_id'],
                    'Laporan Anda Telah Disetujui Petugas!',
                    "Laporan barang '{$item['nama']}' (#{$item['kode']}) kini telah disetujui petugas dan aktif di katalog publik kampus."
                ]);
            }
            set_flash_message('success', "Laporan #{$item['kode']} berhasil disetujui! Status barang diperbarui menjadi '{$newStatus}'.");
        } elseif ($action === 'reject') {
            db_execute("UPDATE items SET is_verified = 0, status = 'Ditutup' WHERE id = ?", [$itemId]);
            if ($item['pelapor_id']) {
                db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread, link) VALUES (?, ?, ?, 'x-circle', 1, 'riwayat.php?tab=laporan')", [
                    $item['pelapor_id'],
                    'Laporan Barang Ditolak / Ditutup',
                    "Laporan '{$item['nama']}' tidak memenuhi kriteria verifikasi atau telah ditutup oleh petugas keamanan."
                ]);
            }
            set_flash_message('info', "Laporan #{$item['kode']} telah ditolak dan ditandai ditutup.");
        }
    }
    header('Location: verifikasi-laporan.php');
    exit;
}

$page_title = 'Verifikasi Laporan - SIP-Barang Admin';
$active_tab = 'verifikasi_laporan';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

$search = trim($_GET['q'] ?? '');
$filterJenis = trim($_GET['jenis'] ?? '');
$filterTab = trim($_GET['tab'] ?? 'all'); // 'pending', 'all', 'verified', 'closed'

$pendingRes = db_fetch_one("SELECT COUNT(*) as cnt FROM items WHERE is_verified = 0 OR status = 'Dalam Verifikasi'");
$pendingCount = intval($pendingRes['cnt'] ?? 0);

$sql = "SELECT * FROM items WHERE 1=1";
$params = [];

if ($filterTab === 'pending') {
    $sql .= " AND (is_verified = 0 OR status = 'Dalam Verifikasi')";
} elseif ($filterTab === 'verified') {
    $sql .= " AND is_verified = 1 AND status != 'Dalam Verifikasi' AND status != 'Ditutup'";
} elseif ($filterTab === 'closed') {
    $sql .= " AND status = 'Ditutup'";
}

if (!empty($search)) {
    $sql .= " AND (nama LIKE ? OR kode LIKE ? OR pelapor_nama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterJenis)) {
    $sql .= " AND jenis_laporan = ?";
    $params[] = $filterJenis;
}

$sql .= " ORDER BY id DESC";
$items = db_fetch_all($sql, $params);
?>

<div class="flex-1 flex flex-col overflow-y-auto">
    <!-- Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-6 sm:px-8 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Verifikasi Laporan Masuk</h1>
            <p class="text-xs text-slate-500">Tinjau keaslian dan kelengkapan data laporan kehilangan & temuan</p>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($pendingCount > 0): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span><?= $pendingCount ?> Laporan Menunggu Verifikasi</span>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                    <span>Semua Laporan Terverifikasi</span>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl">
        
        <!-- Search and Filters Bar -->
        <div class="card-default p-4 bg-white rounded-card border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <form action="verifikasi-laporan.php" method="GET" class="flex-1 max-w-md flex items-center gap-2">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($filterTab) ?>">
                <?php if (!empty($filterJenis)): ?>
                    <input type="hidden" name="jenis" value="<?= htmlspecialchars($filterJenis) ?>">
                <?php endif; ?>
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari kode TRX, nama barang, atau nama pelapor..." class="input-default pl-9 py-2 text-xs md:text-sm">
                </div>
                <button type="submit" class="btn-primary h-9 px-4 text-xs font-semibold rounded-btn">Cari</button>
            </form>

            <!-- Status Tabs -->
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="verifikasi-laporan.php?tab=all<?= !empty($filterJenis) ? '&jenis=' . urlencode($filterJenis) : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $filterTab === 'all' ? 'bg-blue-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    Semua
                </a>
                <a href="verifikasi-laporan.php?tab=pending<?= !empty($filterJenis) ? '&jenis=' . urlencode($filterJenis) : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors inline-flex items-center gap-1.5 <?= $filterTab === 'pending' ? 'bg-amber-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    <span>Menunggu</span>
                    <?php if ($pendingCount > 0): ?>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $filterTab === 'pending' ? 'bg-white text-amber-700 font-bold' : 'bg-amber-100 text-amber-800' ?>"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
                <a href="verifikasi-laporan.php?tab=verified<?= !empty($filterJenis) ? '&jenis=' . urlencode($filterJenis) : '' ?>" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $filterTab === 'verified' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    Disetujui
                </a>
                <span class="text-slate-300">|</span>
                <a href="verifikasi-laporan.php?tab=<?= urlencode($filterTab) ?>&jenis=kehilangan" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $filterJenis === 'kehilangan' ? 'bg-red-100 text-red-700 font-bold border border-red-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    Kehilangan
                </a>
                <a href="verifikasi-laporan.php?tab=<?= urlencode($filterTab) ?>&jenis=temuan" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $filterJenis === 'temuan' ? 'bg-emerald-100 text-emerald-700 font-bold border border-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                    Temuan
                </a>
            </div>
        </div>

        <!-- Table Container -->
        <div class="card-default bg-white rounded-card border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="py-3.5 px-5">KODE</th>
                            <th class="py-3.5 px-5">NAMA BARANG</th>
                            <th class="py-3.5 px-5">PELAPOR</th>
                            <th class="py-3.5 px-5">LOKASI & WAKTU</th>
                            <th class="py-3.5 px-5">STATUS LAPORAN</th>
                            <th class="py-3.5 px-5 text-right">AKSI VERIFIKASI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                        <span>Tidak ada laporan yang sesuai dengan filter kriteria saat ini.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $it): 
                                $isPending = (empty($it['is_verified']) || $it['status'] === 'Dalam Verifikasi');
                                $isRejected = ($it['status'] === 'Ditutup');
                                $isApproved = (!$isPending && !$isRejected);
                            ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-5 font-mono text-xs text-slate-500 font-bold">
                                        #<?= htmlspecialchars($it['kode']) ?>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($it['nama']) ?></div>
                                        <span class="text-xs text-slate-400"><?= htmlspecialchars($it['kategori']) ?></span>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($it['pelapor_nama']) ?></div>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($it['pelapor_tipe'] ?? 'Mahasiswa') ?></span>
                                    </td>
                                    <td class="py-4 px-5 text-xs text-slate-500">
                                        <div class="truncate max-w-[200px]"><?= htmlspecialchars($it['lokasi_singkat']) ?></div>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($it['waktu_kejadian']) ?></span>
                                    </td>
                                    <td class="py-4 px-5">
                                        <?= render_status_badge($it['status'], 'sm') ?>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-1 whitespace-nowrap">
                                        <a href="../detail.php?id=<?= $it['id'] ?>" target="_blank" class="h-8 px-2.5 rounded-btn bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium inline-flex items-center gap-1">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Lihat</span>
                                        </a>

                                        <?php if ($isPending): ?>
                                            <a href="verifikasi-laporan.php?action=approve&id=<?= $it['id'] ?>" class="h-8 px-2.5 rounded-btn bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold inline-flex items-center gap-1 shadow-sm transition-colors">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Setujui</span>
                                            </a>
                                            <a href="verifikasi-laporan.php?action=reject&id=<?= $it['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menolak dan menutup laporan ini?')" class="h-8 px-2.5 rounded-btn bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                <span>Tolak</span>
                                            </a>
                                        <?php elseif ($isRejected): ?>
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-full">
                                                <i data-lucide="x-circle" class="w-3.5 h-3.5 text-rose-600"></i>
                                                <span>Ditolak / Ditutup</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                                                <i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600"></i>
                                                <span>Disetujui</span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
