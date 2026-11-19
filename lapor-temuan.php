<?php
$page_title = 'Lapor Temuan - SIP-Barang Kampus';
$current_page = 'lapor';
require_once __DIR__ . '/includes/header.php';

$user = get_logged_in_user();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_barang = trim($_POST['jenis_barang'] ?? '');
    $lokasi_penemuan = trim($_POST['lokasi_penemuan'] ?? '');
    $waktu_raw = trim($_POST['waktu_penemuan'] ?? '');
    $tempat_simpan = trim($_POST['tempat_simpan'] ?? '');
    $deskripsi_tambahan = trim($_POST['deskripsi_tambahan'] ?? '');

    if (empty($jenis_barang) || empty($lokasi_penemuan) || empty($tempat_simpan)) {
        $errorMessage = 'Semua kolom bertanda bintang (*) wajib diisi.';
    } else {
        $waktuFormatted = !empty($waktu_raw) ? date('d M Y, H:i', strtotime($waktu_raw)) . ' WIB' : date('d M Y, H:i') . ' WIB';

        // Categorize
        $kategori = 'Aksesoris & Botol Minum';
        $slug = 'aksesoris';
        $icon = 'sparkles';
        $namaBarang = $jenis_barang;

        if (stripos($jenis_barang, 'Elektronik') !== false || stripos($jenis_barang, 'HP') !== false || stripos($jenis_barang, 'Laptop') !== false) {
            $kategori = 'Elektronik & Gadget';
            $slug = 'elektronik';
            $icon = 'smartphone';
            $namaBarang = 'Elektronik / Gadget Temuan';
        } elseif (stripos($jenis_barang, 'Dompet') !== false || stripos($jenis_barang, 'KTM') !== false) {
            $kategori = 'Dompet & Kartu Identitas';
            $slug = 'dompet';
            $icon = 'wallet';
            $namaBarang = 'Dompet / Kartu Identitas Temuan';
        } elseif (stripos($jenis_barang, 'Kunci') !== false) {
            $kategori = 'Kunci Kendaraan';
            $slug = 'kunci';
            $icon = 'key-round';
            $namaBarang = 'Kunci Kendaraan Temuan';
        } elseif (stripos($jenis_barang, 'Tas') !== false || stripos($jenis_barang, 'Pakaian') !== false || stripos($jenis_barang, 'Jaket') !== false) {
            $kategori = 'Tas & Pakaian';
            $slug = 'tas';
            $icon = 'backpack';
            $namaBarang = 'Tas / Pakaian Temuan';
        } else {
            $namaBarang = 'Barang Temuan: ' . $jenis_barang;
        }

        // Handle File Upload
        $fotoFilename = null;
        if (isset($_FILES['foto_temuan']) && $_FILES['foto_temuan']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['foto_temuan']['tmp_name'];
            $origName = basename($_FILES['foto_temuan']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $fotoFilename = 'found_' . time() . '_' . random_int(100, 999) . '.' . $ext;
                $targetPath = __DIR__ . '/assets/uploads/' . $fotoFilename;
                move_uploaded_file($tmpName, $targetPath);
            }
        }

        $kode = 'TRX-' . random_int(88220, 88999);
        $pelaporId = $user ? $user['id'] : 1;
        $pelaporNama = $user ? ($user['nama'] . ' (Mahasiswa)') : 'Budi Pratama (Mahasiswa)';

        $deskripsi = "Ditemukan {$jenis_barang} di {$lokasi_penemuan}. Saat ini barang disimpan di: {$tempat_simpan}.";
        if (!empty($deskripsi_tambahan)) {
            $deskripsi .= ' ' . $deskripsi_tambahan;
        }

        $sql = "INSERT INTO items (
            kode, nama, kategori, kategori_slug, status, jenis_laporan, lokasi, lokasi_singkat,
            waktu_kejadian, deskripsi, ciri_umum, tempat_simpan, pelapor_id,
            pelapor_nama, pelapor_tipe, foto, icon, warna_placeholder, is_verified
        ) VALUES (?, ?, ?, ?, 'Dalam Verifikasi', 'temuan', ?, ?, ?, ?, ?, ?, ?, ?, 'Mahasiswa', ?, ?, '#d1fae5', 0)";

        $ok = db_execute($sql, [
            $kode,
            $namaBarang,
            $kategori,
            $slug,
            $lokasi_penemuan,
            $lokasi_penemuan,
            $waktuFormatted,
            $deskripsi,
            $deskripsi,
            $tempat_simpan,
            $pelaporId,
            $pelaporNama,
            $fotoFilename,
            $icon
        ]);

        if ($ok) {
            db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread, link) VALUES (?, ?, ?, 'package-check', 1, 'riwayat.php?tab=laporan')", [
                $pelaporId,
                'Laporan Temuan Berhasil Dibuat!',
                "Terima kasih atas kebaikan Anda. Laporan temuan '{$namaBarang}' (#{$kode}) telah tercatat di sistem dan menunggu verifikasi petugas keamanan."
            ]);

            set_flash_message('success', "Laporan temuan berhasil dikirim! Kode laporan: #{$kode}. Menunggu verifikasi petugas.");
            header('Location: riwayat.php?tab=laporan');
            exit;
        } else {
            $errorMessage = 'Gagal menyimpan laporan temuan. Silakan periksa kembali formulir Anda.';
        }
    }
}

require_once __DIR__ . '/includes/navbar.php';
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

            <?php if (!empty($errorMessage)): ?>
                <div class="mb-5 p-3.5 rounded-btn bg-red-50 border border-red-200 text-xs sm:text-sm text-red-700 flex items-center gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                    <span><?= htmlspecialchars($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <form action="lapor-temuan.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                
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
                        Foto Barang Temuan (Opsional)
                    </label>
                    <div class="upload-zone p-6 text-center rounded-btn bg-slate-50 border-2 border-dashed border-slate-200 hover:border-primary transition-colors cursor-pointer" onclick="this.querySelector('input').click()">
                        <input type="file" name="foto_temuan" class="hidden" accept="image/*" onchange="previewUpload(this)">
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
                                value="<?= date('Y-m-d\TH:i') ?>"
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
