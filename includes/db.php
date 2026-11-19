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
