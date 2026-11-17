<?php
$page_title = 'Riwayat Saya - SIP-Barang Kampus';
$current_page = 'riwayat';
$isLoggedIn = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Check query param for default tab
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'klaim' ? 'klaim' : 'laporan';
?>

<main class="flex-1 py-10">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
        
        <!-- Header Section -->
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Riwayat Pelaporan & Klaim Saya
            </h1>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">
                Pantau status verifikasi dan tindak lanjut barang Anda secara berkala.
            </p>
        </div>

        <!-- Tabs: Laporan Saya & Klaim Saya (Figma: Tab Laporan 160x38, Tab Klaim 140x38) -->
        <div class="flex items-center gap-3 mb-6">
            <button 
                type="button" 
                data-target="tabLaporan"
                class="history-tab-btn h-10 px-5 rounded-btn text-sm font-semibold transition-all flex items-center gap-2 <?= ($activeTab === 'laporan') ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"
            >
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Laporan Saya (3)</span>
            </button>

            <button 
                type="button" 
                data-target="tabKlaim"
                class="history-tab-btn h-10 px-5 rounded-btn text-sm font-semibold transition-all flex items-center gap-2 <?= ($activeTab === 'klaim') ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"
            >
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Klaim Saya (1)</span>
            </button>
        </div>

        <!-- Tab 1: Laporan Saya Table Container -->
        <div id="tabLaporan" class="history-tab-content <?= ($activeTab === 'laporan') ? '' : 'hidden' ?>">
            <div class="card-default rounded-card bg-white border border-slate-200 shadow-sm overflow-hidden">
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
                            <!-- Row 1 -->
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-4 px-5 font-mono text-xs text-slate-400">01</td>
                                <td class="py-4 px-5 font-bold text-slate-900">
                                    KTM & Dompet Kulit Cokelat
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm">
                                    Dompet & Kartu Identitas
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    15 Nov 2026, 14:00 WIB
                                </td>
                                <td class="py-4 px-5">
                                    <?= render_status_badge('Hilang', 'sm') ?>
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <a href="detail.php?id=2" class="btn-soft-primary h-8 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1">
                                        Detail
                                    </a>
                                </td>
                            </tr>

                            <!-- Row 2 -->
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-4 px-5 font-mono text-xs text-slate-400">02</td>
                                <td class="py-4 px-5 font-bold text-slate-900">
                                    Tumbler Hydro Flask 32oz Biru
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm">
                                    Aksesoris & Botol Minum
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    16 Nov 2026, 09:15 WIB
                                </td>
                                <td class="py-4 px-5">
                                    <?= render_status_badge('Dalam Verifikasi', 'sm') ?>
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <a href="detail.php?id=1" class="btn-soft-primary h-8 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1">
                                        Detail
                                    </a>
                                </td>
                            </tr>

                            <!-- Row 3 -->
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-4 px-5 font-mono text-xs text-slate-400">03</td>
                                <td class="py-4 px-5 font-bold text-slate-900">
                                    Kunci Motor Honda Vario 160
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm">
                                    Kunci Kendaraan
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    10 Nov 2026, 17:00 WIB
                                </td>
                                <td class="py-4 px-5">
                                    <?= render_status_badge('Dikembalikan', 'sm') ?>
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <a href="detail.php?id=3" class="btn-soft-primary h-8 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2: Klaim Saya Table Container -->
        <div id="tabKlaim" class="history-tab-content <?= ($activeTab === 'klaim') ? '' : 'hidden' ?>">
            <div class="card-default rounded-card bg-white border border-slate-200 shadow-sm overflow-hidden">
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
                            <!-- Klaim 1 -->
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-4 px-5 font-mono text-xs text-slate-400">01</td>
                                <td class="py-4 px-5">
                                    <div class="font-bold text-slate-900">Tumbler Hydro Flask 32oz Biru Dongker</div>
                                    <span class="text-xs font-mono text-slate-400">ID Pengajuan: #KLM-2026-8821</span>
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm">
                                    Aksesoris & Botol Minum
                                </td>
                                <td class="py-4 px-5 text-slate-500 text-xs md:text-sm whitespace-nowrap">
                                    16 Nov 2026, 17:00 WIB
                                </td>
                                <td class="py-4 px-5">
                                    <?= render_status_badge('Dalam Verifikasi', 'sm') ?>
                                </td>
                                <td class="py-4 px-5 text-right">
                                    <a href="detail.php?id=1" class="btn-soft-primary h-8 px-4 text-xs font-semibold rounded-btn inline-flex items-center gap-1">
                                        Lihat Klaim
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
