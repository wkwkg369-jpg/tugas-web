<?php
$page_title = 'Beranda - SIP-Barang Kampus';
$current_page = 'beranda';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$allItems = get_mock_items();
// Take first 6 for home page
$recentItems = array_slice($allItems, 0, 6);
?>

<main class="flex-1 pb-16">
    <!-- Hero Section -->
    <section class="pt-12 pb-14 bg-gradient-to-b from-white to-slate-50 border-b border-slate-100">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6 text-center">
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-slate-900 tracking-tight leading-tight">
                Temukan Barang Hilangmu di Kampus
            </h1>
            <p class="mt-4 text-base sm:text-lg text-slate-500 max-w-2xl mx-auto leading-relaxed">
                Layanan terpadu pelaporan dan pencarian barang hilang tercepat & aman di seluruh lingkungan kampus.
            </p>

            <!-- Search Box Hero -->
            <form action="pencarian.php" method="GET" class="mt-8 max-w-[800px] mx-auto">
                <div class="card-default p-2 flex items-center gap-2 rounded-btn bg-white border border-slate-200 shadow-sm focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all">
                    <div class="pl-3 text-slate-400">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </div>
                    <input 
                        type="text" 
                        name="q" 
                        placeholder="Cari barang (contoh: KTM, Kunci Motor, Dompet, Jaket)..." 
                        class="w-full bg-transparent border-none text-slate-900 text-sm md:text-base placeholder-slate-400 focus:outline-none focus:ring-0 py-2"
                    >
                    <button type="submit" class="btn-primary h-11 px-6 rounded-btn text-sm font-medium whitespace-nowrap">
                        <i data-lucide="search" class="w-4 h-4"></i>
                        <span>Cari</span>
                    </button>
                </div>
            </form>

            <!-- Action Buttons: Lapor Hilang & Lapor Temuan -->
            <div class="mt-6 flex flex-wrap items-center justify-center gap-4">
                <a href="lapor-kehilangan.php" class="btn-primary h-11 px-6 rounded-btn text-sm font-semibold shadow-sm transition-transform hover:-translate-y-0.5">
                    <i data-lucide="file-question" class="w-4 h-4"></i>
                    <span>Lapor Kehilangan</span>
                </a>
                <a href="lapor-temuan.php" class="btn-secondary h-11 px-6 rounded-btn text-sm font-semibold shadow-sm transition-transform hover:-translate-y-0.5">
                    <i data-lucide="package-check" class="w-4 h-4 text-slate-700"></i>
                    <span>Lapor Temuan</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Section: Barang Terbaru Dilaporkan -->
    <section class="py-12 max-w-[1200px] mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Barang Terbaru Dilaporkan</h2>
                <p class="text-sm text-slate-500 mt-1">Daftar laporan barang hilang dan temuan terbaru di kampus</p>
            </div>
            <a href="pencarian.php" class="text-primary hover:text-blue-700 text-sm font-semibold inline-flex items-center gap-1 group">
                <span>Lihat Semua</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-0.5 transition-transform"></i>
            </a>
        </div>

        <!-- 3 Columns Grid (Figma: Kartu Barang 1-6) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($recentItems as $item): ?>
                <div class="card-default card-hover rounded-card overflow-hidden flex flex-col group border border-slate-200">
                    <!-- Photo Placeholder with subtle icon matching Figma -->
                    <a href="detail.php?id=<?= $item['id'] ?>" class="h-44 bg-slate-100 flex items-center justify-center relative overflow-hidden group-hover:opacity-95 transition-opacity">
                        <div class="w-16 h-16 rounded-full bg-white/80 shadow-sm flex items-center justify-center text-slate-400 group-hover:scale-105 transition-transform">
                            <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="w-8 h-8"></i>
                        </div>
                        <!-- Status Badge Top Right -->
                        <div class="absolute top-3 right-3">
                            <?= render_status_badge($item['status']) ?>
                        </div>
                    </a>

                    <!-- Card Body -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-medium text-slate-400 uppercase tracking-wider"><?= htmlspecialchars($item['kategori']) ?></span>
                            <h3 class="text-lg font-bold text-slate-900 mt-1 line-clamp-1 group-hover:text-primary transition-colors">
                                <a href="detail.php?id=<?= $item['id'] ?>">
                                    <?= htmlspecialchars($item['nama']) ?>
                                </a>
                            </h3>
                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-2 text-xs md:text-sm text-slate-500">
                            <div class="flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-4 h-4 text-slate-400 shrink-0"></i>
                                <span class="truncate"><?= htmlspecialchars($item['lokasi_singkat']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="w-4 h-4 text-slate-400 shrink-0"></i>
                                <span><?= htmlspecialchars($item['waktu_singkat']) ?></span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3">
                            <a href="detail.php?id=<?= $item['id'] ?>" class="w-full btn-secondary py-2 text-xs font-semibold rounded-btn">
                                <span>Lihat Detail</span>
                                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Section: Cara Kerja Sistem -->
    <section class="py-12 bg-white border-y border-slate-200">
        <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
            <div class="text-center mb-10">
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Cara Kerja Sistem</h2>
                <p class="text-sm text-slate-500 mt-1">4 langkah mudah mengamankan dan mengembalikan barang hilang di kampus</p>
            </div>

            <!-- 4 Step Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Langkah 1 -->
                <div class="p-6 rounded-card border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-card transition-all">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-primary flex items-center justify-center mb-4">
                        <i data-lucide="file-text" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-primary">1. Lapor</h3>
                    <p class="mt-2 text-xs md:text-sm text-slate-500 leading-relaxed">
                        Laporkan barang hilang atau temuan Anda dengan mengisi formulir lengkap.
                    </p>
                </div>

                <!-- Langkah 2 -->
                <div class="p-6 rounded-card border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-card transition-all">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-primary flex items-center justify-center mb-4">
                        <i data-lucide="search" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-primary">2. Cari</h3>
                    <p class="mt-2 text-xs md:text-sm text-slate-500 leading-relaxed">
                        Gunakan filter kategori dan lokasi kampus untuk menemukan barang Anda.
                    </p>
                </div>

                <!-- Langkah 3 -->
                <div class="p-6 rounded-card border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-card transition-all">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-primary flex items-center justify-center mb-4">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-primary">3. Klaim</h3>
                    <p class="mt-2 text-xs md:text-sm text-slate-500 leading-relaxed">
                        Ajukan bukti kepemilikan dan verifikasi ciri khusus secara online.
                    </p>
                </div>

                <!-- Langkah 4 -->
                <div class="p-6 rounded-card border border-slate-200 bg-slate-50/50 hover:bg-white hover:shadow-card transition-all">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-primary flex items-center justify-center mb-4">
                        <i data-lucide="hand-helping" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-base font-bold text-primary">4. Ambil</h3>
                    <p class="mt-2 text-xs md:text-sm text-slate-500 leading-relaxed">
                        Tunjukkan bukti verifikasi dan ambil barang di Pos Keamanan Kampus.
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
