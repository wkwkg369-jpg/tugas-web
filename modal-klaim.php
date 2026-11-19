<?php
$page_title = 'Modal Klaim Kepemilikan - SIP-Barang Kampus';
$current_page = 'cari';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$itemId = isset($_GET['id']) ? intval($_GET['id']) : 1;
$item = get_item_by_id($itemId);
if (!$item) {
    $allItems = get_items();
    $item = !empty($allItems) ? $allItems[0] : null;
}
?>

<!-- Preview of Modal Klaim (Figma: Mahasiswa / Modal Klaim) -->
<main class="flex-1 py-12 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-[560px] rounded-card shadow-modal border border-slate-200 overflow-hidden">
        
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5 text-slate-900">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center">
                    <i data-lucide="shield-question" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold">Ajukan Klaim Kepemilikan</h3>
                    <p class="text-xs text-slate-500">Verifikasi tanda kepemilikan barang temuan</p>
                </div>
            </div>
            <a href="detail.php?id=<?= $item['id'] ?>" class="text-slate-400 hover:text-slate-700 transition-colors p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </a>
        </div>

        <!-- Form -->
        <form action="klaim-berhasil.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

            <!-- Item Banner -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600 flex items-center gap-2">
                <i data-lucide="package" class="w-4 h-4 text-primary shrink-0"></i>
                <span class="truncate"><strong>Barang:</strong> <?= htmlspecialchars($item['nama']) ?> (#<?= htmlspecialchars($item['kode']) ?>)</span>
            </div>

            <!-- Verification Question -->
            <div>
                <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1">
                    Pertanyaan Verifikasi Kepemilikan:
                </label>
                <p class="text-xs text-primary font-medium mb-2 italic">
                    "Sebutkan stiker, tanda goresan khusus, atau isi barang saat tertinggal:"
                </p>
                <textarea 
                    name="jawaban_verifikasi" 
                    rows="3" 
                    required
                    placeholder="Di bagian bawah ada stiker bulat Fakultas Ilmu Komputer dan sedikit penyok di sudut tutup..."
                    class="input-default text-xs md:text-sm"
                >Di bagian bawah ada stiker bulat Fakultas Ilmu Komputer dan sedikit penyok di sudut tutup...</textarea>
            </div>

            <!-- Upload Bukti Pendukung -->
            <div>
                <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1">
                    Upload Bukti Pendukung (Foto Lama / Struk Pembelian)
                </label>
                <div class="upload-zone p-4 text-center rounded-btn bg-slate-50 border-2 border-dashed border-slate-200 cursor-pointer" onclick="this.querySelector('input').click()">
                    <input type="file" name="bukti_klaim" class="hidden" accept="image/*,.pdf" onchange="previewUpload(this)">
                    <div class="upload-placeholder flex flex-col items-center justify-center gap-1 text-slate-500">
                        <i data-lucide="paperclip" class="w-5 h-5 text-primary"></i>
                        <span class="text-xs font-medium">Klik atau seret foto bukti di sini (Foto lama / struk pembelian)</span>
                        <span class="text-[10px] text-slate-400">PNG, JPG, PDF maks 5MB</span>
                    </div>
                    <div class="upload-preview hidden flex-col items-center justify-center gap-1 text-emerald-600">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <span class="file-name text-xs font-semibold"></span>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="detail.php?id=<?= $item['id'] ?>" class="btn-secondary h-11 px-5 rounded-btn text-sm font-medium">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    <span>Batal</span>
                </a>
                <button type="submit" class="btn-primary h-11 px-6 rounded-btn text-sm font-semibold">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Ajukan Klaim</span>
                </button>
            </div>
        </form>

    </div>
</main>

<script>
function previewUpload(input) {
    if (input.files && input.files[0]) {
        const zone = input.closest('.upload-zone');
        const placeholder = zone.querySelector('.upload-placeholder');
        const preview = zone.querySelector('.upload-preview');
        const fileName = zone.querySelector('.file-name');
        
        placeholder.classList.add('hidden');
        preview.classList.remove('hidden');
        preview.classList.add('flex');
        fileName.textContent = input.files[0].name;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
