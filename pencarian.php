<?php
$page_title = 'Pencarian Barang - SIP-Barang Kampus';
$current_page = 'cari';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$categoryFilter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$locationFilter = isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '';

$filters = [];
if (!empty($searchQuery)) $filters['q'] = $searchQuery;
if (!empty($statusFilter) && $statusFilter !== 'all') $filters['status'] = $statusFilter;
if (!empty($categoryFilter) && $categoryFilter !== 'all') $filters['kategori'] = $categoryFilter;
if (!empty($locationFilter) && $locationFilter !== 'Semua Lokasi Kampus') $filters['lokasi'] = $locationFilter;

$allItems = get_items($filters);
$locations = get_locations();
$categories = get_categories();
?>

<main class="flex-1 py-8">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
        
        <!-- Search Bar Top (Figma: Search Bar 1200x56) -->
        <form action="pencarian.php" method="GET" class="card-default p-2 flex items-center gap-3 rounded-btn bg-white border border-slate-200 shadow-sm focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all mb-8">
            <div class="pl-3 text-slate-400">
                <i data-lucide="search" class="w-5 h-5"></i>
            </div>
            <input 
                type="text" 
                name="q"
                id="searchInput" 
                value="<?= htmlspecialchars($searchQuery) ?>" 
                placeholder="Cari nama barang hilang, ciri khusus, atau tempat kejadian..." 
                class="w-full bg-transparent border-none text-slate-900 text-sm md:text-base placeholder-slate-400 focus:outline-none focus:ring-0 py-2"
            >
            <button type="submit" class="btn-primary h-11 px-6 rounded-btn text-sm font-medium whitespace-nowrap">
                <i data-lucide="search" class="w-4 h-4"></i>
                <span>Cari</span>
            </button>
        </form>

        <!-- Main Content Area: Sidebar Filter & Grid Results -->
        <div class="flex flex-col lg:flex-row gap-8 items-start">
            
            <!-- Left: Panel Filter (Figma: Panel Filter 260px) -->
            <aside class="w-full lg:w-[280px] shrink-0 card-default p-5 bg-white rounded-card border border-slate-200 sticky top-24">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2 text-slate-900 font-bold text-base">
                        <i data-lucide="sliders-horizontal" class="w-4 h-4 text-primary"></i>
                        <span>Filter Pencarian</span>
                    </div>
                </div>

                <div class="space-y-6 mt-4">
                    <!-- Status Laporan -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Status Laporan</h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                                <input type="checkbox" value="all" class="filter-status rounded text-primary focus:ring-blue-500 w-4 h-4" checked>
                                <span>Semua Status</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                                <input type="checkbox" value="Hilang" class="filter-status rounded text-primary focus:ring-blue-500 w-4 h-4">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    <span>Hilang</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                                <input type="checkbox" value="Ditemukan" class="filter-status rounded text-primary focus:ring-blue-500 w-4 h-4">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>Ditemukan</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                                <input type="checkbox" value="Dalam Verifikasi" class="filter-status rounded text-primary focus:ring-blue-500 w-4 h-4">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Dalam Verifikasi</span>
                                </span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                                <input type="checkbox" value="Dikembalikan" class="filter-status rounded text-primary focus:ring-blue-500 w-4 h-4">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    <span>Dikembalikan</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Kategori Barang -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">Kategori Barang</h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                                <input type="checkbox" value="all" class="filter-category rounded text-primary focus:ring-blue-500 w-4 h-4" checked>
                                <span>Semua Kategori</span>
                            </label>
                            <?php foreach ($categories as $cat): ?>
                                <?php if ($cat['id'] !== 'all'): ?>
                                    <label class="flex items-center gap-2 text-sm text-slate-600 hover:text-slate-900 cursor-pointer">
                                        <input type="checkbox" value="<?= htmlspecialchars($cat['name']) ?>" class="filter-category rounded text-primary focus:ring-blue-500 w-4 h-4">
                                        <span><?= htmlspecialchars($cat['name']) ?></span>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>

                    <!-- Lokasi Kampus Dropdown -->
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Lokasi Kampus</h4>
                        <div class="relative">
                            <select id="filterLocation" class="input-default pr-8 py-2 text-sm text-slate-700 bg-white">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($loc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Reset Filter Button -->
                    <button id="btnResetFilter" type="button" class="w-full btn-secondary py-2 text-sm font-medium rounded-btn flex items-center justify-center gap-2">
                        <i data-lucide="rotate-ccw" class="w-4 h-4 text-slate-500"></i>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </aside>

            <!-- Right: Results Grid -->
            <div class="flex-1 w-full">
                <!-- Info Header -->
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm text-slate-500">
                        Menampilkan <span id="totalSearchCount" class="font-bold text-slate-900"><?= count($allItems) ?></span> barang terdaftar
                    </p>
                    <div class="text-xs text-slate-400">
                        Urutkan: <span class="font-medium text-slate-700">Terbaru</span>
                    </div>
                </div>

                <!-- Empty State if no items -->
                <div id="noResultsAlert" class="<?= empty($allItems) ? '' : 'hidden' ?> card-default p-12 bg-white rounded-card border border-slate-200 text-center">
                    <i data-lucide="search-x" class="w-12 h-12 text-slate-300 mx-auto mb-3"></i>
                    <h3 class="text-base font-bold text-slate-800">Tidak ada barang yang cocok</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Coba ubah kata kunci pencarian atau sesuaikan opsi filter lokasi dan kategori Anda.</p>
                </div>

                <!-- Cards Grid (Figma: Card 1 to 9) -->
                <div id="catalogGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                    <?php foreach ($allItems as $item): ?>
                        <div 
                            class="catalog-card card-default card-hover rounded-card overflow-hidden flex flex-col group border border-slate-200"
                            data-nama="<?= htmlspecialchars($item['nama']) ?>"
                            data-kategori="<?= htmlspecialchars($item['kategori']) ?>"
                            data-status="<?= htmlspecialchars($item['status']) ?>"
                            data-lokasi="<?= htmlspecialchars($item['lokasi']) ?> <?= htmlspecialchars($item['lokasi_singkat']) ?>"
                        >
                            <!-- Photo / Icon Placeholder -->
                            <a href="detail.php?id=<?= $item['id'] ?>" class="h-36 bg-slate-100 flex items-center justify-center relative overflow-hidden group-hover:opacity-95 transition-opacity">
                                <?php if (!empty($item['foto']) && file_exists(__DIR__ . '/assets/uploads/' . $item['foto'])): ?>
                                    <img src="assets/uploads/<?= htmlspecialchars($item['foto']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded-full bg-white/80 shadow-sm flex items-center justify-center text-slate-400 group-hover:scale-105 transition-transform">
                                        <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="w-7 h-7"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-2.5 right-2.5">
                                    <?= render_status_badge($item['status'], 'sm') ?>
                                </div>
                            </a>

                            <!-- Card Info -->
                            <div class="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block"><?= htmlspecialchars($item['kategori']) ?></span>
                                    <h3 class="text-base font-bold text-slate-900 mt-1 line-clamp-1 group-hover:text-primary transition-colors">
                                        <a href="detail.php?id=<?= $item['id'] ?>">
                                            <?= htmlspecialchars($item['nama']) ?>
                                        </a>
                                    </h3>
                                </div>

                                <div class="mt-3 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-500">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                        <span class="truncate"><?= htmlspecialchars($item['lokasi_singkat']) ?></span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                                        <span><?= htmlspecialchars($item['waktu_singkat']) ?></span>
                                    </div>
                                </div>

                                <div class="mt-4 pt-1">
                                    <a href="detail.php?id=<?= $item['id'] ?>" class="w-full btn-soft-primary h-9 rounded-btn text-xs font-semibold flex items-center justify-center gap-1.5 group-hover:bg-primary group-hover:text-white transition-all">
                                        <span>Lihat Detail</span>
                                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
