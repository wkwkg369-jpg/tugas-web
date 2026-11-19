<?php
/**
 * Database Connection & Migration Layer
 * SIP-Barang Kampus
 * 
 * Mendukung dual-engine otomatis:
 * 1. MySQL (Default XAMPP: 127.0.0.1:3306, user 'root', password '')
 * 2. SQLite Fallback (Otomatis jika MySQL belum dinyalakan di XAMPP Control Panel)
 * 
 * Menjamin aplikasi 100% langsung dapat berjalan tanpa error konfigurasi.
 */

class Database {
    private static ?PDO $instance = null;
    private static string $driver = '';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Coba koneksi ke MySQL XAMPP terlebih dahulu
        $mysqlHost = '127.0.0.1';
        $mysqlPort = '3306';
        $mysqlUser = 'root';
        $mysqlPass = '';
        $dbName = 'sip_barang_kampus';

        try {
            // Coba koneksi ke server MySQL tanpa nama DB dulu untuk create database jika belum ada
            $dsn = "mysql:host={$mysqlHost};port={$mysqlPort};charset=utf8mb4";
            $pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2
            ]);

            // Buat database jika belum ada
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            self::$instance = $pdo;
            self::$driver = 'mysql';
        } catch (Throwable $e) {
            // Jika MySQL gagal (belum di-start di XAMPP), gunakan SQLite fallback
            $sqlitePath = __DIR__ . '/sip_barang.sqlite';
            try {
                $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                $pdo->exec("PRAGMA foreign_keys = ON;");
                self::$instance = $pdo;
                self::$driver = 'sqlite';
            } catch (Throwable $se) {
                die("Gagal menginisialisasi database: " . $se->getMessage());
            }
        }

        // Inisialisasi tabel dan seed data jika belum ada
        self::initSchema(self::$instance, self::$driver);

        return self::$instance;
    }

    public static function getDriver(): string {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$driver;
    }

    private static function initSchema(PDO $pdo, string $driver): void {
        $isSqlite = ($driver === 'sqlite');

        // Check if table 'items' exists
        $tableExists = false;
        try {
            if ($isSqlite) {
                $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='items'")->fetch();
                $tableExists = !empty($check);
            } else {
                $check = $pdo->query("SHOW TABLES LIKE 'items'")->fetch();
                $tableExists = !empty($check);
            }
        } catch (Throwable $e) {
            $tableExists = false;
        }

        if ($tableExists) {
            return;
        }

        // Schema Definitions
        if ($isSqlite) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    nama TEXT NOT NULL,
                    nim TEXT UNIQUE,
                    prodi TEXT,
                    email TEXT UNIQUE NOT NULL,
                    phone TEXT,
                    password TEXT NOT NULL,
                    role TEXT NOT NULL DEFAULT 'mahasiswa',
                    avatar TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    slug TEXT UNIQUE NOT NULL,
                    icon TEXT DEFAULT 'tag'
                );

                CREATE TABLE IF NOT EXISTS locations (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL UNIQUE
                );

                CREATE TABLE IF NOT EXISTS items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    kode TEXT UNIQUE NOT NULL,
                    nama TEXT NOT NULL,
                    kategori TEXT NOT NULL,
                    kategori_slug TEXT NOT NULL,
                    status TEXT NOT NULL DEFAULT 'Hilang',
                    jenis_laporan TEXT NOT NULL DEFAULT 'kehilangan',
                    lokasi TEXT NOT NULL,
                    lokasi_singkat TEXT NOT NULL,
                    waktu_kejadian TEXT NOT NULL,
                    deskripsi TEXT NOT NULL,
                    ciri_umum TEXT,
                    ciri_khusus TEXT,
                    tempat_simpan TEXT,
                    pelapor_id INTEGER,
                    pelapor_nama TEXT NOT NULL,
                    pelapor_tipe TEXT NOT NULL DEFAULT 'Mahasiswa',
                    foto TEXT,
                    icon TEXT DEFAULT 'package',
                    warna_placeholder TEXT DEFAULT '#dbeafe',
                    is_verified INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS claims (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    kode_klaim TEXT UNIQUE NOT NULL,
                    item_id INTEGER NOT NULL,
                    user_id INTEGER,
                    nama_pengklaim TEXT NOT NULL,
                    email_pengklaim TEXT,
                    phone_pengklaim TEXT,
                    jawaban_verifikasi TEXT NOT NULL,
                    bukti_foto TEXT,
                    status TEXT NOT NULL DEFAULT 'Dalam Verifikasi',
                    catatan_petugas TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    verified_at DATETIME,
                    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
                );

                CREATE TABLE IF NOT EXISTS notifications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER DEFAULT 1,
                    judul TEXT NOT NULL,
                    pesan TEXT NOT NULL,
                    icon TEXT DEFAULT 'bell',
                    is_unread INTEGER DEFAULT 1,
                    link TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );

                CREATE TABLE IF NOT EXISTS otp_codes (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email TEXT NOT NULL,
                    code TEXT NOT NULL,
                    type TEXT DEFAULT 'register',
                    is_used INTEGER DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                );
            ");
        } else {
            // MySQL Schema
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `nama` VARCHAR(150) NOT NULL,
                    `nim` VARCHAR(30) UNIQUE NULL,
                    `prodi` VARCHAR(150) NULL,
                    `email` VARCHAR(150) UNIQUE NOT NULL,
                    `phone` VARCHAR(30) NULL,
                    `password` VARCHAR(255) NOT NULL,
                    `role` ENUM('mahasiswa', 'admin', 'petugas') NOT NULL DEFAULT 'mahasiswa',
                    `avatar` VARCHAR(255) NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `categories` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) UNIQUE NOT NULL,
                    `icon` VARCHAR(50) DEFAULT 'tag'
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `locations` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(150) NOT NULL UNIQUE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `items` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `kode` VARCHAR(30) UNIQUE NOT NULL,
                    `nama` VARCHAR(200) NOT NULL,
                    `kategori` VARCHAR(100) NOT NULL,
                    `kategori_slug` VARCHAR(100) NOT NULL,
                    `status` ENUM('Hilang', 'Ditemukan', 'Dalam Verifikasi', 'Dikembalikan', 'Ditutup') NOT NULL DEFAULT 'Hilang',
                    `jenis_laporan` ENUM('kehilangan', 'temuan') NOT NULL DEFAULT 'kehilangan',
                    `lokasi` VARCHAR(255) NOT NULL,
                    `lokasi_singkat` VARCHAR(150) NOT NULL,
                    `waktu_kejadian` VARCHAR(100) NOT NULL,
                    `deskripsi` TEXT NOT NULL,
                    `ciri_umum` TEXT NULL,
                    `ciri_khusus` TEXT NULL,
                    `tempat_simpan` VARCHAR(255) NULL,
                    `pelapor_id` INT NULL,
                    `pelapor_nama` VARCHAR(150) NOT NULL,
                    `pelapor_tipe` VARCHAR(50) NOT NULL DEFAULT 'Mahasiswa',
                    `foto` VARCHAR(255) NULL,
                    `icon` VARCHAR(50) DEFAULT 'package',
                    `warna_placeholder` VARCHAR(20) DEFAULT '#dbeafe',
                    `is_verified` TINYINT(1) DEFAULT 1,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `claims` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `kode_klaim` VARCHAR(30) UNIQUE NOT NULL,
                    `item_id` INT NOT NULL,
                    `user_id` INT NULL,
                    `nama_pengklaim` VARCHAR(150) NOT NULL,
                    `email_pengklaim` VARCHAR(150) NULL,
                    `phone_pengklaim` VARCHAR(30) NULL,
                    `jawaban_verifikasi` TEXT NOT NULL,
                    `bukti_foto` VARCHAR(255) NULL,
                    `status` ENUM('Dalam Verifikasi', 'Disetujui', 'Ditolak', 'Selesai') NOT NULL DEFAULT 'Dalam Verifikasi',
                    `catatan_petugas` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `verified_at` DATETIME NULL,
                    FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT DEFAULT 1,
                    `judul` VARCHAR(255) NOT NULL,
                    `pesan` TEXT NOT NULL,
                    `icon` VARCHAR(50) DEFAULT 'bell',
                    `is_unread` TINYINT(1) DEFAULT 1,
                    `link` VARCHAR(255) NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

                CREATE TABLE IF NOT EXISTS `otp_codes` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(150) NOT NULL,
                    `code` VARCHAR(10) NOT NULL,
                    `type` VARCHAR(50) DEFAULT 'register',
                    `is_used` TINYINT(1) DEFAULT 0,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // Seed Initial Mock Data
        self::seedData($pdo);
    }

    private static function seedData(PDO $pdo): void {
        // 1. Seed Users
        $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);

        $users = [
            [
                'nama' => 'Budi Pratama',
                'nim' => '2024080123',
                'prodi' => 'Sistem Informasi - Fakultas Ilmu Komputer',
                'email' => 'budi.pratama@ui.ac.id',
                'phone' => '081234567890',
                'password' => $defaultPassword,
                'role' => 'mahasiswa'
            ],
            [
                'nama' => 'Sarah Nurhaliza',
                'nim' => '2024080456',
                'prodi' => 'Ilmu Komputer - Fakultas Ilmu Komputer',
                'email' => 'sarah.nurhaliza@ui.ac.id',
                'phone' => '081298765432',
                'password' => $defaultPassword,
                'role' => 'mahasiswa'
            ],
            [
                'nama' => 'Ahmad Rian',
                'nim' => '2024080789',
                'prodi' => 'Teknik Elektro - Fakultas Teknik',
                'email' => 'ahmad.rian@ui.ac.id',
                'phone' => '081345678901',
                'password' => $defaultPassword,
                'role' => 'mahasiswa'
            ],
            [
                'nama' => 'Petugas Keamanan (Pos Rektorat)',
                'nim' => 'ADM-001',
                'prodi' => 'Biro Keamanan & Ketertiban Kampus',
                'email' => 'admin@ui.ac.id',
                'phone' => '081122334455',
                'password' => $adminPassword,
                'role' => 'admin'
            ]
        ];

        $stmtUser = $pdo->prepare("INSERT INTO users (nama, nim, prodi, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($users as $u) {
            $stmtUser->execute([$u['nama'], $u['nim'], $u['prodi'], $u['email'], $u['phone'], $u['password'], $u['role']]);
        }

        // 2. Seed Categories
        $categories = [
            ['name' => 'Elektronik & Gadget', 'slug' => 'elektronik', 'icon' => 'smartphone'],
            ['name' => 'Dompet & Kartu Identitas', 'slug' => 'dompet', 'icon' => 'wallet'],
            ['name' => 'Kunci Kendaraan', 'slug' => 'kunci', 'icon' => 'key-round'],
            ['name' => 'Tas & Pakaian', 'slug' => 'tas', 'icon' => 'backpack'],
            ['name' => 'Aksesoris & Botol Minum', 'slug' => 'aksesoris', 'icon' => 'sparkles'],
            ['name' => 'Lain-lain', 'slug' => 'lainnya', 'icon' => 'package']
        ];
        $stmtCat = $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $stmtCat->execute([$cat['name'], $cat['slug'], $cat['icon']]);
        }

        // 3. Seed Locations
        $locations = [
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
        $stmtLoc = $pdo->prepare("INSERT INTO locations (name) VALUES (?)");
        foreach ($locations as $loc) {
            $stmtLoc->execute([$loc]);
        }

        // 4. Seed Items
        $items = [
            [
                'kode' => 'TRX-88219',
                'nama' => 'Tumbler Hydro Flask 32oz Biru',
                'kategori' => 'Aksesoris & Botol Minum',
                'kategori_slug' => 'aksesoris',
                'status' => 'Ditemukan',
                'jenis_laporan' => 'temuan',
                'lokasi' => 'Ruang Kelas 304, Gedung Kuliah Bersama (GKB)',
                'lokasi_singkat' => 'R.304 Gedung Kuliah Bersama',
                'waktu_kejadian' => '16 November 2026, 09:15 WIB',
                'deskripsi' => 'Ditemukan tumbler stainless warna biru dongker dalam kondisi baik. Terdapat stiker fakultas di bagian bawah botol. Tidak ada tanda kepemilikan nama tertulis.',
                'ciri_umum' => 'Tumbler warna biru navy doff ukuran 32oz dengan tutup fleksibel hitam.',
                'ciri_khusus' => 'Ada stiker bulat FIK di dasar botol dan sedikit baret halus di dekat leher tumbler.',
                'tempat_simpan' => 'Pos Keamanan Rektorat Gedung A (Lt. 1)',
                'pelapor_id' => 4,
                'pelapor_nama' => 'Petugas Keamanan Kampus (Pos A)',
                'pelapor_tipe' => 'Petugas',
                'icon' => 'smartphone',
                'warna_placeholder' => '#dbeafe'
            ],
            [
                'kode' => 'TRX-88218',
                'nama' => 'Dompet Kulit Cokelat & KTM',
                'kategori' => 'Dompet & Kartu Identitas',
                'kategori_slug' => 'dompet',
                'status' => 'Hilang',
                'jenis_laporan' => 'kehilangan',
                'lokasi' => 'Perpustakaan Pusat Lantai 2',
                'lokasi_singkat' => 'Perpustakaan Pusat Lt. 2',
                'waktu_kejadian' => '17 November 2026, 10:30 WIB',
                'deskripsi' => 'Dompet lipat kulit warna cokelat tua tertinggal di meja baca dekat rak buku ilmu sosial. Di dalamnya terdapat KTM dan kartu penting lainnya.',
                'ciri_umum' => 'Dompet kulit lipat dua warna cokelat gelap merk Bogesi.',
                'ciri_khusus' => 'Di slot tersembunyi tersimpan struk ATM BNI dan foto polaroid keluarga.',
                'tempat_simpan' => 'Dalam pencarian pelapor',
                'pelapor_id' => 1,
                'pelapor_nama' => 'Budi Pratama (Mahasiswa)',
                'pelapor_tipe' => 'Mahasiswa',
                'icon' => 'wallet',
                'warna_placeholder' => '#fee2e2'
            ],
            [
                'kode' => 'TRX-88215',
                'nama' => 'Kunci Motor Honda Vario 160',
                'kategori' => 'Kunci Kendaraan',
                'kategori_slug' => 'kunci',
                'status' => 'Ditemukan',
                'jenis_laporan' => 'temuan',
                'lokasi' => 'Parkiran Sepeda Motor Barat Blok C',
                'lokasi_singkat' => 'Parkiran Sepeda Motor Barat',
                'waktu_kejadian' => '14 November 2026, 17:00 WIB',
                'deskripsi' => 'Kunci kontak smartkey Honda Vario tergantung di tiang pembatas parkiran motor.',
                'ciri_umum' => 'Remote keyless Honda dengan cover silikon merah dan gantungan tali kain.',
                'ciri_khusus' => 'Gantungan kunci bertuliskan nama angkatan 2023.',
                'tempat_simpan' => 'Pos Satpam Parkiran Barat',
                'pelapor_id' => 4,
                'pelapor_nama' => 'Petugas Parkir',
                'pelapor_tipe' => 'Petugas',
                'icon' => 'key-round',
                'warna_placeholder' => '#d1fae5'
            ],
            [
                'kode' => 'TRX-88210',
                'nama' => 'Jaket Hoodie Abu-abu Uniqlo',
                'kategori' => 'Tas & Pakaian',
                'kategori_slug' => 'tas',
                'status' => 'Hilang',
                'jenis_laporan' => 'kehilangan',
                'lokasi' => 'Kantin Pusat Rumpun Ilmu Lt. 1',
                'lokasi_singkat' => 'Kantin RIK Lt. 1',
                'waktu_kejadian' => '13 November 2026, 12:15 WIB',
                'deskripsi' => 'Jaket hoodie warna abu-abu misty size L tertinggal di sandaran kursi kantin saat jam makan siang.',
                'ciri_umum' => 'Hoodie zipper polos abu-abu merk Uniqlo size L.',
                'ciri_khusus' => 'Tali hoodie sebelah kanan sedikit terurai ujungnya.',
                'tempat_simpan' => 'Dalam pencarian pelapor',
                'pelapor_id' => 3,
                'pelapor_nama' => 'Ahmad Rian (Mahasiswa)',
                'pelapor_tipe' => 'Mahasiswa',
                'icon' => 'backpack',
                'warna_placeholder' => '#fee2e2'
            ],
            [
                'kode' => 'TRX-88204',
                'nama' => 'Laptop Asus Vivobook 14',
                'kategori' => 'Elektronik & Gadget',
                'kategori_slug' => 'elektronik',
                'status' => 'Ditemukan',
                'jenis_laporan' => 'temuan',
                'lokasi' => 'Laboratorium Komputer Gedung B',
                'lokasi_singkat' => 'Lab Komputer Gedung B',
                'waktu_kejadian' => '12 November 2026, 16:30 WIB',
                'deskripsi' => 'Laptop Asus warna silver lengkap dengan sleeve bag hitam ditemukan di meja PC Lab 2.',
                'ciri_umum' => 'Laptop 14 inch warna silver abu dengan charger asli.',
                'ciri_khusus' => 'Ada stiker Github dan sticker logo React di cover belakang monitor.',
                'tempat_simpan' => 'Ruang Teknisi Lab Komputer Lt. 2',
                'pelapor_id' => 4,
                'pelapor_nama' => 'Laboran Komputer',
                'pelapor_tipe' => 'Petugas',
                'icon' => 'laptop',
                'warna_placeholder' => '#d1fae5'
            ],
            [
                'kode' => 'TRX-88198',
                'nama' => 'Payung Lipat Otomatis Biru',
                'kategori' => 'Aksesoris & Botol Minum',
                'kategori_slug' => 'aksesoris',
                'status' => 'Dikembalikan',
                'jenis_laporan' => 'temuan',
                'lokasi' => 'Masjid Ukhuwah Islamiyah / Lobi Rektorat',
                'lokasi_singkat' => 'Lobi Utama Gedung Rektorat',
                'waktu_kejadian' => '10 November 2026, 13:00 WIB',
                'deskripsi' => 'Payung lipat tombol otomatis warna navy blue, sudah berhasil diambil oleh pemilik aslinya setelah verifikasi tanda pengenal.',
                'ciri_umum' => 'Payung lipat 3 warna biru gelap pegangan kayu.',
                'ciri_khusus' => 'Ada inisial nama kecil di tali pengikat.',
                'tempat_simpan' => 'Sudah diserahkan ke pemilik',
                'pelapor_id' => 4,
                'pelapor_nama' => 'Petugas Keamanan Lobi',
                'pelapor_tipe' => 'Petugas',
                'icon' => 'shirt',
                'warna_placeholder' => '#e0e7ff'
            ],
            [
                'kode' => 'TRX-88190',
                'nama' => 'Kacamata Minus Frame Hitam',
                'kategori' => 'Aksesoris & Botol Minum',
                'kategori_slug' => 'aksesoris',
                'status' => 'Dalam Verifikasi',
                'jenis_laporan' => 'temuan',
                'lokasi' => 'Gazebo Rumpun Soshum',
                'lokasi_singkat' => 'Gazebo Rumpun Soshum',
                'waktu_kejadian' => '09 November 2026, 15:45 WIB',
                'deskripsi' => 'Kacamata baca frame kotak warna hitam doff lengkap dengan kotak kacamata abu-abu.',
                'ciri_umum' => 'Frame kacamata plastik hitam ringan.',
                'ciri_khusus' => 'Engsel kanan agak sedikit longgar.',
                'tempat_simpan' => 'Pos Satpam Dekanat Soshum',
                'pelapor_id' => 2,
                'pelapor_nama' => 'Mahasiswa Soshum',
                'pelapor_tipe' => 'Mahasiswa',
                'icon' => 'wallet',
                'warna_placeholder' => '#fef3c7'
            ],
            [
                'kode' => 'TRX-88185',
                'nama' => 'Flashdisk Sandisk 64GB',
                'kategori' => 'Elektronik & Gadget',
                'kategori_slug' => 'elektronik',
                'status' => 'Hilang',
                'jenis_laporan' => 'kehilangan',
                'lokasi' => 'Perpustakaan Pusat Lantai 1',
                'lokasi_singkat' => 'Perpustakaan Pusat Lt. 1',
                'waktu_kejadian' => '08 November 2026, 11:20 WIB',
                'deskripsi' => 'Flashdisk USB 3.0 warna merah hitam tertancap di komputer OPAC perpustakaan.',
                'ciri_umum' => 'Sandisk Cruzer Blade 64GB bodi hitam strip merah.',
                'ciri_khusus' => 'Berisi folder skripsi atas nama Sarah N.',
                'tempat_simpan' => 'Dalam pencarian pelapor',
                'pelapor_id' => 2,
                'pelapor_nama' => 'Sarah Nurhaliza',
                'pelapor_tipe' => 'Mahasiswa',
                'icon' => 'smartphone',
                'warna_placeholder' => '#fee2e2'
            ],
            [
                'kode' => 'TRX-88180',
                'nama' => 'Charger Laptop Lenovo 65W',
                'kategori' => 'Elektronik & Gadget',
                'kategori_slug' => 'elektronik',
                'status' => 'Ditemukan',
                'jenis_laporan' => 'temuan',
                'lokasi' => 'Halte Bus Kuning Teknik',
                'lokasi_singkat' => 'Halte Bus Kuning Teknik',
                'waktu_kejadian' => '07 November 2026, 09:10 WIB',
                'deskripsi' => 'Adaptor charger USB Type-C Lenovo 65W original tertinggal di bangku tunggu halte.',
                'ciri_umum' => 'Charger kotak warna hitam kabel USB-C tebal.',
                'ciri_khusus' => 'Ada isolasi hitam pelindung di ujung kabel dekat port.',
                'tempat_simpan' => 'Pos Satpam Gerbang Utama Teknik',
                'pelapor_id' => 4,
                'pelapor_nama' => 'Driver Bus Kampus',
                'pelapor_tipe' => 'Petugas',
                'icon' => 'laptop',
                'warna_placeholder' => '#d1fae5'
            ]
        ];

        $stmtItem = $pdo->prepare("INSERT INTO items (
            kode, nama, kategori, kategori_slug, status, jenis_laporan, lokasi, lokasi_singkat,
            waktu_kejadian, deskripsi, ciri_umum, ciri_khusus, tempat_simpan, pelapor_id,
            pelapor_nama, pelapor_tipe, icon, warna_placeholder
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($items as $it) {
            $stmtItem->execute([
                $it['kode'], $it['nama'], $it['kategori'], $it['kategori_slug'], $it['status'],
                $it['jenis_laporan'], $it['lokasi'], $it['lokasi_singkat'], $it['waktu_kejadian'],
                $it['deskripsi'], $it['ciri_umum'], $it['ciri_khusus'], $it['tempat_simpan'],
                $it['pelapor_id'], $it['pelapor_nama'], $it['pelapor_tipe'], $it['icon'],
                $it['warna_placeholder']
            ]);
        }

        // 5. Seed Claims
        $claims = [
            [
                'kode_klaim' => 'KLM-2026-8821',
                'item_id' => 1,
                'user_id' => 1,
                'nama_pengklaim' => 'Budi Pratama',
                'email_pengklaim' => 'budi.pratama@ui.ac.id',
                'phone_pengklaim' => '081234567890',
                'jawaban_verifikasi' => 'Di bagian bawah ada stiker bulat Fakultas Ilmu Komputer dan sedikit penyok di sudut tutup.',
                'status' => 'Dalam Verifikasi'
            ]
        ];
        $stmtClaim = $pdo->prepare("INSERT INTO claims (kode_klaim, item_id, user_id, nama_pengklaim, email_pengklaim, phone_pengklaim, jawaban_verifikasi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($claims as $c) {
            $stmtClaim->execute([
                $c['kode_klaim'], $c['item_id'], $c['user_id'], $c['nama_pengklaim'],
                $c['email_pengklaim'], $c['phone_pengklaim'], $c['jawaban_verifikasi'], $c['status']
            ]);
        }

        // 6. Seed Notifications
        $notifications = [
            [
                'user_id' => 1,
                'judul' => 'Laporan Barang Hilang Telah Diverifikasi Petugas!',
                'pesan' => 'Laporan Anda mengenai "Dompet Kulit Cokelat & KTM" telah disetujui dan kini aktif di daftar pencarian kampus.',
                'icon' => 'bell',
                'is_unread' => 1
            ],
            [
                'user_id' => 1,
                'judul' => 'Potensi Temuan: Ada Barang Baru yang Cocok!',
                'pesan' => 'Seorang mahasiswa baru saja melaporkan temuan "Tumbler Biru di GKB R.304". Periksa detailnya sekarang.',
                'icon' => 'search',
                'is_unread' => 1
            ],
            [
                'user_id' => 1,
                'judul' => 'Klaim Kepemilikan Barang Telah Disetujui!',
                'pesan' => 'Klaim Anda untuk "Kunci Motor Honda Vario" disetujui. Silakan ambil barang di Pos Satpam Barat.',
                'icon' => 'check-circle',
                'is_unread' => 0
            ],
            [
                'user_id' => 1,
                'judul' => 'Laporan Selesai: Barang Berhasil Dikembalikan',
                'pesan' => 'Status laporan "Payung Lipat Otomatis" telah diperbarui menjadi Selesai / Dikembalikan ke pemilik.',
                'icon' => 'package-check',
                'is_unread' => 0
            ],
            [
                'user_id' => 1,
                'judul' => 'Tips Keamanan: Beri Tanda Khusus pada Barang Anda',
                'pesan' => 'Selalu beri label nama atau gantungan unik pada barang berharga untuk mempermudah identifikasi penemuan.',
                'icon' => 'shield',
                'is_unread' => 0
            ]
        ];
        $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread) VALUES (?, ?, ?, ?, ?)");
        foreach ($notifications as $n) {
            $stmtNotif->execute([$n['user_id'], $n['judul'], $n['pesan'], $n['icon'], $n['is_unread']]);
        }
    }
}

// Global Helper Functions for DB Queries
function get_db(): PDO {
    return Database::getConnection();
}

function db_fetch_all(string $sql, array $params = []): array {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_fetch_one(string $sql, array $params = []): ?array {
    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_execute(string $sql, array $params = []): bool {
    $stmt = get_db()->prepare($sql);
    return $stmt->execute($params);
}

function db_last_insert_id(): string {
    return get_db()->lastInsertId();
}
