<?php
// Admin Sidebar matching Figma ID 1217
$pendingReportsCount = db_fetch_one("SELECT COUNT(*) as c FROM items WHERE is_verified = 0 OR status = 'Dalam Verifikasi'")['c'] ?? 0;
$pendingClaimsCount = db_fetch_one("SELECT COUNT(*) as c FROM claims WHERE status = 'Dalam Verifikasi'")['c'] ?? 0;
?>
<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 border-r border-slate-800 z-30">
    <!-- Brand -->
    <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800">
        <a href="index.php" class="flex items-center gap-2.5 text-white font-bold text-base tracking-tight">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white">
                <i data-lucide="shield" class="w-4 h-4"></i>
            </div>
            <span>SIP-Barang Admin</span>
        </a>
    </div>

    <!-- Navigation -->
    <div class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
        <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Menu Utama
        </div>

        <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs md:text-sm font-medium transition-colors <?= $active_tab === 'dashboard' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
            <span>Dashboard</span>
        </a>

        <a href="verifikasi-laporan.php" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-xs md:text-sm font-medium transition-colors <?= $active_tab === 'verifikasi_laporan' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-3">
                <i data-lucide="file-check" class="w-4 h-4"></i>
                <span>Verifikasi Laporan</span>
            </div>
            <?php if ($pendingReportsCount > 0): ?>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-slate-900"><?= $pendingReportsCount ?></span>
            <?php endif; ?>
        </a>

        <a href="verifikasi-klaim.php" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-xs md:text-sm font-medium transition-colors <?= $active_tab === 'verifikasi_klaim' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <div class="flex items-center gap-3">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Verifikasi Klaim</span>
            </div>
            <?php if ($pendingClaimsCount > 0): ?>
                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-500 text-white"><?= $pendingClaimsCount ?></span>
            <?php endif; ?>
        </a>

        <a href="catat-penyerahan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs md:text-sm font-medium transition-colors <?= $active_tab === 'penyerahan' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <i data-lucide="handshake" class="w-4 h-4"></i>
            <span>Catat Penyerahan</span>
        </a>

        <a href="kelola-laporan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs md:text-sm font-medium transition-colors <?= $active_tab === 'kelola' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <i data-lucide="boxes" class="w-4 h-4"></i>
            <span>Kelola Laporan</span>
        </a>

        <div class="pt-4 px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Laporan & Arsip
        </div>

        <a href="rekap-laporan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs md:text-sm font-medium transition-colors <?= $active_tab === 'rekap' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-400 hover:bg-slate-800 hover:text-white' ?>">
            <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
            <span>Rekap Laporan</span>
        </a>

        <div class="pt-4 border-t border-slate-800 my-2"></div>

        <a href="../index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-medium text-slate-400 hover:bg-slate-800 hover:text-white transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Halaman Mahasiswa</span>
        </a>
    </div>

    <!-- Admin Profile Footer (Figma: AK Admin Kampus Pos Keamanan Pusat) -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/50 flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0">
                AK
            </div>
            <div class="truncate">
                <p class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($currentUser['nama'] ?? 'Admin Kampus') ?></p>
                <p class="text-[10px] text-slate-400 truncate">Pos Keamanan Pusat</p>
            </div>
        </div>
        <a href="../keluar.php" title="Keluar" class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded transition-colors">
            <i data-lucide="log-out" class="w-4 h-4"></i>
        </a>
    </div>
</aside>
