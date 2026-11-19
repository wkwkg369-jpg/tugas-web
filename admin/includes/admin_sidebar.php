<?php
// Admin Sidebar matching Figma: ui/design halaman admin/Admin/Dashboard.png
if (!isset($active_tab)) {
    $active_tab = 'dashboard';
}
$currentUser = get_logged_in_user();
?>
<aside class="w-64 bg-[#0B132B] text-slate-300 flex flex-col shrink-0 border-r border-slate-800/60 z-30 select-none">
    <!-- Brand / Logo -->
    <div class="h-20 px-6 flex items-center gap-3">
        <a href="index.php" class="flex items-center gap-3 text-white font-bold text-base tracking-tight hover:opacity-95 transition-opacity">
            <!-- 4-point Sparkle Icon matching Figma -->
            <svg class="w-6 h-6 text-blue-500 fill-blue-500 shrink-0" viewBox="0 0 24 24">
                <path d="M12 2L14.2 9.8L22 12L14.2 14.2L12 22L9.8 14.2L2 12L9.8 9.8L12 2Z" />
            </svg>
            <span class="text-[17px] font-bold text-white tracking-tight">SIP-Barang Admin</span>
        </a>
    </div>

    <!-- Navigation Menu (8 Items matching Figma) -->
    <div class="flex-1 px-4 space-y-1.5 overflow-y-auto">
        <!-- 1. Dashboard -->
        <a href="index.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'dashboard' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="grid" class="w-5 h-5 <?= $active_tab === 'dashboard' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Dashboard</span>
        </a>

        <!-- 2. Verifikasi Laporan -->
        <a href="verifikasi-laporan.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'verifikasi_laporan' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="file-check" class="w-5 h-5 <?= $active_tab === 'verifikasi_laporan' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Verifikasi Laporan</span>
        </a>

        <!-- 3. Verifikasi Klaim -->
        <a href="verifikasi-klaim.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'verifikasi_klaim' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="shield-check" class="w-5 h-5 <?= $active_tab === 'verifikasi_klaim' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Verifikasi Klaim</span>
        </a>

        <!-- 4. Penyerahan -->
        <a href="catat-penyerahan.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'penyerahan' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="package" class="w-5 h-5 <?= $active_tab === 'penyerahan' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Penyerahan</span>
        </a>

        <!-- 5. Kelola Laporan -->
        <a href="kelola-laporan.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'kelola' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="copy" class="w-5 h-5 <?= $active_tab === 'kelola' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Kelola Laporan</span>
        </a>

        <!-- 6. Pengguna -->
        <a href="kelola-pengguna.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'pengguna' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="users" class="w-5 h-5 <?= $active_tab === 'pengguna' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Pengguna</span>
        </a>

        <!-- 7. Kategori & Lokasi -->
        <a href="kategori-lokasi.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'kategori_lokasi' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="tag" class="w-5 h-5 <?= $active_tab === 'kategori_lokasi' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Kategori & Lokasi</span>
        </a>

        <!-- 8. Rekap Laporan -->
        <a href="rekap-laporan.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm transition-all <?= $active_tab === 'rekap' ? 'bg-[#1E293B] text-white font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/50 font-medium' ?>">
            <i data-lucide="bar-chart-2" class="w-5 h-5 <?= $active_tab === 'rekap' ? 'text-white' : 'text-slate-400' ?>"></i>
            <span>Rekap Laporan</span>
        </a>
    </div>

    <!-- Admin Profile Box matching Figma bottom card -->
    <div class="p-4 mt-auto">
        <div class="bg-[#131D33] p-3 rounded-2xl flex items-center justify-between border border-slate-800/60 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-sm">
                    AK
                </div>
                <div class="truncate">
                    <p class="text-sm font-semibold text-white truncate leading-snug"><?= htmlspecialchars($currentUser['nama'] ?? 'Admin Kampus') ?></p>
                    <p class="text-xs text-slate-400 truncate leading-snug">Pos Keamanan Pusat</p>
                </div>
            </div>
            <a href="../keluar.php" title="Keluar dari Sistem" class="p-2 text-slate-400 hover:text-red-400 hover:bg-slate-800/60 rounded-lg transition-colors shrink-0">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</aside>
