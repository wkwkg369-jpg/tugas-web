<?php
/**
 * Seed data contoh untuk katalog SIP-Barang Kampus (versi GitHub).
 * Jalankan sekali: php deploy/seed-demo.php
 */
require __DIR__ . '/../includes/db.php';

$pdo = Database::getConnection();

$ada = (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
if ($ada > 0) {
    echo "Dilewati: sudah ada {$ada} item.\n";
    exit(0);
}

$items = [
    ['Tumbler Hydro Flask 32oz Biru', 'Aksesoris & Botol Minum', 'aksesoris', 'Ditemukan', 'temuan',
        'Ruang Kelas 304, Gedung Kuliah Bersama (GKB)', 'GKB R.304', '-2 days 09:15',
        'Tumbler stainless warna biru dongker ditemukan di meja kelas setelah perkuliahan pagi.',
        'Tumbler biru navy doff 32oz, tutup fleksibel hitam.',
        'Stiker bulat FIK di dasar botol dan baret halus di dekat leher.', 'Pos Keamanan Rektorat Gedung A (Lt. 1)', 'cup-soda', 1],
    ['Dompet Kulit Cokelat & KTM', 'Dompet & Kartu Identitas', 'dompet', 'Hilang', 'kehilangan',
        'Perpustakaan Pusat Lantai 2', 'Perpustakaan Lt. 2', '-3 days 10:30',
        'Dompet lipat kulit tertinggal di meja baca dekat rak ilmu sosial.',
        'Dompet kulit lipat dua warna cokelat gelap merk Bogesi.',
        'Slot tersembunyi berisi struk ATM BNI dan foto polaroid keluarga.', 'Dalam pencarian pelapor', 'wallet', 1],
    ['Kunci Motor Honda Vario 160', 'Kunci Kendaraan', 'kunci', 'Ditemukan', 'temuan',
        'Parkiran Sepeda Motor Barat Blok C', 'Parkiran Barat', '-4 days 17:00',
        'Kunci kontak smartkey tergantung di tiang pembatas parkiran motor.',
        'Remote keyless Honda dengan cover silikon merah dan gantungan kain.',
        'Gantungan kunci bertuliskan nama angkatan 2023.', 'Pos Satpam Parkiran Barat', 'key-round', 1],
    ['Jaket Hoodie Abu-abu Uniqlo', 'Tas & Pakaian', 'tas', 'Hilang', 'kehilangan',
        'Kantin Pusat Rumpun Ilmu Lt. 1', 'Kantin Pusat', '-5 days 12:15',
        'Jaket hoodie tertinggal di sandaran kursi kantin saat jam makan siang.',
        'Hoodie zipper polos abu-abu misty merk Uniqlo size L.',
        'Tali hoodie sebelah kanan sedikit terurai ujungnya.', 'Dalam pencarian pelapor', 'shirt', 1],
    ['Laptop Asus Vivobook 14', 'Elektronik & Gadget', 'elektronik', 'Ditemukan', 'temuan',
        'Laboratorium Komputer Gedung C Lt. 3', 'Lab Komputer C', '-6 days 15:45',
        'Laptop tertinggal di meja praktikum setelah sesi kelas berakhir.',
        'Asus Vivobook 14 warna silver, layar 14 inci.',
        'Stiker kampus di bagian dalam layar dan nama pemilik di bawah keyboard.', 'Pos Satpam Gedung C', 'laptop', 1],
    ['Kacamata Minus Frame Hitam', 'Aksesoris & Botol Minum', 'aksesoris', 'Ditemukan', 'temuan',
        'Masjid Kampus Lt. 1', 'Masjid Kampus', '-7 days 13:20',
        'Kacamata minus ditemukan di area wudhu masjid kampus.',
        'Frame plastik hitam ringan.',
        'Engsel kanan agak longgar.', 'Pos Satpam Dekanat Soshum', 'glasses', 1],
    ['Flashdisk Sandisk 64GB', 'Elektronik & Gadget', 'elektronik', 'Hilang', 'kehilangan',
        'Perpustakaan Pusat Lantai 1', 'Perpustakaan Lt. 1', '-10 days 11:20',
        'Flashdisk USB 3.0 tertancap di komputer OPAC perpustakaan.',
        'Sandisk Cruzer Blade 64GB bodi hitam strip merah.',
        'Berisi folder skripsi atas nama Sarah N.', 'Dalam pencarian pelapor', 'usb', 1],
    ['Payung Lipat Otomatis Biru', 'Aksesoris & Botol Minum', 'aksesoris', 'Dikembalikan', 'temuan',
        'Gazebo & Area Terbuka', 'Gazebo', '-9 days 08:30',
        'Payung lipat tertinggal di gazebo area terbuka, sudah diambil pemiliknya.',
        'Payung lipat otomatis biru tua dengan pegangan kayu.',
        'Ada inisial nama di tali pegangan.', 'Sudah diambil pemilik', 'umbrella', 1],
];

$stmt = $pdo->prepare('INSERT INTO items
    (kode, nama, kategori, kategori_slug, status, jenis_laporan, lokasi, lokasi_singkat,
     waktu_kejadian, deskripsi, ciri_umum, ciri_khusus, tempat_simpan, pelapor_id, pelapor_nama,
     pelapor_tipe, foto, icon, warna_placeholder, is_verified, created_at)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

$n = 0;
foreach ($items as $i => $it) {
    $stmt->execute([
        'TRX-' . (88220 + $i), $it[0], $it[1], $it[2], $it[3], $it[4], $it[5], $it[6],
        date('Y-m-d H:i:s', strtotime($it[7])), $it[8], $it[9], $it[10], $it[11],
        1, 'Budi Pratama (Mahasiswa)', 'Mahasiswa', null, $it[12], 'slate', 1,
        date('Y-m-d H:i:s', strtotime($it[7])),
    ]);
    $n++;
}

echo "Seed selesai: {$n} item ditambahkan.\n";
