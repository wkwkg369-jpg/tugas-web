<?php
// Navbar Component SIP-Barang Kampus
$isLoggedIn = isset($isLoggedIn) ? $isLoggedIn : false;
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
                Cari
            </a>
            <a href="riwayat.php" class="text-sm font-medium transition-colors <?= ($current_page === 'riwayat') ? 'text-primary font-semibold' : 'text-slate-500 hover:text-slate-900' ?>">
                Riwayat
            </a>
        </div>

        <!-- Right Side Actions: Notification & Auth -->
        <div class="hidden md:flex items-center gap-4">
            <!-- Notification Bell Icon with Badge -->
            <a href="notifikasi.php" class="relative p-2 text-slate-500 hover:text-primary transition-colors rounded-lg hover:bg-slate-50" title="Pusat Notifikasi">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                    2
                </span>
            </a>

            <?php if ($isLoggedIn): ?>
                <!-- Logged in state -->
                <a href="riwayat.php" class="btn-primary h-10 px-4 text-sm font-medium rounded-btn flex items-center gap-2">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    <span>Profil</span>
                </a>
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
                <span class="absolute top-1 right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">
                    2
                </span>
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
            Notifikasi
        </a>
        <div class="pt-2 border-t border-slate-100 flex gap-2">
            <a href="masuk.php" class="flex-1 btn-primary py-2 text-center text-sm">Masuk</a>
            <a href="daftar.php" class="flex-1 btn-secondary py-2 text-center text-sm">Daftar</a>
        </div>
    </div>
</nav>
