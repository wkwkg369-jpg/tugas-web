<?php
$page_title = 'Klaim Berhasil Diajukan - SIP-Barang Kampus';
$current_page = 'riwayat';
$isLoggedIn = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-14 flex items-center justify-center px-4">
    <!-- Card Klaim Berhasil (Figma: Card Klaim Berhasil 520x490) -->
    <div class="card-default w-full max-w-[520px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-card text-center">
        
        <!-- Icon Circle Sukses (56x56, soft green) -->
        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-6">
            <i data-lucide="check-circle" class="w-8 h-8"></i>
        </div>

        <!-- Judul H2 & Deskripsi -->
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
            Klaim Berhasil Diajukan!
        </h1>
        <p class="mt-3 text-sm text-slate-500 leading-relaxed max-w-md mx-auto">
            Bukti kepemilikan Anda telah kami terima dan diteruskan ke petugas keamanan. Kami akan memverifikasi klaim ini dalam 1x24 jam kerja.
        </p>

        <!-- Box Ringkasan Klaim (Figma: Box Ringkasan Klaim 440x110) -->
        <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left space-y-2">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>ID Pengajuan: <strong class="text-slate-600 font-mono">#KLM-2026-8821</strong></span>
                <span>16 Nov 2026, 17:00 WIB</span>
            </div>
            <div class="text-base font-bold text-slate-900 truncate">
                Tumbler Hydro Flask 32oz Biru Dongker
            </div>
            <div class="pt-1 flex items-center justify-between">
                <span class="text-xs text-slate-500">Status Klaim Saat Ini:</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Dalam Verifikasi</span>
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 space-y-3">
            <a href="riwayat.php" class="w-full btn-primary h-12 rounded-btn text-sm font-semibold shadow-sm flex items-center justify-center gap-2">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Pantau Status di Riwayat Saya</span>
            </a>
            <div>
                <a href="index.php" class="text-sm text-slate-500 hover:text-slate-800 font-medium inline-block py-2 transition-colors">
                    Kembali ke Halaman Beranda
                </a>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
