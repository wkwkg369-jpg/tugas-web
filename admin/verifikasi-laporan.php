<?php
$page_title = 'Verifikasi Laporan - SIP-Barang Admin';
$active_tab = 'verifikasi_laporan';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Handle Action (Approve / Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $itemId = intval($_GET['id']);
    $item = get_item_by_id($itemId);

    if ($item) {
        if ($action === 'approve') {
            db_execute("UPDATE items SET is_verified = 1 WHERE id = ?", [$itemId]);
            // Send notification to reporter
            if ($item['pelapor_id']) {
                db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread) VALUES (?, ?, ?, 'check-circle', 1)", [
                    $item['pelapor_id'],
                    'Laporan Anda Telah Disetujui Petugas!',
                    "Laporan barang '{$item['nama']}' (#{item['kode']}) kini telah aktif di katalog publik kampus."
                ]);
            }
            set_flash_message('success', "Laporan #{$item['kode']} berhasil disetujui dan dipublikasikan!");
        } elseif ($action === 'reject') {
            db_execute("UPDATE items SET status = 'Ditutup' WHERE id = ?", [$itemId]);
            if ($item['pelapor_id']) {
                db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread) VALUES (?, ?, ?, 'x-circle', 1)", [
                    $item['pelapor_id'],
                    'Laporan Barang Ditolak / Ditutup',
                    "Laporan '{$item['nama']}' tidak memenuhi kriteria verifikasi atau telah ditutup oleh petugas."
                ]);
            }
            set_flash_message('info', "Laporan #{$item['kode']} telah ditandai ditutup.");
        }
    }
    header('Location: verifikasi-laporan.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$filterJenis = trim($_GET['jenis'] ?? '');

$sql = "SELECT * FROM items WHERE 1=1";
$params = [];
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
        <div class="text-xs text-slate-500 font-mono">
            Total Laporan: <?= count($items) ?>
        </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl">
        
        <!-- Search and Filters Bar -->
        <div class="card-default p-4 bg-white rounded-card border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <form action="verifikasi-laporan.php" method="GET" class="flex-1 max-w-md flex items-center gap-2">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari kode TRX, nama barang, atau nama pelapor..." class="input-default pl-9 py-2 text-xs md:text-sm">
                </div>
                <button type="submit" class="btn-primary h-9 px-4 text-xs font-semibold rounded-btn">Cari</button>
            </form>

            <div class="flex items-center gap-2">
                <a href="verifikasi-laporan.php" class="px-3 py-1.5 rounded-lg text-xs font-medium <?= empty($filterJenis) ? 'bg-blue-100 text-blue-700 font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Semua</a>
                <a href="verifikasi-laporan.php?jenis=kehilangan" class="px-3 py-1.5 rounded-lg text-xs font-medium <?= $filterJenis === 'kehilangan' ? 'bg-red-100 text-red-700 font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Kehilangan</a>
                <a href="verifikasi-laporan.php?jenis=temuan" class="px-3 py-1.5 rounded-lg text-xs font-medium <?= $filterJenis === 'temuan' ? 'bg-emerald-100 text-emerald-700 font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">Temuan</a>
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
                            <th class="py-3.5 px-5">STATUS</th>
                            <th class="py-3.5 px-5 text-right">AKSI VERIFIKASI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    Tidak ada laporan yang sesuai kriteria pencarian.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $it): ?>
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
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($it['pelapor_tipe']) ?></span>
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
                                        <a href="verifikasi-laporan.php?action=approve&id=<?= $it['id'] ?>" class="h-8 px-2.5 rounded-btn bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold inline-flex items-center gap-1">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Setujui</span>
                                        </a>
                                        <a href="verifikasi-laporan.php?action=reject&id=<?= $it['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menutup laporan ini?')" class="h-8 px-2.5 rounded-btn bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold inline-flex items-center gap-1">
                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                            <span>Tolak</span>
                                        </a>
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
