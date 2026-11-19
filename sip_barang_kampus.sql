-- ========================================================
-- SIP-Barang Kampus (Lost & Found Web Application)
-- Database Schema & Seed Data for MySQL / MariaDB (XAMPP)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `sip_barang_kampus` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sip_barang_kampus`;

-- 1. Tabel Users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
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

-- 2. Tabel Categories
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'tag'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Locations
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Items (Laporan Barang)
DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
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

-- 5. Tabel Claims (Klaim Kepemilikan)
DROP TABLE IF EXISTS `claims`;
CREATE TABLE `claims` (
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

-- 6. Tabel Notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT 1,
  `judul` VARCHAR(255) NOT NULL,
  `pesan` TEXT NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bell',
  `is_unread` TINYINT(1) DEFAULT 1,
  `link` VARCHAR(255) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabel OTP Codes
DROP TABLE IF EXISTS `otp_codes`;
CREATE TABLE `otp_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `type` VARCHAR(50) DEFAULT 'register',
  `is_used` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- SEED DATA AWAL
-- ========================================================

-- Akun Default:
-- Mahasiswa: budi.pratama@ui.ac.id / password123 (NIM: 2024080123)
-- Admin: admin@ui.ac.id / admin123
INSERT INTO `users` (`id`, `nama`, `nim`, `prodi`, `email`, `phone`, `password`, `role`) VALUES
(1, 'Budi Pratama', '2024080123', 'Sistem Informasi - Fakultas Ilmu Komputer', 'budi.pratama@ui.ac.id', '081234567890', '$2y$10$w3UjH8/k0oAicY3r0JvHVOc7JmUj7/U7G6q5Vp48iYJ6H.l3z4f5q', 'mahasiswa'),
(2, 'Sarah Nurhaliza', '2024080456', 'Ilmu Komputer - Fakultas Ilmu Komputer', 'sarah.nurhaliza@ui.ac.id', '081298765432', '$2y$10$w3UjH8/k0oAicY3r0JvHVOc7JmUj7/U7G6q5Vp48iYJ6H.l3z4f5q', 'mahasiswa'),
(3, 'Ahmad Rian', '2024080789', 'Teknik Elektro - Fakultas Teknik', 'ahmad.rian@ui.ac.id', '081345678901', '$2y$10$w3UjH8/k0oAicY3r0JvHVOc7JmUj7/U7G6q5Vp48iYJ6H.l3z4f5q', 'mahasiswa'),
(4, 'Petugas Keamanan (Pos Rektorat)', 'ADM-001', 'Biro Keamanan & Ketertiban Kampus', 'admin@ui.ac.id', '081122334455', '$2y$10$tZ2E7iFzZ6c5F8q0KkRj5edG/gVj3n0vI08O7aCq21s7y5Q0mO/vS', 'admin');

-- Kategori
INSERT INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Elektronik & Gadget', 'elektronik', 'smartphone'),
(2, 'Dompet & Kartu Identitas', 'dompet', 'wallet'),
(3, 'Kunci Kendaraan', 'kunci', 'key-round'),
(4, 'Tas & Pakaian', 'tas', 'backpack'),
(5, 'Aksesoris & Botol Minum', 'aksesoris', 'sparkles'),
(6, 'Lain-lain', 'lainnya', 'package');

-- Lokasi
INSERT INTO `locations` (`id`, `name`) VALUES
(1, 'Perpustakaan Pusat'),
(2, 'Gedung Kuliah Bersama (GKB)'),
(3, 'Parkiran Sepeda Motor Barat'),
(4, 'Kantin Pusat Rumpun Ilmu'),
(5, 'Laboratorium Komputer'),
(6, 'Gedung Rektorat'),
(7, 'Masjid Kampus'),
(8, 'Halte Bus Kampus'),
(9, 'Gazebo & Area Terbuka');

-- Barang / Laporan
INSERT INTO `items` (`id`, `kode`, `nama`, `kategori`, `kategori_slug`, `status`, `jenis_laporan`, `lokasi`, `lokasi_singkat`, `waktu_kejadian`, `deskripsi`, `ciri_umum`, `ciri_khusus`, `tempat_simpan`, `pelapor_id`, `pelapor_nama`, `pelapor_tipe`, `icon`, `warna_placeholder`) VALUES
(1, 'TRX-88219', 'Tumbler Hydro Flask 32oz Biru', 'Aksesoris & Botol Minum', 'aksesoris', 'Ditemukan', 'temuan', 'Ruang Kelas 304, Gedung Kuliah Bersama (GKB)', 'R.304 Gedung Kuliah Bersama', '16 November 2026, 09:15 WIB', 'Ditemukan tumbler stainless warna biru dongker dalam kondisi baik. Terdapat stiker fakultas di bagian bawah botol. Tidak ada tanda kepemilikan nama tertulis.', 'Tumbler warna biru navy doff ukuran 32oz dengan tutup fleksibel hitam.', 'Ada stiker bulat FIK di dasar botol dan sedikit baret halus di dekat leher tumbler.', 'Pos Keamanan Rektorat Gedung A (Lt. 1)', 4, 'Petugas Keamanan Kampus (Pos A)', 'Petugas', 'smartphone', '#dbeafe'),
(2, 'TRX-88218', 'Dompet Kulit Cokelat & KTM', 'Dompet & Kartu Identitas', 'dompet', 'Hilang', 'kehilangan', 'Perpustakaan Pusat Lantai 2', 'Perpustakaan Pusat Lt. 2', '17 November 2026, 10:30 WIB', 'Dompet lipat kulit warna cokelat tua tertinggal di meja baca dekat rak buku ilmu sosial. Di dalamnya terdapat KTM dan kartu penting lainnya.', 'Dompet kulit lipat dua warna cokelat gelap merk Bogesi.', 'Di slot tersembunyi tersimpan struk ATM BNI dan foto polaroid keluarga.', 'Dalam pencarian pelapor', 1, 'Budi Pratama (Mahasiswa)', 'Mahasiswa', 'wallet', '#fee2e2'),
(3, 'TRX-88215', 'Kunci Motor Honda Vario 160', 'Kunci Kendaraan', 'kunci', 'Ditemukan', 'temuan', 'Parkiran Sepeda Motor Barat Blok C', 'Parkiran Sepeda Motor Barat', '14 November 2026, 17:00 WIB', 'Kunci kontak smartkey Honda Vario tergantung di tiang pembatas parkiran motor.', 'Remote keyless Honda dengan cover silikon merah dan gantungan tali kain.', 'Gantungan kunci bertuliskan nama angkatan 2023.', 'Pos Satpam Parkiran Barat', 4, 'Petugas Parkir', 'Petugas', 'key-round', '#d1fae5'),
(4, 'TRX-88210', 'Jaket Hoodie Abu-abu Uniqlo', 'Tas & Pakaian', 'tas', 'Hilang', 'kehilangan', 'Kantin Pusat Rumpun Ilmu Lt. 1', 'Kantin RIK Lt. 1', '13 November 2026, 12:15 WIB', 'Jaket hoodie warna abu-abu misty size L tertinggal di sandaran kursi kantin saat jam makan siang.', 'Hoodie zipper polos abu-abu merk Uniqlo size L.', 'Tali hoodie sebelah kanan sedikit terurai ujungnya.', 'Dalam pencarian pelapor', 3, 'Ahmad Rian (Mahasiswa)', 'Mahasiswa', 'backpack', '#fee2e2'),
(5, 'TRX-88204', 'Laptop Asus Vivobook 14', 'Elektronik & Gadget', 'elektronik', 'Ditemukan', 'temuan', 'Laboratorium Komputer Gedung B', 'Lab Komputer Gedung B', '12 November 2026, 16:30 WIB', 'Laptop Asus warna silver lengkap dengan sleeve bag hitam ditemukan di meja PC Lab 2.', 'Laptop 14 inch warna silver abu dengan charger asli.', 'Ada stiker Github dan sticker logo React di cover belakang monitor.', 'Ruang Teknisi Lab Komputer Lt. 2', 4, 'Laboran Komputer', 'Petugas', 'laptop', '#d1fae5'),
(6, 'TRX-88198', 'Payung Lipat Otomatis Biru', 'Aksesoris & Botol Minum', 'aksesoris', 'Dikembalikan', 'temuan', 'Masjid Ukhuwah Islamiyah / Lobi Rektorat', 'Lobi Utama Gedung Rektorat', '10 November 2026, 13:00 WIB', 'Payung lipat tombol otomatis warna navy blue, sudah berhasil diambil oleh pemilik aslinya setelah verifikasi tanda pengenal.', 'Payung lipat 3 warna biru gelap pegangan kayu.', 'Ada inisial nama kecil di tali pengikat.', 'Sudah diserahkan ke pemilik', 4, 'Petugas Keamanan Lobi', 'Petugas', 'shirt', '#e0e7ff'),
(7, 'TRX-88190', 'Kacamata Minus Frame Hitam', 'Aksesoris & Botol Minum', 'aksesoris', 'Dalam Verifikasi', 'temuan', 'Gazebo Rumpun Soshum', 'Gazebo Rumpun Soshum', '09 November 2026, 15:45 WIB', 'Kacamata baca frame kotak warna hitam doff lengkap dengan kotak kacamata abu-abu.', 'Frame kacamata plastik hitam ringan.', 'Engsel kanan agak sedikit longgar.', 'Pos Satpam Dekanat Soshum', 2, 'Mahasiswa Soshum', 'Mahasiswa', 'wallet', '#fef3c7'),
(8, 'TRX-88185', 'Flashdisk Sandisk 64GB', 'Elektronik & Gadget', 'elektronik', 'Hilang', 'kehilangan', 'Perpustakaan Pusat Lantai 1', 'Perpustakaan Pusat Lt. 1', '08 November 2026, 11:20 WIB', 'Flashdisk USB 3.0 warna merah hitam tertancap di komputer OPAC perpustakaan.', 'Sandisk Cruzer Blade 64GB bodi hitam strip merah.', 'Berisi folder skripsi atas nama Sarah N.', 'Dalam pencarian pelapor', 2, 'Sarah Nurhaliza', 'Mahasiswa', 'smartphone', '#fee2e2'),
(9, 'TRX-88180', 'Charger Laptop Lenovo 65W', 'Elektronik & Gadget', 'elektronik', 'Ditemukan', 'temuan', 'Halte Bus Kuning Teknik', 'Halte Bus Kuning Teknik', '07 November 2026, 09:10 WIB', 'Adaptor charger USB Type-C Lenovo 65W original tertinggal di bangku tunggu halte.', 'Charger kotak warna hitam kabel USB-C tebal.', 'Ada isolasi hitam pelindung di ujung kabel dekat port.', 'Pos Satpam Gerbang Utama Teknik', 4, 'Driver Bus Kampus', 'Petugas', 'laptop', '#d1fae5');

-- Klaim
INSERT INTO `claims` (`id`, `kode_klaim`, `item_id`, `user_id`, `nama_pengklaim`, `email_pengklaim`, `phone_pengklaim`, `jawaban_verifikasi`, `status`) VALUES
(1, 'KLM-2026-8821', 1, 1, 'Budi Pratama', 'budi.pratama@ui.ac.id', '081234567890', 'Di bagian bawah ada stiker bulat Fakultas Ilmu Komputer dan sedikit penyok di sudut tutup.', 'Dalam Verifikasi');

-- Notifikasi
INSERT INTO `notifications` (`id`, `user_id`, `judul`, `pesan`, `icon`, `is_unread`) VALUES
(1, 1, 'Laporan Barang Hilang Telah Diverifikasi Petugas!', 'Laporan Anda mengenai "Dompet Kulit Cokelat & KTM" telah disetujui dan kini aktif di daftar pencarian kampus.', 'bell', 1),
(2, 1, 'Potensi Temuan: Ada Barang Baru yang Cocok!', 'Seorang mahasiswa baru saja melaporkan temuan "Tumbler Biru di GKB R.304". Periksa detailnya sekarang.', 'search', 1),
(3, 1, 'Klaim Kepemilikan Barang Telah Disetujui!', 'Klaim Anda untuk "Kunci Motor Honda Vario" disetujui. Silakan ambil barang di Pos Satpam Barat.', 'check-circle', 0),
(4, 1, 'Laporan Selesai: Barang Berhasil Dikembalikan', 'Status laporan "Payung Lipat Otomatis" telah diperbarui menjadi Selesai / Dikembalikan ke pemilik.', 'package-check', 0),
(5, 1, 'Tips Keamanan: Beri Tanda Khusus pada Barang Anda', 'Selalu beri label nama atau gantungan unik pada barang berharga untuk mempermudah identifikasi penemuan.', 'shield', 0);
