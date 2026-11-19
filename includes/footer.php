<!-- Footer Component SIP-Barang Kampus -->
<footer class="mt-auto bg-white border-t border-slate-200 py-8">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-center md:text-left">
                <a href="index.php" class="inline-flex items-center gap-2 text-primary font-bold text-base hover:opacity-90 transition-opacity">
                    <i data-lucide="sparkles" class="w-4 h-4 text-primary"></i>
                    <span>SIP-Barang Kampus</span>
                </a>
                <p class="mt-1 text-xs md:text-sm text-slate-500">
                    Sistem Informasi Pelaporan & Pencarian Barang Hilang di Lingkungan Kampus.
                </p>
            </div>
            <div class="text-xs text-slate-400">
                &copy; 2026 SIP-Barang Kampus. All rights reserved.
            </div>
        </div>
    </div>
</footer>

<!-- Global Toast Notification Component -->
<div id="toastNotification" class="hidden fixed bottom-6 right-6 z-50 max-w-sm w-full bg-white rounded-card shadow-modal border border-slate-200 border-l-4 border-l-emerald-500 p-4 transition-all">
    <div class="flex items-start gap-3">
        <div class="text-emerald-500 mt-0.5">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
        </div>
        <div class="flex-1">
            <h4 id="toastTitle" class="text-sm font-semibold text-slate-900">Klaim berhasil diajukan</h4>
            <p id="toastDesc" class="mt-0.5 text-xs text-slate-500 leading-relaxed">Menunggu konfirmasi dari admin. Kami akan memberi tahu kamu setelah diproses.</p>
        </div>
        <button id="closeToastBtn" type="button" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
</div>

<!-- Main Interactive JS (versi dari mtime: cegah cache lama di browser) -->
<script src="assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?: '1' ?>"></script>
</body>
</html>
