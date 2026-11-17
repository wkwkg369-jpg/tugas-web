<?php
$page_title = 'Detail Barang - SIP-Barang Kampus';
$current_page = 'cari';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$items = get_mock_items();

// Find item or default to item 1
$item = null;
foreach ($items as $it) {
    if ($it['id'] === $id) {
        $item = $it;
        break;
    }
}
if (!$item) {
    $item = $items[0];
}
?>

<main class="flex-1 py-10">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6">
        
        <!-- Breadcrumb & Back Link -->
        <div class="mb-6 flex items-center justify-between">
            <a href="pencarian.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-primary transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Pencarian</span>
            </a>
            <span class="text-xs text-slate-400 font-mono">ID Laporan: #<?= htmlspecialchars($item['kode']) ?></span>
        </div>

        <!-- 2 Columns Layout: Photos/Reporter on Left, Details on Right -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Photo Carousel & Notice Cards (5 cols) -->
            <div class="lg:col-span-5 space-y-4">
                
                <!-- Placeholder Foto Besar (Figma: 540x460) -->
                <div class="card-default rounded-card overflow-hidden bg-slate-100 border border-slate-200 relative">
                    <div class="h-80 sm:h-96 flex flex-col items-center justify-center relative p-6 text-center">
                        <div class="w-20 h-20 rounded-2xl bg-white/90 shadow-sm flex items-center justify-center text-slate-400 mb-3">
                            <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="w-10 h-10"></i>
                        </div>
                        <p class="text-sm font-medium text-slate-500">Foto Utama Barang <?= htmlspecialchars($item['status']) ?></p>
                        <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($item['nama']) ?></p>

                        <!-- Prev & Next Carousel Buttons -->
                        <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow-md border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-white hover:text-primary transition-all">
                            <i data-lucide="chevron-left" class="w-5 h-5"></i>
                        </button>
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow-md border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-white hover:text-primary transition-all">
                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Card Info Pelapor (Figma: 540x60) -->
                <div class="card-default p-4 rounded-card bg-white border border-slate-200 flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-blue-50 text-primary flex items-center justify-center shrink-0">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs md:text-sm font-semibold text-slate-900">
                            Dilaporkan oleh: <?= htmlspecialchars($item['pelapor']) ?>
                        </h4>
                        <p class="text-xs text-slate-500">Terverifikasi resmi oleh sistem SIP-Barang Kampus</p>
                    </div>
                </div>

                <!-- Card Peringatan Keamanan (Figma: 540x60) -->
                <div class="card-default p-4 rounded-card bg-red-50 border border-red-200 flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                        <i data-lucide="shield-alert" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h4 class="text-xs md:text-sm font-semibold text-red-900">
                            Peringatan Keamanan & Klaim Kepemilikan
                        </h4>
                        <p class="text-xs text-red-700 leading-tight">
                            Klaim palsu akan dikenakan sanksi tata tertib akademik universitas.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Right Column: Card Info Barang (7 cols) (Figma: 620x560) -->
            <div class="lg:col-span-7">
                <div class="card-default p-6 sm:p-8 rounded-card bg-white border border-slate-200 shadow-sm space-y-6">
                    
                    <!-- Header: Badge & Title -->
                    <div>
                        <div class="flex items-center justify-between gap-4">
                            <?= render_status_badge($item['status']) ?>
                            <span class="text-xs text-slate-400 font-mono">#<?= htmlspecialchars($item['kode']) ?></span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-3 tracking-tight">
                            <?= htmlspecialchars($item['nama']) ?>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Kategori: <span class="font-medium text-slate-700"><?= htmlspecialchars($item['kategori']) ?></span> &bull; ID Laporan: #<?= htmlspecialchars($item['kode']) ?>
                        </p>
                    </div>

                    <div class="border-t border-slate-200"></div>

                    <!-- Meta Details -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5 mb-1">
                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-primary"></i>
                                <span>Lokasi Ditemukan</span>
                            </span>
                            <p class="text-sm font-semibold text-slate-800">
                                <?= htmlspecialchars($item['lokasi']) ?>
                            </p>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs text-slate-400 font-medium flex items-center gap-1.5 mb-1">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-primary"></i>
                                <span>Waktu Penemuan</span>
                            </span>
                            <p class="text-sm font-semibold text-slate-800">
                                <?= htmlspecialchars($item['waktu']) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Deskripsi Umum -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 mb-2">Deskripsi Umum</h3>
                        <p class="text-sm text-slate-600 leading-relaxed bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                            <?= htmlspecialchars($item['deskripsi']) ?>
                        </p>
                    </div>

                    <!-- Ciri Umum -->
                    <?php if (!empty($item['ciri_umum'])): ?>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 mb-2">Ciri-Ciri Barang</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                <?= htmlspecialchars($item['ciri_umum']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Tempat Simpan Box (Figma: 548x56, soft blue) -->
                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-200 text-blue-700 flex items-center justify-center shrink-0">
                            <i data-lucide="archive" class="w-4 h-4"></i>
                        </div>
                        <div class="text-xs sm:text-sm font-medium">
                            <span class="font-bold">Tempat Barang Disimpan:</span> <?= htmlspecialchars($item['tempat_simpan']) ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center gap-4">
                        <?php if ($item['status'] === 'Ditemukan' || $item['status'] === 'Dalam Verifikasi'): ?>
                            <button type="button" class="btn-open-klaim btn-primary h-12 px-8 rounded-btn text-base font-semibold shadow-sm flex items-center gap-2">
                                <i data-lucide="hand-helping" class="w-5 h-5"></i>
                                <span>Ini Barang Saya (Klaim)</span>
                            </button>
                        <?php endif; ?>
                        
                        <a href="riwayat.php" class="btn-secondary h-12 px-6 rounded-btn text-sm font-medium">
                            <span>Cek Riwayat Saya</span>
                        </a>
                    </div>

                </div>
            </div>

        </div>

    </div>
</main>

<!-- Modal Klaim Kepemilikan (Figma: Modal Container 560x520) -->
<div id="modalKlaim" class="hidden fixed inset-0 z-50 modal-overlay items-center justify-center p-4">
    <div class="bg-white w-full max-w-[560px] rounded-card shadow-modal border border-slate-200 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Modal Header -->
        <div class="p-5 sm:p-6 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5 text-slate-900">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-primary flex items-center justify-center">
                    <i data-lucide="shield-question" class="w-5 h-5"></i>
                </div>
                <h3 class="text-lg font-bold">Ajukan Klaim Kepemilikan</h3>
            </div>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-700 transition-colors p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Modal Body & Form -->
        <form id="formKlaim" action="klaim-berhasil.php" method="POST" class="p-5 sm:p-6 space-y-4">
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
                    "Sebutkan stiker, tanda goresan khusus, atau isi tumbler/barang saat tertinggal:"
                </p>
                <textarea 
                    name="jawaban_verifikasi" 
                    rows="3" 
                    required
                    placeholder="Contoh: Di bagian bawah ada stiker bulat Fakultas Ilmu Komputer dan sedikit penyok di sudut tutup..."
                    class="input-default text-xs md:text-sm"
                ></textarea>
            </div>

            <!-- Upload Bukti Pendukung -->
            <div>
                <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1">
                    Upload Bukti Pendukung (Foto Lama / Struk Pembelian)
                </label>
                <div class="upload-zone p-4 text-center rounded-btn bg-slate-50">
                    <input type="file" name="bukti_klaim" class="hidden" accept="image/*,.pdf">
                    <div class="upload-placeholder flex flex-col items-center justify-center gap-1 text-slate-500">
                        <i data-lucide="paperclip" class="w-5 h-5 text-primary"></i>
                        <span class="text-xs font-medium">Klik atau seret foto bukti di sini (Foto lama / struk pembelian)</span>
                        <span class="text-[10px] text-slate-400">PNG, JPG, PDF maks 5MB</span>
                    </div>
                    <div class="upload-preview hidden flex-col items-center justify-center gap-1 text-emerald-600">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                        <span class="file-name text-xs font-medium"></span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Buttons (Figma: Btn Batal 140x44, Btn Ajukan 140x44) -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" class="btn-close-modal btn-secondary h-11 px-5 rounded-btn text-sm font-medium">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    <span>Batal</span>
                </button>
                <button type="submit" class="btn-primary h-11 px-6 rounded-btn text-sm font-semibold">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Ajukan Klaim</span>
                </button>
            </div>
        </form>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
