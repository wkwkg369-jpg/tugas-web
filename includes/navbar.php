<?php
// Navbar Component SIP-Barang Kampus
$currentUser = get_logged_in_user();
$isLoggedIn = ($currentUser !== null);
$unreadCount = get_unread_notifications_count();
$flash = get_flash_message();
?>
<nav class="h-[72px] bg-white border-b border-slate-200 sticky top-0 z-40">
    <div class="max-w-[1200px] mx-auto h-full px-4 sm:px-6 flex items-center justify-between">
        <!-- Logo / Brand -->
        <a href="index.php" class="flex items-center gap-2 text-primary font-bold text-lg tracking-tight hover:opacity-95 transition-opacity">
            <i data-lucide="sparkles" class="w-5 h-5 text-primary"></i>
            <span>SIP-Barang Kampus</span>
        </a>

        <!-- Desktop Navigation Links -->
        <div class="hidden md:flex items-center gap-8">
            <a href="index.php" class="text-sm font-medium transition-colors <?= ($current_page === 'beranda') ? 'text-primary font-semibold' : 'text-slate-500 hover:text-slate-900' ?>">
                Beranda
            </a>
            <a href="pencarian.php" class="text-sm font-medium transition-colors <?= ($current_page === 'cari') ? 'text-primary font-semibold' : 'text-slate-500 hover:text-slate-900' ?>">
                Cari Barang
            </a>
            <a href="riwayat.php" class="text-sm font-medium transition-colors <?= ($current_page === 'riwayat') ? 'text-primary font-semibold' : 'text-slate-500 hover:text-slate-900' ?>">
                Riwayat
            </a>
            <?php if (is_admin()): ?>
                <a href="admin/index.php" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition-colors inline-flex items-center gap-1">
                    <i data-lucide="shield" class="w-3.5 h-3.5"></i>
                    <span>Panel Admin</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Right Side Actions: Notification & Auth -->
        <div class="hidden md:flex items-center gap-4">
            <!-- Notification Bell Icon with Dynamic Badge -->
            <a href="notifikasi.php" class="relative p-2 text-slate-500 hover:text-primary transition-colors rounded-lg hover:bg-slate-50" title="Pusat Notifikasi">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <?php if ($isLoggedIn): ?>
                <!-- User Profile Dropdown Button -->
                <div class="relative group">
                    <button type="button" class="flex items-center gap-2.5 pl-3 pr-2 py-1.5 rounded-btn border border-slate-200 hover:border-slate-300 bg-white transition-all text-left">
                        <div class="w-7 h-7 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs">
                            <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
                        </div>
                        <div class="text-xs">
                            <span class="font-semibold text-slate-800 block line-clamp-1 max-w-[120px]"><?= htmlspecialchars(explode(' ', $currentUser['nama'])[0]) ?></span>
                            <span class="text-slate-400 font-mono text-[10px] block"><?= htmlspecialchars($currentUser['nim'] ?: $currentUser['role']) ?></span>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-transform"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-56 rounded-card bg-white border border-slate-200 shadow-modal py-2 hidden group-hover:block transition-all z-50">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($currentUser['nama']) ?></p>
                            <p class="text-[11px] text-slate-500 truncate"><?= htmlspecialchars($currentUser['email']) ?></p>
                        </div>
                        
                        <a href="riwayat.php" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors">
                            <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                            <span>Riwayat Saya</span>
                        </a>
                        <a href="lapor-kehilangan.php" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors">
                            <i data-lucide="file-question" class="w-4 h-4 text-slate-400"></i>
                            <span>Lapor Kehilangan</span>
                        </a>
                        <a href="lapor-temuan.php" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-700 hover:bg-slate-50 hover:text-primary transition-colors">
                            <i data-lucide="package-check" class="w-4 h-4 text-slate-400"></i>
                            <span>Lapor Temuan</span>
                        </a>

                        <?php if (is_admin()): ?>
                            <div class="border-t border-slate-100 my-1"></div>
                            <a href="admin/index.php" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-blue-600 hover:bg-blue-50 transition-colors">
                                <i data-lucide="layout-dashboard" class="w-4 h-4 text-blue-500"></i>
                                <span>Panel Admin Petugas</span>
                            </a>
                        <?php endif; ?>

                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="keluar.php" class="flex items-center gap-2.5 px-4 py-2 text-xs text-red-600 hover:bg-red-50 transition-colors">
                            <i data-lucide="log-out" class="w-4 h-4 text-red-500"></i>
                            <span>Keluar dari Akun</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Guest state: Masuk button -->
                <a href="masuk.php" class="btn-primary h-10 px-5 text-sm font-medium rounded-btn flex items-center gap-2 shadow-sm">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span>Masuk</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Button -->
        <div class="flex items-center gap-2 md:hidden">
            <a href="notifikasi.php" class="relative p-2 text-slate-500">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute top-1 right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">
                        <?= $unreadCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <button id="mobileMenuBtn" type="button" class="p-2 text-slate-600 hover:text-slate-900 focus:outline-none">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobileMenu" class="hidden md:hidden bg-white border-b border-slate-200 px-4 pt-2 pb-4 space-y-2 shadow-lg">
        <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($current_page === 'beranda') ? 'bg-blue-50 text-primary' : 'text-slate-700 hover:bg-slate-50' ?>">
            Beranda
        </a>
        <a href="pencarian.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($current_page === 'cari') ? 'bg-blue-50 text-primary' : 'text-slate-700 hover:bg-slate-50' ?>">
            Cari Barang
        </a>
        <a href="riwayat.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($current_page === 'riwayat') ? 'bg-blue-50 text-primary' : 'text-slate-700 hover:bg-slate-50' ?>">
            Riwayat Saya
        </a>
        <a href="notifikasi.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($current_page === 'notifikasi') ? 'bg-blue-50 text-primary' : 'text-slate-700 hover:bg-slate-50' ?>">
            Notifikasi <?= $unreadCount > 0 ? "({$unreadCount})" : '' ?>
        </a>
        <?php if (is_admin()): ?>
            <a href="admin/index.php" class="block px-3 py-2 rounded-md text-base font-semibold text-blue-600 bg-blue-50">
                Panel Admin
            </a>
        <?php endif; ?>
        <div class="pt-2 border-t border-slate-100">
            <?php if ($isLoggedIn): ?>
                <div class="px-3 py-2 text-xs text-slate-500">
                    Masuk sebagai: <strong class="text-slate-800"><?= htmlspecialchars($currentUser['nama']) ?></strong>
                </div>
                <a href="keluar.php" class="block px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 rounded-md">
                    Keluar dari Akun
                </a>
            <?php else: ?>
                <div class="flex gap-2">
                    <a href="masuk.php" class="flex-1 btn-primary py-2 text-center text-sm">Masuk</a>
                    <a href="daftar.php" class="flex-1 btn-secondary py-2 text-center text-sm">Daftar</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Flash Toast Notification Banner if exists -->
<?php if ($flash): ?>
    <div id="flashToast" class="fixed top-20 right-4 z-50 max-w-sm w-full bg-white rounded-xl shadow-modal border p-4 flex items-start gap-3 transition-all duration-300 animate-in fade-in slide-in-from-top-4 <?= $flash['type'] === 'success' ? 'border-emerald-300' : ($flash['type'] === 'error' ? 'border-red-300' : 'border-blue-300') ?>">
        <div class="p-1 rounded-full <?= $flash['type'] === 'success' ? 'bg-emerald-100 text-emerald-600' : ($flash['type'] === 'error' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-primary') ?>">
            <i data-lucide="<?= $flash['type'] === 'success' ? 'check' : ($flash['type'] === 'error' ? 'alert-triangle' : 'info') ?>" class="w-5 h-5"></i>
        </div>
        <div class="flex-1 text-xs sm:text-sm text-slate-700">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
        <button type="button" onclick="document.getElementById('flashToast').remove()" class="text-slate-400 hover:text-slate-600">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    <script>
        setTimeout(() => {
            const ft = document.getElementById('flashToast');
            if (ft) {
                ft.style.opacity = '0';
                setTimeout(() => ft.remove(), 300);
            }
        }, 4000);
    </script>
<?php endif; ?>
