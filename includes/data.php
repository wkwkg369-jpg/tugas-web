<?php
/**
 * Mock Data & Helper Functions untuk SIP-Barang Kampus
 * Didesain rapi agar teman backend dapat dengan mudah mengganti data array ini
 * dengan query database (MySQL / PDO) nantinya.
 */

// Kategori Barang
function get_categories() {
    return [
        ['id' => 'all', 'name' => 'Semua Kategori'],
        ['id' => 'elektronik', 'name' => 'Elektronik & Gadget'],
        ['id' => 'dompet', 'name' => 'Dompet & Kartu Identitas'],
        ['id' => 'kunci', 'name' => 'Kunci Kendaraan'],
        ['id' => 'tas', 'name' => 'Tas & Pakaian'],
        ['id' => 'aksesoris', 'name' => 'Aksesoris & Botol Minum']
    ];
}

// Lokasi Kampus
function get_locations() {
    return [
        'Semua Lokasi Kampus',
        'Perpustakaan Pusat',
        'Gedung Kuliah Bersama (GKB)',
        'Parkiran Sepeda Motor Barat',
        'Kantin Pusat Rumpun Ilmu',
        'Laboratorium Komputer',
        'Gedung Rektorat',
        'Masjid Kampus',
        'Halte Bus Kampus',
        'Gazebo & Area Terbuka'
    ];
}

// Data Barang (Katalog & Beranda)
function get_mock_items() {
    return [
        [
            'id' => 1,
            'kode' => 'TRX-88219',
            'nama' => 'Tumbler Hydro Flask 32oz Biru',
            'kategori' => 'Aksesoris & Botol Minum',
            'kategori_slug' => 'aksesoris',
            'status' => 'Ditemukan',
            'lokasi' => 'Ruang Kelas 304, Gedung Kuliah Bersama (GKB)',
            'lokasi_singkat' => 'R.304 Gedung Kuliah Bersama',
            'waktu' => '16 November 2026, pukul 09:15 WIB',
            'waktu_singkat' => 'Kemarin, 14:15 WIB',
            'deskripsi' => 'Ditemukan tumbler stainless warna biru dongker dalam kondisi baik. Terdapat stiker fakultas di bagian bawah botol. Tidak ada tanda kepemilikan nama tertulis.',
            'ciri_umum' => 'Tumbler warna biru navy doff ukuran 32oz dengan tutup fleksibel hitam.',
            'ciri_khusus' => 'Ada stiker bulat FIK di dasar botol dan sedikit baret halus di dekat leher tumbler.',
            'tempat_simpan' => 'Pos Keamanan Rektorat Gedung A (Lt. 1)',
            'pelapor' => 'Petugas Keamanan Kampus (Pos A)',
            'icon' => 'smartphone',
            'warna_placeholder' => '#dbeafe'
        ],
        [
            'id' => 2,
            'kode' => 'TRX-88218',
            'nama' => 'Dompet Kulit Cokelat & KTM',
            'kategori' => 'Dompet & Kartu Identitas',
            'kategori_slug' => 'dompet',
            'status' => 'Hilang',
            'lokasi' => 'Perpustakaan Pusat Lantai 2',
            'lokasi_singkat' => 'Perpustakaan Pusat Lt. 2',
            'waktu' => '17 November 2026, 10:30 WIB',
            'waktu_singkat' => 'Hari ini, 10:30 WIB',
            'deskripsi' => 'Dompet lipat kulit warna cokelat tua tertinggal di meja baca dekat rak buku ilmu sosial. Di dalamnya terdapat KTM dan kartu penting lainnya.',
            'ciri_umum' => 'Dompet kulit lipat dua warna cokelat gelap merk Bogesi.',
            'ciri_khusus' => 'Di slot tersembunyi tersimpan struk ATM BNI dan foto polaroid keluarga.',
            'tempat_simpan' => 'Dalam pencarian pelapor',
            'pelapor' => 'Budi Pratama (Mahasiswa)',
            'icon' => 'wallet',
            'warna_placeholder' => '#fee2e2'
        ],
        [
            'id' => 3,
            'kode' => 'TRX-88215',
            'nama' => 'Kunci Motor Honda Vario 160',
            'kategori' => 'Kunci Kendaraan',
            'kategori_slug' => 'kunci',
            'status' => 'Ditemukan',
            'lokasi' => 'Parkiran Sepeda Motor Barat Blok C',
            'lokasi_singkat' => 'Parkiran Sepeda Motor Barat',
            'waktu' => '14 November 2026, 17:00 WIB',
            'waktu_singkat' => '14 Nov 2026, 17:00 WIB',
            'deskripsi' => 'Kunci kontak smartkey Honda Vario tergantung di tiang pembatas parkiran motor.',
            'ciri_umum' => 'Remote keyless Honda dengan cover silikon merah dan gantungan tali kain.',
            'ciri_khusus' => 'Gantungan kunci bertuliskan nama angkatan 2023.',
            'tempat_simpan' => 'Pos Satpam Parkiran Barat',
            'pelapor' => 'Petugas Parkir',
            'icon' => 'key-round',
            'warna_placeholder' => '#d1fae5'
        ],
        [
            'id' => 4,
            'kode' => 'TRX-88210',
            'nama' => 'Jaket Hoodie Abu-abu Uniqlo',
            'kategori' => 'Tas & Pakaian',
            'kategori_slug' => 'tas',
            'status' => 'Hilang',
            'lokasi' => 'Kantin Pusat Rumpun Ilmu Lt. 1',
            'lokasi_singkat' => 'Kantin RIK Lt. 1',
            'waktu' => '13 November 2026, 12:15 WIB',
            'waktu_singkat' => '13 Nov 2026, 12:15 WIB',
            'deskripsi' => 'Jaket hoodie warna abu-abu misty size L tertinggal di sandaran kursi kantin saat jam makan siang.',
            'ciri_umum' => 'Hoodie zipper polos abu-abu merk Uniqlo size L.',
            'ciri_khusus' => 'Tali hoodie sebelah kanan sedikit terurai ujungnya.',
            'tempat_simpan' => 'Dalam pencarian pelapor',
            'pelapor' => 'Ahmad Rian (Mahasiswa)',
            'icon' => 'backpack',
            'warna_placeholder' => '#fee2e2'
        ],
        [
            'id' => 5,
            'kode' => 'TRX-88204',
            'nama' => 'Laptop Asus Vivobook 14',
            'kategori' => 'Elektronik & Gadget',
            'kategori_slug' => 'elektronik',
            'status' => 'Ditemukan',
            'lokasi' => 'Laboratorium Komputer Gedung B',
            'lokasi_singkat' => 'Lab Komputer Gedung B',
            'waktu' => '12 November 2026, 16:30 WIB',
            'waktu_singkat' => '12 Nov 2026, 16:30 WIB',
            'deskripsi' => 'Laptop Asus warna silver lengkap dengan sleeve bag hitam ditemukan di meja PC Lab 2.',
            'ciri_umum' => 'Laptop 14 inch warna silver abu dengan charger asli.',
            'ciri_khusus' => 'Ada stiker Github dan sticker logo React di cover belakang monitor.',
            'tempat_simpan' => 'Ruang Teknisi Lab Komputer Lt. 2',
            'pelapor' => 'Laboran Komputer',
            'icon' => 'laptop',
            'warna_placeholder' => '#d1fae5'
        ],
        [
            'id' => 6,
            'kode' => 'TRX-88198',
            'nama' => 'Payung Lipat Otomatis Biru',
            'kategori' => 'Aksesoris & Botol Minum',
            'kategori_slug' => 'aksesoris',
            'status' => 'Dikembalikan',
            'lokasi' => 'Masjid Ukhuwah Islamiyah / Lobi Rektorat',
            'lokasi_singkat' => 'Lobi Utama Gedung Rektorat',
            'waktu' => '10 November 2026, 13:00 WIB',
            'waktu_singkat' => '10 Nov 2026, 13:00 WIB',
            'deskripsi' => 'Payung lipat tombol otomatis warna navy blue, sudah berhasil diambil oleh pemilik aslinya setelah verifikasi tanda pengenal.',
            'ciri_umum' => 'Payung lipat 3 warna biru gelap pegangan kayu.',
            'ciri_khusus' => 'Ada inisial nama kecil di tali pengikat.',
            'tempat_simpan' => 'Sudah diserahkan ke pemilik',
            'pelapor' => 'Petugas Keamanan Lobi',
            'icon' => 'shirt',
            'warna_placeholder' => '#e0e7ff'
        ],
        [
            'id' => 7,
            'kode' => 'TRX-88190',
            'nama' => 'Kacamata Minus Frame Hitam',
            'kategori' => 'Aksesoris & Botol Minum',
            'kategori_slug' => 'aksesoris',
            'status' => 'Dalam Verifikasi',
            'lokasi' => 'Gazebo Rumpun Soshum',
            'lokasi_singkat' => 'Gazebo Rumpun Soshum',
            'waktu' => '09 November 2026, 15:45 WIB',
            'waktu_singkat' => '09 Nov 2026, 15:45 WIB',
            'deskripsi' => 'Kacamata baca frame kotak warna hitam doff lengkap dengan kotak kacamata abu-abu.',
            'ciri_umum' => 'Frame kacamata plastik hitam ringan.',
            'ciri_khusus' => 'Engsel kanan agak sedikit longgar.',
            'tempat_simpan' => 'Pos Satpam Dekanat Soshum',
            'pelapor' => 'Mahasiswa Soshum',
            'icon' => 'wallet',
            'warna_placeholder' => '#fef3c7'
        ],
        [
            'id' => 8,
            'kode' => 'TRX-88185',
            'nama' => 'Flashdisk Sandisk 64GB',
            'kategori' => 'Elektronik & Gadget',
            'kategori_slug' => 'elektronik',
            'status' => 'Hilang',
            'lokasi' => 'Perpustakaan Pusat Lantai 1',
            'lokasi_singkat' => 'Perpustakaan Pusat Lt. 1',
            'waktu' => '08 November 2026, 11:20 WIB',
            'waktu_singkat' => '08 Nov 2026, 11:20 WIB',
            'deskripsi' => 'Flashdisk USB 3.0 warna merah hitam tertancap di komputer OPAC perpustakaan.',
            'ciri_umum' => 'Sandisk Cruzer Blade 64GB bodi hitam strip merah.',
            'ciri_khusus' => 'Berisi folder skripsi atas nama Sarah N.',
            'tempat_simpan' => 'Dalam pencarian pelapor',
            'pelapor' => 'Sarah Nurhaliza',
            'icon' => 'smartphone',
            'warna_placeholder' => '#fee2e2'
        ],
        [
            'id' => 9,
            'kode' => 'TRX-88180',
            'nama' => 'Charger Laptop Lenovo 65W',
            'kategori' => 'Elektronik & Gadget',
            'kategori_slug' => 'elektronik',
            'status' => 'Ditemukan',
            'lokasi' => 'Halte Bus Kuning Teknik',
            'lokasi_singkat' => 'Halte Bus Kuning Teknik',
            'waktu' => '07 November 2026, 09:10 WIB',
            'waktu_singkat' => '07 Nov 2026, 09:10 WIB',
            'deskripsi' => 'Adaptor charger USB Type-C Lenovo 65W original tertinggal di bangku tunggu halte.',
            'ciri_umum' => 'Charger kotak warna hitam kabel USB-C tebal.',
            'ciri_khusus' => 'Ada isolasi hitam pelindung di ujung kabel dekat port.',
            'tempat_simpan' => 'Pos Satpam Gerbang Utama Teknik',
            'pelapor' => 'Driver Bus Kampus',
            'icon' => 'laptop',
            'warna_placeholder' => '#d1fae5'
        ]
    ];
}

// Data Notifikasi
function get_mock_notifications() {
    return [
        [
            'id' => 1,
            'judul' => 'Laporan Barang Hilang Telah Diverifikasi Petugas!',
            'pesan' => 'Laporan Anda mengenai "KTM Makara & Dompet Kulit" telah disetujui dan kini aktif di daftar pencarian kampus.',
            'waktu' => '5 menit lalu',
            'unread' => true,
            'icon' => 'bell'
        ],
        [
            'id' => 2,
            'judul' => 'Potensi Temuan: Ada Barang Baru yang Cocok!',
            'pesan' => 'Seorang mahasiswa baru saja melaporkan temuan "Tumbler Biru di GKB R.304". Periksa detailnya sekarang.',
            'waktu' => '1 jam lalu',
            'unread' => true,
            'icon' => 'search'
        ],
        [
            'id' => 3,
            'judul' => 'Klaim Kepemilikan Barang Telah Disetujui!',
            'pesan' => 'Klaim Anda untuk "Kunci Motor Honda Vario" disetujui. Silakan ambil barang di Pos Satpam Barat.',
            'waktu' => 'Kemarin',
            'unread' => false,
            'icon' => 'check-circle'
        ],
        [
            'id' => 4,
            'judul' => 'Laporan Selesai: Barang Berhasil Dikembalikan',
            'pesan' => 'Status laporan "Payung Lipat Otomatis" telah diperbarui menjadi Selesai / Dikembalikan ke pemilik.',
            'waktu' => '3 hari lalu',
            'unread' => false,
            'icon' => 'package-check'
        ],
        [
            'id' => 5,
            'judul' => 'Tips Keamanan: Beri Tanda Khusus pada Barang Anda',
            'pesan' => 'Selalu beri label nama atau gantungan unik pada barang berharga untuk mempermudah identifikasi penemuan.',
            'waktu' => '1 minggu lalu',
            'unread' => false,
            'icon' => 'shield'
        ]
    ];
}

// Helper Render Badge Status
function render_status_badge($status, $size = 'normal') {
    $classes = [
        'Hilang' => 'bg-red-50 text-red-600 border border-red-200',
        'Ditemukan' => 'bg-emerald-50 text-emerald-600 border border-emerald-200',
        'Dalam Verifikasi' => 'bg-amber-50 text-amber-600 border border-amber-200',
        'Dikembalikan' => 'bg-indigo-50 text-indigo-600 border border-indigo-200',
        'Ditutup' => 'bg-slate-100 text-slate-500 border border-slate-200'
    ];
    
    $dotClasses = [
        'Hilang' => 'bg-red-500',
        'Ditemukan' => 'bg-emerald-500',
        'Dalam Verifikasi' => 'bg-amber-500',
        'Dikembalikan' => 'bg-indigo-500',
        'Ditutup' => 'bg-slate-400'
    ];
    
    $cls = $classes[$status] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
    $dotCls = $dotClasses[$status] ?? 'bg-slate-400';
    $padding = $size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs md:text-sm';

    return '<span class="inline-flex items-center gap-1.5 font-medium rounded-full ' . $padding . ' ' . $cls . '">
        <span class="w-1.5 h-1.5 rounded-full ' . $dotCls . '"></span>
        ' . htmlspecialchars($status) . '
    </span>';
}
?>
