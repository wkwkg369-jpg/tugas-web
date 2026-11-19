<?php
$page_title = 'Riwayat Saya - SIP-Barang Kampus';
$current_page = 'riwayat';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Check query param for default tab
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'klaim' ? 'klaim' : 'laporan';

$user = get_logged_in_user();
$userId = $user ? $user['id'] : null;

$reports = get_user_reports($userId);
$claims = get_user_claims($userId);
?>

<main class="flex-1 py-10">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
        
        <!-- Header Section -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    Riwayat Pelaporan & Klaim Saya
                </h1>
                <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                    Pantau status verifikasi dan tindak lanjut barang Anda secara berkala.
                </p>
            </div>

            <!-- Quick Add Action -->
            <div class="flex items-center gap-2">
                <a href="lapor-kehilangan.php" class="btn-primary h-10 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Lapor Kehilangan</span>
                </a>
                <a href="lapor-temuan.php" class="btn-secondary h-10 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1.5">
                    <i data-lucide="package-plus" class="w-4 h-4"></i>
                    <span>Lapor Temuan</span>
                </a>
            </div>
        </div>

        <!-- Tabs: Laporan Saya & Klaim Saya (Figma: Tab Laporan 160x38, Tab Klaim 140x38) -->
        <div class="flex items-center gap-3 mb-6">
            <button 
                type="button" 
                data-target="tabLaporan"
                class="history-tab-btn h-10 px-5 rounded-btn text-sm font-semibold transition-all flex items-center gap-2 <?= ($activeTab === 'laporan') ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"
            >
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Laporan Saya (<?= count($reports) ?>)</span>
            </button>

            <button 
                type="button" 
                data-target="tabKlaim"
                class="history-tab-btn h-10 px-5 rounded-btn text-sm font-semibold transition-all flex items-center gap-2 <?= ($activeTab === 'klaim') ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"
            >
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Klaim Saya (<?= count($claims) ?>)</span>
            </button>
        </div>

        <!-- Tab 1: Laporan Saya Table Container -->
        <div id="tabLaporan" class="history-tab-content <?= ($activeTab === 'laporan') ? '' : 'hidden' ?>">
            <div class="card-default rounded-card bg-white border border-slate-200 shadow-sm overflow-hidden">
                <?php if (empty($reports)): ?>
                    <div class="p-12 text-center text-slate-500">
                        <i data-lucide="inbox" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                        <p class="font-medium text-slate-700">Belum ada riwayat laporan barang</p>
                        <p class="text-xs text-slate-400 mt-1">Laporan kehilangan atau penemuan Anda akan tampil di sini.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-5">NO</th>
                                    <th class="py-3.5 px-5">NAMA BARANG</th>
                                    <th class="py-3.5 px-5">KATEGORI</th>
                                    <th class="py-3.5 px-5">TANGGAL KEJADIAN</th>
                                    <th class="py-3.5 px-5">STATUS LAPORAN</th>
                                    <th class="py-3.5 px-5 text-right">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($reports as $idx => $r): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-4 px-5 font-mono text-xs text-slate-400">
                                            <?= str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) ?>
                                        </td>
                                        <td class="py-4 px-5 font-bold text-slate-900">
                                            <div><?= htmlspecialchars($r['nama']) ?></div>
                                            <span class="text-[11px] font-mono text-slate-400 font-normal">#<?= htmlspecialchars($r['kode']) ?></span>
                                        </td>
                                        <td class="py-4 px-5 text-slate-500 text-xs md:text-sm">
                                            <?= htmlspecialchars($r['kategori']) ?>
                                        </td>
                                        <td class="py-4 px-5 text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                            <?= htmlspecialchars($r['waktu_singkat']) ?>
                                        </td>
                                        <td class="py-4 px-5">
                                            <?= render_status_badge($r['status'], 'sm') ?>
                                        </td>
                                        <td class="py-4 px-5 text-right">
                                            <a href="detail.php?id=<?= $r['id'] ?>" class="btn-soft-primary h-8 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 2: Klaim Saya Table Container -->
        <div id="tabKlaim" class="history-tab-content <?= ($activeTab === 'klaim') ? '' : 'hidden' ?>">
            <div class="card-default rounded-card bg-white border border-slate-200 shadow-sm overflow-hidden">
                <?php if (empty($claims)): ?>
                    <div class="p-12 text-center text-slate-500">
                        <i data-lucide="shield-alert" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                        <p class="font-medium text-slate-700">Belum ada pengajuan klaim kepemilikan</p>
                        <p class="text-xs text-slate-400 mt-1">Jika Anda menemukan barang Anda di katalog temuan, klik tombol 'Ini Barang Saya' untuk mengajukan klaim.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-5">NO</th>
                                    <th class="py-3.5 px-5">BARANG YANG DIKLAIM</th>
                                    <th class="py-3.5 px-5">KATEGORI</th>
                                    <th class="py-3.5 px-5">TANGGAL PENGAJUAN</th>
                                    <th class="py-3.5 px-5">STATUS KLAIM</th>
                                    <th class="py-3.5 px-5 text-right">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($claims as $idx => $c): ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-4 px-5 font-mono text-xs text-slate-400">
                                            <?= str_pad((string)($idx + 1), 2, '0', STR_PAD_LEFT) ?>
                                        </td>
                                        <td class="py-4 px-5">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($c['item_nama']) ?></div>
                                            <span class="text-xs font-mono text-slate-400">ID Pengajuan: #<?= htmlspecialchars($c['kode_klaim']) ?></span>
                                        </td>
                                        <td class="py-4 px-5 text-slate-500 text-xs md:text-sm">
                                            <?= htmlspecialchars($c['item_kategori'] ?? 'Aksesoris') ?>
                                        </td>
                                        <td class="py-4 px-5 text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                            <?= !empty($c['created_at']) ? date('d M Y, H:i', strtotime($c['created_at'])) . ' WIB' : '16 Nov 2026, 17:00 WIB' ?>
                                        </td>
                                        <td class="py-4 px-5">
                                            <?= render_status_badge($c['status'], 'sm') ?>
                                        </td>
                                        <td class="py-4 px-5 text-right">
                                            <a href="detail.php?id=<?= $c['item_id'] ?>" class="btn-soft-primary h-8 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1">
                                                Lihat Klaim
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
