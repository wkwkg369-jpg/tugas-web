<?php
$page_title = 'Dashboard Admin - SIP-Barang Kampus';
$active_tab = 'dashboard';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Fetch Live Metrics
$countHilang = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Hilang'")['c'] ?? 52;
$countTemuan = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Ditemukan'")['c'] ?? 84;
$countVerif = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Dalam Verifikasi'")['c'] ?? 19;
$countSelesai = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Dikembalikan'")['c'] ?? 65;

// Fetch Urgent Queue
$urgentItems = db_fetch_all("SELECT * FROM items WHERE status IN ('Dalam Verifikasi', 'Hilang', 'Ditemukan') ORDER BY id DESC LIMIT 5");
?>

<div class="flex-1 flex flex-col overflow-y-auto">
    <!-- Top Header -->
    <header class="h-16 bg-white border-b border-slate-200 px-6 sm:px-8 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Dashboard Utama</h1>
            <p class="text-xs text-slate-500">Admin: Posko Terpadu Keamanan Kampus</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Sistem Aktif (Online)</span>
            </span>
            <a href="../index.php" target="_blank" class="btn-secondary h-9 px-3.5 text-xs font-medium rounded-btn inline-flex items-center gap-1.5">
                <i data-lucide="external-link" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Lihat Web Mahasiswa</span>
            </a>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1 p-6 sm:p-8 space-y-6 max-w-7xl">
        
        <!-- 4 Stat Cards matching Figma -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- Stat 1: Laporan Hilang -->
            <div class="card-default p-5 bg-white rounded-card border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Laporan Hilang</p>
                    <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1"><?= $countHilang ?></h3>
                    <span class="inline-flex items-center gap-1 mt-2 text-[11px] font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                        Hilang
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <i data-lucide="file-question" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Stat 2: Barang Ditemukan -->
            <div class="card-default p-5 bg-white rounded-card border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Barang Ditemukan</p>
                    <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1"><?= $countTemuan ?></h3>
                    <span class="inline-flex items-center gap-1 mt-2 text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Ditemukan
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i data-lucide="package-check" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Stat 3: Dalam Verifikasi -->
            <div class="card-default p-5 bg-white rounded-card border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Dalam Verifikasi</p>
                    <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1"><?= $countVerif ?></h3>
                    <span class="inline-flex items-center gap-1 mt-2 text-[11px] font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Verifikasi
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Stat 4: Selesai Dikembalikan -->
            <div class="card-default p-5 bg-white rounded-card border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500">Selesai Dikembalikan</p>
                    <h3 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-1"><?= $countSelesai ?></h3>
                    <span class="inline-flex items-center gap-1 mt-2 text-[11px] font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                        Dikembalikan
                    </span>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i data-lucide="check-check" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- 2 Columns: Chart & Urgent Queue -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: Card Chart (7 cols) -->
            <div class="lg:col-span-7 card-default p-6 bg-white rounded-card border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Grafik Laporan per Bulan (2026)</h3>
                        <p class="text-xs text-slate-500">Jumlah laporan barang hilang dan temuan per bulan</p>
                    </div>
                    <span class="text-xs font-semibold text-primary bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">
                        Tahun 2026
                    </span>
                </div>

                <!-- Visual Bar Chart Representation matching Figma -->
                <div class="h-64 flex items-end justify-between gap-3 sm:gap-6 pt-6 border-b border-slate-100">
                    <?php
                    $months = [
                        ['m' => 'Jun', 'hilang' => 35, 'temu' => 45],
                        ['m' => 'Jul', 'hilang' => 28, 'temu' => 50],
                        ['m' => 'Agu', 'hilang' => 42, 'temu' => 65],
                        ['m' => 'Sep', 'hilang' => 55, 'temu' => 70],
                        ['m' => 'Okt', 'hilang' => 48, 'temu' => 80],
                        ['m' => 'Nov', 'hilang' => 52, 'temu' => 84],
                    ];
                    foreach ($months as $mo):
                    ?>
                        <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end group">
                            <div class="w-full max-w-[32px] flex items-end justify-center gap-1 h-full">
                                <div style="height: <?= $mo['hilang'] ?>%;" class="w-3 sm:w-3.5 bg-red-400 rounded-t group-hover:bg-red-500 transition-all" title="Hilang: <?= $mo['hilang'] ?>"></div>
                                <div style="height: <?= $mo['temu'] ?>%;" class="w-3 sm:w-3.5 bg-blue-500 rounded-t group-hover:bg-blue-600 transition-all" title="Temuan: <?= $mo['temu'] ?>"></div>
                            </div>
                            <span class="text-xs font-medium text-slate-500"><?= $mo['m'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="flex items-center justify-center gap-6 mt-4 text-xs text-slate-600">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-red-400"></span>
                        <span>Barang Hilang</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-blue-500"></span>
                        <span>Barang Temuan</span>
                    </span>
                </div>
            </div>

            <!-- Right: Card Queue Perlu Diproses Segera (5 cols) -->
            <div class="lg:col-span-5 card-default p-6 bg-white rounded-card border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Perlu Diproses Segera</h3>
                            <p class="text-xs text-slate-500">Laporan baru dan klaim yang menunggu tindakan admin</p>
                        </div>
                        <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                            <?= count($urgentItems) ?> Item
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <?php foreach ($urgentItems as $it): ?>
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <h4 class="text-xs sm:text-sm font-bold text-slate-900 truncate"><?= htmlspecialchars($it['nama']) ?></h4>
                                    <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                        <?= htmlspecialchars($it['pelapor_nama']) ?> &bull; <span class="font-mono text-slate-400">#<?= htmlspecialchars($it['kode']) ?></span>
                                    </p>
                                </div>
                                <div class="shrink-0 flex items-center gap-2">
                                    <?= render_status_badge($it['status'], 'sm') ?>
                                    <a href="verifikasi-laporan.php?id=<?= $it['id'] ?>" class="btn-primary h-7 px-2.5 text-[11px] font-semibold rounded-btn">
                                        Verifikasi
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 mt-4 text-center">
                    <a href="verifikasi-laporan.php" class="text-xs font-semibold text-primary hover:text-blue-700 inline-flex items-center gap-1">
                        <span>Lihat Semua Antrean Verifikasi</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
