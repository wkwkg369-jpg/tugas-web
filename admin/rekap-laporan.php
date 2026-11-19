<?php
$page_title = 'Rekap Laporan - SIP-Barang Admin';
$active_tab = 'rekap';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Aggregated Stats
$totalReports = db_fetch_one("SELECT COUNT(*) as c FROM items")['c'] ?? 0;
$totalHilang = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE jenis_laporan = 'kehilangan'")['c'] ?? 0;
$totalTemuan = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE jenis_laporan = 'temuan'")['c'] ?? 0;
$totalKembali = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Dikembalikan'")['c'] ?? 0;
$returnRate = $totalTemuan > 0 ? round(($totalKembali / $totalTemuan) * 100) : 0;

$byCategory = db_fetch_all("SELECT kategori, COUNT(*) as jumlah FROM items GROUP BY kategori ORDER BY jumlah DESC");
$recentLogs = db_fetch_all("SELECT * FROM items ORDER BY id DESC LIMIT 10");
?>

<div class="flex-1 flex flex-col overflow-y-auto">
    <!-- Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-6 sm:px-8 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Rekap & Laporan Statistik Keamanan</h1>
            <p class="text-xs text-slate-500">Laporan resmi inventarisasi barang hilang & temuan lingkungan kampus</p>
        </div>
        <button type="button" onclick="window.print()" class="btn-primary h-9 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1.5 shadow-sm">
            <i data-lucide="printer" class="w-4 h-4"></i>
            <span>Cetak / Ekspor PDF</span>
        </button>
    </header>

    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl">
        
        <!-- Summary Cards matching Figma ID 1861 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 bg-white rounded-card border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-500 font-medium">Total Seluruh Laporan</span>
                <h3 class="text-2xl font-bold text-slate-900 mt-1"><?= $totalReports ?></h3>
                <span class="text-[11px] text-slate-400 mt-1 block">Tercatat di sistem</span>
            </div>
            <div class="p-5 bg-white rounded-card border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-500 font-medium">Laporan Kehilangan</span>
                <h3 class="text-2xl font-bold text-red-600 mt-1"><?= $totalHilang ?></h3>
                <span class="text-[11px] text-slate-400 mt-1 block">Dilaporkan mahasiswa</span>
            </div>
            <div class="p-5 bg-white rounded-card border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-500 font-medium">Barang Berhasil Ditemukan</span>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1"><?= $totalTemuan ?></h3>
                <span class="text-[11px] text-slate-400 mt-1 block">Diamankan di posko</span>
            </div>
            <div class="p-5 bg-white rounded-card border border-slate-200 shadow-sm">
                <span class="text-xs text-slate-500 font-medium">Tingkat Pengembalian Sah</span>
                <h3 class="text-2xl font-bold text-primary mt-1"><?= $returnRate ?>%</h3>
                <span class="text-[11px] text-emerald-600 font-semibold mt-1 block"><?= $totalKembali ?> barang kembali ke pemilik</span>
            </div>
        </div>

        <!-- Breakdown by Category -->
        <div class="card-default p-6 bg-white rounded-card border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-4">Distribusi Kategori Barang</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($byCategory as $bc): ?>
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($bc['kategori']) ?></p>
                            <span class="text-[11px] text-slate-400"><?= $bc['jumlah'] ?> item tercatat</span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 font-bold text-xs">
                            <?= round(($bc['jumlah'] / max(1, $totalReports)) * 100) ?>%
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 10 Recent Audit Items -->
        <div class="card-default bg-white rounded-card border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">10 Laporan Terakhir Masuk</h3>
                <span class="text-xs text-slate-400">Arsip Otomatis</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-500 uppercase">
                        <tr>
                            <th class="py-3 px-5">KODE</th>
                            <th class="py-3 px-5">NAMA BARANG</th>
                            <th class="py-3 px-5">KATEGORI</th>
                            <th class="py-3 px-5">STATUS</th>
                            <th class="py-3 px-5">LOKASI</th>
                            <th class="py-3 px-5">WAKTU</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <?php foreach ($recentLogs as $rl): ?>
                            <tr>
                                <td class="py-3 px-5 font-mono font-bold text-slate-600">#<?= htmlspecialchars($rl['kode']) ?></td>
                                <td class="py-3 px-5 font-bold text-slate-900"><?= htmlspecialchars($rl['nama']) ?></td>
                                <td class="py-3 px-5 text-slate-500"><?= htmlspecialchars($rl['kategori']) ?></td>
                                <td class="py-3 px-5"><?= render_status_badge($rl['status'], 'sm') ?></td>
                                <td class="py-3 px-5 text-slate-500"><?= htmlspecialchars($rl['lokasi_singkat']) ?></td>
                                <td class="py-3 px-5 text-slate-400"><?= htmlspecialchars($rl['waktu_kejadian']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
