<?php
$page_title = 'Dashboard Admin - SIP-Barang Kampus';
$active_tab = 'dashboard';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Real live counts from database
$countHilang = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Hilang'")['c'] ?? 0;
$countTemuan = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Ditemukan'")['c'] ?? 0;
$countVerif = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Dalam Verifikasi'")['c'] ?? 0;
$countSelesai = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE status = 'Dikembalikan'")['c'] ?? 0;

// Monthly data for Bar Chart (2026)
$monthNames = [
    6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov'
];
$currentMonthNum = intval(date('n'));

$monthlyCounts = [];
try {
    $driver = Database::getDriver();
    if ($driver === 'sqlite') {
        $rows = db_fetch_all("SELECT strftime('%m', created_at) as m, COUNT(*) as c FROM items GROUP BY m");
        foreach ($rows as $r) {
            $monthlyCounts[intval($r['m'])] = intval($r['c']);
        }
    } else {
        $rows = db_fetch_all("SELECT MONTH(created_at) as m, COUNT(*) as c FROM items WHERE YEAR(created_at) = YEAR(CURRENT_DATE) GROUP BY MONTH(created_at)");
        foreach ($rows as $r) {
            $monthlyCounts[intval($r['m'])] = intval($r['c']);
        }
    }
} catch (Throwable $e) {}

$maxCount = 0;
foreach ($monthNames as $num => $name) {
    $c = $monthlyCounts[$num] ?? 0;
    if ($c > $maxCount) $maxCount = $c;
}

$chartMonths = [];
foreach ($monthNames as $num => $name) {
    $c = $monthlyCounts[$num] ?? 0;
    $height = $maxCount > 0 ? max(10, round(($c / $maxCount) * 90)) : 6;
    $chartMonths[] = [
        'month' => $name,
        'height' => $c > 0 ? $height : 6,
        'count' => $c,
        'is_active' => ($num === $currentMonthNum || $num === 11)
    ];
}

// Fetch Real Urgent List from database
$urgentList = [];

// 1. Pending Claims
try {
    $pendingClaims = db_fetch_all("
        SELECT c.*, i.nama as item_nama 
        FROM claims c 
        JOIN items i ON c.item_id = i.id 
        WHERE c.status = 'Dalam Verifikasi' 
        ORDER BY c.id DESC 
        LIMIT 5
    ");
    foreach ($pendingClaims as $cl) {
        $urgentList[] = [
            'title' => 'Klaim ' . $cl['item_nama'],
            'subtitle' => 'Pengklaim: ' . ($cl['nama_pengklaim'] ?? 'Mahasiswa'),
            'badge' => 'Klaim Baru',
            'badge_class' => 'bg-amber-50 text-amber-600',
            'btn_text' => 'Cek Klaim',
            'btn_url' => 'verifikasi-klaim.php?id=' . $cl['id'],
            'type' => 'klaim'
        ];
    }
} catch (Throwable $e) {}

// 2. Pending / Unverified Items
if (count($urgentList) < 5) {
    try {
        $pendingItems = db_fetch_all("
            SELECT * FROM items 
            WHERE is_verified = 0 OR status = 'Dalam Verifikasi' OR status IN ('Hilang', 'Ditemukan')
            ORDER BY id DESC 
            LIMIT " . (5 - count($urgentList))
        );
        foreach ($pendingItems as $it) {
            $isHilang = ($it['status'] === 'Hilang' || $it['jenis_laporan'] === 'kehilangan');
            $urgentList[] = [
                'title' => $it['nama'],
                'subtitle' => ($isHilang ? 'Pelapor: ' : 'Penemu: ') . ($it['pelapor_nama'] ?? 'Civitas Kampus'),
                'badge' => $it['status'],
                'badge_class' => $isHilang ? 'bg-red-50 text-red-500' : 'bg-emerald-50 text-emerald-600',
                'btn_text' => 'Verifikasi',
                'btn_url' => 'verifikasi-laporan.php?id=' . $it['id'],
                'type' => 'laporan'
            ];
        }
    } catch (Throwable $e) {}
}
?>

<div class="flex-1 flex flex-col bg-[#F8FAFC] overflow-y-auto min-w-0">
    <!-- Top Header Bar matching Figma -->
    <header class="px-8 pt-8 pb-6 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Utama</h1>
        </div>
        <div>
            <span class="text-sm font-normal text-slate-500">Admin: Posko Terpadu Keamanan Kampus</span>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="px-8 pb-8 space-y-6">
        
        <!-- 4 Metric Cards Row matching Figma -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Card 1: Laporan Hilang -->
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] flex flex-col justify-between h-[138px]">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500">Laporan Hilang</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-red-50 text-red-500">Hilang</span>
                </div>
                <div class="flex items-end justify-between">
                    <span class="text-4xl font-extrabold text-red-500 tracking-tight leading-none"><?= $countHilang ?></span>
                    <div class="w-8 h-8 rounded-full border-2 border-red-500 flex items-center justify-center text-red-500 font-bold text-base leading-none shrink-0 mb-0.5">
                        !
                    </div>
                </div>
            </div>

            <!-- Card 2: Barang Ditemukan -->
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] flex flex-col justify-between h-[138px]">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500">Barang Ditemukan</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-500">Ditemukan</span>
                </div>
                <div class="flex items-end justify-between">
                    <span class="text-4xl font-extrabold text-emerald-500 tracking-tight leading-none"><?= $countTemuan ?></span>
                    <div class="shrink-0 text-emerald-500">
                        <svg class="w-8 h-8 stroke-[1.75]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m7.5 4.27 9 5.15"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3.3 7 8.7 5 8.7-5"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 3: Dalam Verifikasi -->
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] flex flex-col justify-between h-[138px]">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500">Dalam Verifikasi</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-500">Verifikasi</span>
                </div>
                <div class="flex items-end justify-between">
                    <span class="text-4xl font-extrabold text-amber-500 tracking-tight leading-none"><?= $countVerif ?></span>
                    <div class="shrink-0 text-amber-500">
                        <svg class="w-8 h-8 stroke-[1.75]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Card 4: Selesai Dikembalikan -->
            <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] flex flex-col justify-between h-[138px]">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500">Selesai Dikembalikan</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-500">Dikembalikan</span>
                </div>
                <div class="flex items-end justify-between">
                    <span class="text-4xl font-extrabold text-indigo-600 tracking-tight leading-none"><?= $countSelesai ?></span>
                    <div class="shrink-0 text-indigo-600">
                        <svg class="w-8 h-8 stroke-[1.75]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline stroke-linecap="round" stroke-linejoin="round" points="9 12 11.5 14.5 15 10"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        <!-- 2 Columns Grid: Bar Chart (Left) & Urgent Queue (Right) matching Figma -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
            
            <!-- Left Column: Grafik Laporan per Bulan (2026) -->
            <div class="bg-white rounded-2xl p-7 border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Grafik Laporan per Bulan (2026)</h2>
                    <p class="text-xs text-slate-400 mt-1">Jumlah laporan barang hilang dan temuan per bulan</p>
                </div>

                <!-- Clean Vertical Bar Chart matching Figma -->
                <div class="h-72 flex items-end justify-between px-4 sm:px-6 pt-10 pb-2">
                    <?php foreach ($chartMonths as $m): ?>
                        <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                            <!-- Tooltip on hover -->
                            <div class="absolute -top-7 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 text-white text-[10px] font-semibold py-1 px-2 rounded pointer-events-none shadow-md whitespace-nowrap z-10">
                                <?= $m['count'] ?> Laporan
                            </div>
                            <!-- Blue Bar matching Figma -->
                            <div style="height: <?= $m['height'] ?>%;" 
                                 class="w-10 sm:w-12 bg-blue-600 rounded-t-lg group-hover:bg-blue-700 transition-all duration-300">
                            </div>
                            <!-- Month Label -->
                            <span class="text-xs mt-3 <?= $m['is_active'] ? 'font-bold text-slate-900' : 'font-medium text-slate-500' ?>">
                                <?= $m['month'] ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Column: Perlu Diproses Segera -->
            <div class="bg-white rounded-2xl p-7 border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Perlu Diproses Segera (<?= count($urgentList) ?> Item)</h2>
                    <p class="text-xs text-slate-400 mt-1 mb-5">Laporan baru dan klaim yang menunggu tindakan admin</p>

                    <!-- List of items matching Figma or Empty State -->
                    <?php if (empty($urgentList)): ?>
                        <div class="py-14 flex flex-col items-center justify-center text-center">
                            <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-emerald-500 mb-3 border border-slate-100 shadow-xs">
                                <i data-lucide="check-circle" class="w-6 h-6"></i>
                            </div>
                            <h4 class="text-sm font-semibold text-slate-800">Semua Laporan Selesai</h4>
                            <p class="text-xs text-slate-400 mt-1 max-w-xs">Tidak ada antrean laporan baru atau klaim yang menunggu tindakan admin saat ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($urgentList as $item): ?>
                                <div class="border border-slate-100/90 rounded-xl p-3.5 flex items-center justify-between hover:border-slate-200 transition-colors bg-white">
                                    <div class="min-w-0 pr-3">
                                        <h3 class="text-sm font-semibold text-slate-900 truncate"><?= htmlspecialchars($item['title']) ?></h3>
                                        <p class="text-xs text-slate-400 truncate mt-0.5"><?= htmlspecialchars($item['subtitle']) ?></p>
                                    </div>
                                    <div class="shrink-0 flex items-center gap-3">
                                        <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $item['badge_class'] ?>">
                                            <?= htmlspecialchars($item['badge']) ?>
                                        </span>
                                        <a href="<?= htmlspecialchars($item['btn_url']) ?>" 
                                           class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg inline-flex items-center gap-1 shadow-sm transition-colors">
                                            <span><?= htmlspecialchars($item['btn_text']) ?></span>
                                            <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
