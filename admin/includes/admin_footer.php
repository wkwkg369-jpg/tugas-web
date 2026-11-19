</div> <!-- end flex h-screen -->

<?php if (isset($flash) && $flash): ?>
    <div id="flashToast" class="fixed top-6 right-6 z-50 max-w-sm w-full bg-white rounded-xl shadow-modal border p-4 flex items-start gap-3 transition-all duration-300 animate-in fade-in slide-in-from-top-4 <?= $flash['type'] === 'success' ? 'border-emerald-300' : ($flash['type'] === 'error' ? 'border-red-300' : 'border-blue-300') ?>">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
</body>
</html>
