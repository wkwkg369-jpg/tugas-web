<?php
$page_title = 'Lapor Kehilangan - SIP-Barang Kampus';
$current_page = 'lapor';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$categories = get_categories();
?>

<main class="flex-1 py-10">
    <div class="max-w-[800px] mx-auto px-4 sm:px-6">
        
        <!-- Breadcrumb / Back Link -->
        <div class="mb-6">
            <a href="index.php" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-primary transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>

        <!-- Card Form Kehilangan (Figma: Card Form Kehilangan 800x780) -->
        <div class="card-default p-6 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm">
            
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0">
                    <i data-lucide="file-question" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        Formulir Lapor Barang Hilang
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
                        Isi informasi barang Anda dengan akurat untuk mempermudah identifikasi dan verifikasi.
                    </p>
                </div>
            </div>

            <form action="riwayat.php" method="POST" class="space-y-6">
                
                <!-- 2 Kolom: Nama Barang & Kategori Barang -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                            Nama Barang <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="tag" class="w-4 h-4"></i>
                            </div>
                            <input 
                                type="text" 
                                name="nama_barang" 
                                required
                                placeholder="Contoh: Dompet Kulit Cokelat" 
                                class="input-default pl-10"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                            Kategori Barang <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="layers" class="w-4 h-4"></i>
                            </div>
                            <select name="kategori_barang" required class="input-default pl-10 text-slate-700 bg-white">
                                <option value="" disabled selected>Pilih Kategori Barang</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($cat['id'] !== 'all'): ?>
                                        <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Ciri Umum Barang (Tampil di Publik) -->
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                        Ciri Umum Barang (Tampil di Publik) <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        name="ciri_umum" 
                        rows="3" 
                        required
                        placeholder="Dompet kulit merk Bogesi warna cokelat tua dengan jahitan benang putih..." 
                        class="input-default text-sm"
                    ></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Ciri fisik yang bisa dilihat siapa saja untuk mengenali barang Anda.</p>
                </div>

                <!-- Ciri Khusus / Bukti Rahasia (Tidak Tampil di Publik) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="text-xs md:text-sm font-bold text-slate-900">
                            Ciri Khusus / Bukti Rahasia <span class="text-red-500">*</span>
                        </label>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <i data-lucide="lock" class="w-3 h-3"></i>
                            <span>Tidak Tampil di Publik</span>
                        </span>
                    </div>
                    <textarea 
                        name="ciri_khusus" 
                        rows="3" 
                        required
                        placeholder="Di dalam slot tersembunyi ada struk ATM BNI & foto polaroid keluarga..." 
                        class="input-default text-sm border-amber-200 focus:border-amber-400 focus:ring-amber-200"
                    ></textarea>
                    <p class="text-[11px] text-slate-400 mt-1">Hanya akan dilihat oleh admin/petugas saat proses verifikasi klaim barang.</p>
                </div>

                <!-- 2 Kolom: Perkiraan Lokasi & Tanggal Kehilangan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                            Perkiraan Lokasi Terakhir <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="map-pin" class="w-4 h-4"></i>
                            </div>
                            <input 
                                type="text" 
                                name="lokasi" 
                                required
                                placeholder="Perpustakaan Pusat Lantai 2" 
                                class="input-default pl-10"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                            Tanggal & Waktu Kehilangan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                            </div>
                            <input 
                                type="datetime-local" 
                                name="waktu_kejadian" 
                                required
                                class="input-default pl-10 text-slate-700"
                            >
                        </div>
                    </div>
                </div>

                <!-- Upload Foto Barang (Opsional) -->
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                        Upload Foto Barang (Opsional)
                    </label>
                    <div class="upload-zone p-6 text-center rounded-btn bg-slate-50 border-2 border-dashed border-slate-200 hover:border-primary transition-colors">
                        <input type="file" name="foto_barang" class="hidden" accept="image/*">
                        <div class="upload-placeholder flex flex-col items-center justify-center gap-1.5 text-slate-500">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-primary flex items-center justify-center mb-1">
                                <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                            </div>
                            <span class="text-xs md:text-sm font-medium">Tarik & lepas file foto di sini, atau <span class="text-primary underline">klik untuk memilih file</span></span>
                            <span class="text-[11px] text-slate-400">Format JPG, PNG maks 5MB</span>
                        </div>
                        <div class="upload-preview hidden flex-col items-center justify-center gap-1 text-emerald-600">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                            <span class="file-name text-xs font-semibold"></span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100">
                    <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim Laporan Kehilangan</span>
                    </button>
                </div>

            </form>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
