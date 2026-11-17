<?php
$page_title = 'Lapor Temuan - SIP-Barang Kampus';
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

        <!-- Card Form Temuan (Figma: Card Form Temuan 800x700) -->
        <div class="card-default p-6 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm">
            
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <i data-lucide="package-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                        Formulir Lapor Barang Temuan
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 leading-relaxed">
                        Terima kasih telah berinisiatif mengembalikan barang temuan ke pemilik aslinya.
                    </p>
                </div>
            </div>

            <form action="riwayat.php" method="POST" class="space-y-6">
                
                <!-- Jenis / Kategori Barang Dropdown -->
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                        Jenis / Kategori Barang <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="tag" class="w-4 h-4"></i>
                        </div>
                        <select name="jenis_barang" required class="input-default pl-10 text-slate-700 bg-white">
                            <option value="" disabled selected>Pilih Jenis / Kategori Barang Temuan</option>
                            <option value="Aksesoris & Botol Minum (Tumbler)">Aksesoris & Botol Minum (Tumbler)</option>
                            <option value="Elektronik & Gadget (HP, Laptop, Flashdisk)">Elektronik & Gadget (HP, Laptop, Flashdisk)</option>
                            <option value="Dompet & Kartu Identitas (KTM, KTP, ATM)">Dompet & Kartu Identitas (KTM, KTP, ATM)</option>
                            <option value="Kunci Kendaraan (Motor / Mobil)">Kunci Kendaraan (Motor / Mobil)</option>
                            <option value="Tas & Pakaian (Jaket, Sweater, Ransel)">Tas & Pakaian (Jaket, Sweater, Ransel)</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>
                </div>

                <!-- Foto Barang Temuan -->
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                        Foto Barang Temuan <span class="text-red-500">*</span>
                    </label>
                    <div class="upload-zone p-6 text-center rounded-btn bg-slate-50 border-2 border-dashed border-slate-200 hover:border-primary transition-colors">
                        <input type="file" name="foto_temuan" required class="hidden" accept="image/*">
                        <div class="upload-placeholder flex flex-col items-center justify-center gap-1.5 text-slate-500">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-1">
                                <i data-lucide="camera" class="w-5 h-5"></i>
                            </div>
                            <span class="text-xs md:text-sm font-medium">Ambil foto langsung atau <span class="text-primary underline">pilih file gambar barang temuan</span></span>
                            <span class="text-[11px] text-slate-400">Pastikan ciri khusus utama tidak terlalu jelas di foto agar aman saat verifikasi</span>
                        </div>
                        <div class="upload-preview hidden flex-col items-center justify-center gap-1 text-emerald-600">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                            <span class="file-name text-xs font-semibold"></span>
                        </div>
                    </div>
                </div>

                <!-- 2 Kolom: Lokasi & Waktu Penemuan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                            Lokasi Penemuan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="map-pin" class="w-4 h-4"></i>
                            </div>
                            <input 
                                type="text" 
                                name="lokasi_penemuan" 
                                required
                                placeholder="Contoh: Ruang Kelas 304, Gedung GKB" 
                                class="input-default pl-10"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                            Tanggal & Waktu Penemuan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="calendar" class="w-4 h-4"></i>
                            </div>
                            <input 
                                type="datetime-local" 
                                name="waktu_penemuan" 
                                required
                                class="input-default pl-10 text-slate-700"
                            >
                        </div>
                    </div>
                </div>

                <!-- Tempat Barang Disimpan Saat Ini Dropdown -->
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                        Tempat Barang Disimpan Saat Ini <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="archive" class="w-4 h-4"></i>
                        </div>
                        <select name="tempat_simpan" required class="input-default pl-10 text-slate-700 bg-white">
                            <option value="" disabled selected>Pilih Tempat Penyimpanan Barang Saat Ini</option>
                            <option value="Dititipkan di Pos Keamanan Rektorat Gedung A (Lt. 1)">Dititipkan di Pos Keamanan Rektorat Gedung A (Lt. 1)</option>
                            <option value="Dititipkan di Pos Satpam Parkiran Barat">Dititipkan di Pos Satpam Parkiran Barat</option>
                            <option value="Dititipkan di Resepsionis / Tata Usaha Fakultas">Dititipkan di Resepsionis / Tata Usaha Fakultas</option>
                            <option value="Masih disimpan oleh saya (pelapor temuan)">Masih disimpan oleh saya (pelapor temuan)</option>
                        </select>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Sangat disarankan untuk menitipkan barang berharga di pos keamanan terdekat.</p>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-100">
                    <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim Laporan Temuan</span>
                    </button>
                </div>

            </form>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
