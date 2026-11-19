<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php';

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $itemId = intval($_POST['item_id']);
    $newStatus = $_POST['new_status'];
    db_execute("UPDATE items SET status = ? WHERE id = ?", [$newStatus, $itemId]);
    set_flash_message('success', "Status laporan berhasil diperbarui menjadi '{$newStatus}'.");
    header('Location: kelola-laporan.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    db_execute("DELETE FROM items WHERE id = ?", [$delId]);
    set_flash_message('info', 'Laporan barang berhasil dihapus dari sistem.');
    header('Location: kelola-laporan.php');
    exit;
}

$page_title = 'Kelola Laporan - SIP-Barang Admin';
$active_tab = 'kelola';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$catFilter = trim($_GET['kategori'] ?? '');

$sql = "SELECT * FROM items WHERE 1=1";
$params = [];
if (!empty($search)) {
    $sql .= " AND (nama LIKE ? OR kode LIKE ? OR lokasi LIKE ? OR pelapor_nama LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($statusFilter)) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}
if (!empty($catFilter)) {
    $sql .= " AND (kategori = ? OR kategori_slug = ?)";
    $params[] = $catFilter;
    $params[] = $catFilter;
}
$sql .= " ORDER BY id DESC";
$items = db_fetch_all($sql, $params);
$categories = get_categories();
?>

<div class="flex-1 flex flex-col overflow-y-auto">
    <!-- Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-6 sm:px-8 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Kelola Seluruh Laporan</h1>
            <p class="text-xs text-slate-500">Manajemen master data inventaris barang hilang dan temuan kampus</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="../lapor-temuan.php" target="_blank" class="btn-primary h-9 px-3.5 text-xs font-semibold rounded-btn inline-flex items-center gap-1.5 shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Laporan Temuan</span>
            </a>
        </div>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl">
        
        <!-- Search and Filters Bar -->
        <div class="card-default p-4 bg-white rounded-card border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <form action="kelola-laporan.php" method="GET" class="flex-1 max-w-md flex items-center gap-2">
                <div class="relative flex-1">
                    <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama barang, kode, pelapor..." class="input-default pl-9 py-2 text-xs md:text-sm">
                </div>
                <button type="submit" class="btn-primary h-9 px-4 text-xs font-semibold rounded-btn">Cari</button>
            </form>

            <div class="flex flex-wrap items-center gap-2 text-xs">
                <select onchange="location.href='kelola-laporan.php?status=' + this.value" class="input-default text-xs py-1.5 px-3 bg-white">
                    <option value="">Semua Status</option>
                    <option value="Hilang" <?= $statusFilter === 'Hilang' ? 'selected' : '' ?>>Hilang</option>
                    <option value="Ditemukan" <?= $statusFilter === 'Ditemukan' ? 'selected' : '' ?>>Ditemukan</option>
                    <option value="Dalam Verifikasi" <?= $statusFilter === 'Dalam Verifikasi' ? 'selected' : '' ?>>Dalam Verifikasi</option>
                    <option value="Dikembalikan" <?= $statusFilter === 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                    <option value="Ditutup" <?= $statusFilter === 'Ditutup' ? 'selected' : '' ?>>Ditutup</option>
                </select>

                <select onchange="location.href='kelola-laporan.php?kategori=' + this.value" class="input-default text-xs py-1.5 px-3 bg-white">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $c): ?>
                        <?php if ($c['id'] !== 'all'): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>" <?= $catFilter === $c['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>

                <a href="kelola-laporan.php" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 font-medium">Reset</a>
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
                            <th class="py-3.5 px-5">KATEGORI</th>
                            <th class="py-3.5 px-5">STATUS SAAT INI</th>
                            <th class="py-3.5 px-5">UBAH STATUS CEPAT</th>
                            <th class="py-3.5 px-5 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    Tidak ada data barang yang sesuai filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $it): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-5 font-mono text-xs font-bold text-slate-500">
                                        #<?= htmlspecialchars($it['kode']) ?>
                                    </td>
                                    <td class="py-4 px-5">
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($it['nama']) ?></div>
                                        <span class="text-xs text-slate-400"><?= htmlspecialchars($it['lokasi_singkat']) ?></span>
                                    </td>
                                    <td class="py-4 px-5 text-xs text-slate-600">
                                        <?= htmlspecialchars($it['kategori']) ?>
                                    </td>
                                    <td class="py-4 px-5">
                                        <?= render_status_badge($it['status'], 'sm') ?>
                                    </td>
                                    <td class="py-4 px-5">
                                        <form action="kelola-laporan.php" method="POST" class="inline-flex items-center gap-1.5">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="item_id" value="<?= $it['id'] ?>">
                                            <select name="new_status" class="input-default text-xs py-1 px-2.5 bg-white border-slate-200">
                                                <option value="Hilang" <?= $it['status'] === 'Hilang' ? 'selected' : '' ?>>Hilang</option>
                                                <option value="Ditemukan" <?= $it['status'] === 'Ditemukan' ? 'selected' : '' ?>>Ditemukan</option>
                                                <option value="Dalam Verifikasi" <?= $it['status'] === 'Dalam Verifikasi' ? 'selected' : '' ?>>Dalam Verifikasi</option>
                                                <option value="Dikembalikan" <?= $it['status'] === 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                                                <option value="Ditutup" <?= $it['status'] === 'Ditutup' ? 'selected' : '' ?>>Ditutup</option>
                                            </select>
                                            <button type="submit" class="h-7 px-2 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                                                Ubah
                                            </button>
                                        </form>
                                    </td>
                                    <td class="py-4 px-5 text-right space-x-1 whitespace-nowrap">
                                        <a href="../detail.php?id=<?= $it['id'] ?>" target="_blank" class="h-8 px-2.5 rounded-btn bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium inline-flex items-center gap-1">
                                            <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                            <span>Lihat</span>
                                        </a>
                                        <a href="kelola-laporan.php?delete=<?= $it['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini secara permanen?')" class="h-8 px-2.5 rounded-btn bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium inline-flex items-center gap-1">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            <span>Hapus</span>
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
