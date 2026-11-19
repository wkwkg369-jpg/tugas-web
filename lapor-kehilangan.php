<?php
$page_title = 'Lapor Kehilangan - SIP-Barang Kampus';
$current_page = 'lapor';
require_once __DIR__ . '/includes/header.php';

$categories = get_categories();
$user = get_logged_in_user();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kategori_barang = trim($_POST['kategori_barang'] ?? '');
    $ciri_umum = trim($_POST['ciri_umum'] ?? '');
    $ciri_khusus = trim($_POST['ciri_khusus'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $waktu_raw = trim($_POST['waktu_kejadian'] ?? '');

    if (empty($nama_barang) || empty($kategori_barang) || empty($ciri_umum) || empty($lokasi)) {
        $errorMessage = 'Semua kolom bertanda bintang (*) wajib diisi.';
    } else {
        // Format waktu
        $waktuFormatted = !empty($waktu_raw) ? date('d M Y, H:i', strtotime($waktu_raw)) . ' WIB' : date('d M Y, H:i') . ' WIB';

        // Slug kategori
        $slug = 'lainnya';
        foreach ($categories as $cat) {
            if ($cat['name'] === $kategori_barang) {
                $slug = $cat['slug'];
                break;
            }
        }

        // Handle File Upload (divalidasi ekstensi + isi berkas)
        $fotoFilename = null;
        if (isset($_FILES['foto_barang']) && $_FILES['foto_barang']['error'] !== UPLOAD_ERR_NO_FILE) {
            $cek = validate_upload($_FILES['foto_barang'], ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
            if ($cek['ok']) {
                $fotoFilename = 'lost_' . time() . '_' . random_int(100, 999) . '.' . $cek['ext'];
                $targetPath = ensure_upload_dir() . '/' . $fotoFilename;
                if (!move_uploaded_file($_FILES['foto_barang']['tmp_name'], $targetPath)) {
                    $fotoFilename = null;
                    $errorMessage = 'Foto gagal disimpan di server. Silakan coba lagi.';
                }
            } else {
                $errorMessage = $cek['error'];
            }
        }

        // Generate unique tracking code
        $kode = 'TRX-' . random_int(88220, 88999);

        // Icon based on category
        $iconMap = [
            'elektronik' => 'smartphone',
            'dompet' => 'wallet',
            'kunci' => 'key-round',
            'tas' => 'backpack',
            'aksesoris' => 'sparkles',
            'lainnya' => 'package'
        ];
        $icon = $iconMap[$slug] ?? 'package';

        // Tamu tidak dipinjamkan identitas akun lain.
        $pelaporId = $user ? (int) $user['id'] : null;
        $pelaporNama = $user ? ($user['nama'] . ' (Mahasiswa)') : 'Tamu (belum masuk)';
        // Notifikasi hanya untuk pengguna yang benar-benar login (tamu tidak dipinjami akun lain)
        $notifyUid = $user ? (int) $user['id'] : 0;

        if (!empty($errorMessage)) {
            // Validasi unggahan gagal: jangan simpan laporan.
        } else {

        $sql = "INSERT INTO items (
            kode, nama, kategori, kategori_slug, status, jenis_laporan, lokasi, lokasi_singkat,
            waktu_kejadian, deskripsi, ciri_umum, ciri_khusus, tempat_simpan, pelapor_id,
            pelapor_nama, pelapor_tipe, foto, icon, warna_placeholder, is_verified
        ) VALUES (?, ?, ?, ?, 'Dalam Verifikasi', 'kehilangan', ?, ?, ?, ?, ?, ?, 'Dalam pencarian pelapor', ?, ?, 'Mahasiswa', ?, ?, '#fee2e2', 0)";

        $ok = db_execute($sql, [
            $kode,
            $nama_barang,
            $kategori_barang,
            $slug,
            $lokasi,
            $lokasi,
            $waktuFormatted,
            $ciri_umum,
            $ciri_umum,
            $ciri_khusus,
            $pelaporId,
            $pelaporNama,
            $fotoFilename,
            $icon
        ]);

        if ($ok) {
            // Notifikasi untuk pelapor
            notify_user(
                $notifyUid,
                'Laporan Barang Hilang Terkirim!',
                "Laporan '{$nama_barang}' (#{$kode}) telah berhasil didaftarkan dan segera ditinjau oleh petugas keamanan.",
                'bell',
                'riwayat.php?tab=laporan'
            );

            // Notifikasi untuk admin & petugas: ada laporan kehilangan baru
            notify_admins(
                'Laporan Kehilangan Baru Masuk',
                "Laporan kehilangan '{$nama_barang}' (#{$kode}) dari {$pelaporNama} menunggu verifikasi petugas.",
                'clipboard-list',
                'admin/verifikasi-laporan.php'
            );

            set_flash_message('success', "Laporan barang hilang '{$nama_barang}' berhasil dibuat dengan ID #{$kode}! Menunggu verifikasi petugas.");
            header('Location: riwayat.php?tab=laporan');
            exit;
        } else {
            $errorMessage = 'Gagal menyimpan laporan. Silakan periksa kembali data Anda.';
        }
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
                        Isi informasi barang Anda dengan akurat untuk mempermudah identifikasi dan verifikasi petugas.
                    </p>
                </div>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="mb-5 p-3.5 rounded-btn bg-red-50 border border-red-200 text-xs sm:text-sm text-red-700 flex items-center gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                    <span><?= htmlspecialchars($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <form action="lapor-kehilangan.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <?= csrf_field() ?>
                
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
                                value="<?= htmlspecialchars($_POST['nama_barang'] ?? '') ?>"
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
                                        <option value="<?= htmlspecialchars($cat['name']) ?>" <?= ($_POST['kategori_barang'] ?? '') === $cat['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
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
                    ><?= htmlspecialchars($_POST['ciri_umum'] ?? '') ?></textarea>
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
                    ><?= htmlspecialchars($_POST['ciri_khusus'] ?? '') ?></textarea>
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
                                value="<?= htmlspecialchars($_POST['lokasi'] ?? '') ?>"
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
                                value="<?= htmlspecialchars($_POST['waktu_kejadian'] ?? date('Y-m-d\TH:i')) ?>"
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
                    <div class="upload-zone p-6 text-center rounded-btn bg-slate-50 border-2 border-dashed border-slate-200 hover:border-primary transition-colors cursor-pointer" onclick="this.querySelector('input').click()">
                        <input type="file" name="foto_barang" class="hidden" accept="image/*" onchange="previewUpload(this)">
                        <div class="upload-placeholder flex flex-col items-center justify-center gap-1.5 text-slate-500">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-primary flex items-center justify-center mb-1">
                                <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                            </div>
                            <span class="text-xs md:text-sm font-medium">Tarik & lepas file foto di sini, atau <span class="text-primary underline">klik untuk memilih file</span></span>
                            <span class="text-[11px] text-slate-400">Format JPG, PNG, WEBP maks 5MB</span>
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
