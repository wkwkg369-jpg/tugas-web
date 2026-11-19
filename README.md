# SIP-Barang Kampus (Sistem Informasi Pelaporan & Pencarian Barang Hilang)

Aplikasi web fullstack Lost and Found untuk lingkungan kampus yang modern, aman, dan responsif. Didesain 100% mengikuti **Figma Design System** (`ui/desgin sistem` dan `ui/design halaman mahasiswa`), dilengkapi arsitektur backend PHP modular, sistem autentikasi, manajemen session, pelaporan interaktif dengan upload gambar, proses klaim berjenjang, dan portal verifikasi admin/petugas keamanan.

---

## 🌟 Fitur Utama Sistem

### 1. Sisi Mahasiswa (Publik & Civitas Akademika)
- **Beranda (`index.php`)**: Hero search cepat, tombol aksi lapor hilang/temuan, katalog barang terbaru dilaporkan, dan 4 langkah alur sistem.
- **Pencarian Katalog (`pencarian.php`)**: Real-time search by keyword, filter kategori barang, filter status laporan, filter lokasi kampus, dan sorting terbaru.
- **Detail Barang (`detail.php`)**: Foto/ikon barang, metadata waktu & lokasi, identitas pelapor terverifikasi, ciri umum barang, tempat penyimpanan fisik barang, peringatan keamanan akademik, dan pemicu klaim barang.
- **Pengajuan Klaim Kepemilikan (`modal-klaim.php` & `klaim-berhasil.php`)**: Modal popup untuk menjawab pertanyaan ciri rahasia, upload bukti pendukung (struk/foto lama), generate ID klaim otomatis (`#KLM-2026-XXXX`), dan status verifikasi langsung.
- **Lapor Kehilangan (`lapor-kehilangan.php`)**: Form pelaporan barang hilang dilengkapi ciri umum (publik), ciri khusus rahasia (private/admin only), lokasi terakhir, tanggal kejadian, dan upload foto barang (`assets/uploads/`).
- **Lapor Temuan (`lapor-temuan.php`)**: Form pelaporan barang temuan civitas kampus, kategori barang, upload foto, lokasi penemuan, dan pilihan lokasi penitipan pos keamanan.
- **Riwayat Pelaporan & Klaim (`riwayat.php`)**: Tab interaktif "Laporan Saya" dan "Klaim Saya", nomor antrean transaksi, status badge warna resmi, dan link langsung ke detail.
- **Pusat Notifikasi (`notifikasi.php`)**: Riwayat notifikasi status pelaporan dan verifikasi klaim secara real-time, badge unread di navbar, dan fitur "Tandai semua dibaca".
- **Autentikasi & Akun**:
  - Masuk (`masuk.php`): Login dengan email kampus / NIM dan password, remember me, show/hide password, dan tombol shortcut Akun Demo 1-klik.
  - Daftar (`daftar.php`): Registrasi mahasiswa baru (Nama, NIM, Program Studi, Email Kampus, WhatsApp, Password).
  - Verifikasi OTP (`verifikasi-otp.php`): Input 6-digit OTP interaktif, countdown timer, dan state switcher desain Figma (Default, Salah, Berhasil).
  - Lupa Sandi (`lupa-sandi.php`): Reset kata sandi melalui kode OTP.
  - Keluar (`keluar.php`): Logout aman dan pembersihan session.

### 2. Sisi Admin & Petugas Keamanan (`/admin/`)
- **Dashboard Utama (`admin/index.php`)**: Metrik real-time (Total Hilang, Ditemukan, Dalam Verifikasi, Selesai Dikembalikan), visualisasi grafik per bulan, dan antrean "Perlu Diproses Segera".
- **Verifikasi Laporan (`admin/verifikasi-laporan.php`)**: Tinjau laporan masuk, validasi keaslian, setujui/publikasikan ke katalog, atau tolak/tutup laporan palsu.
- **Verifikasi Klaim Kepemilikan (`admin/verifikasi-klaim.php`)**: Tampilan perbandingan berdampingan (*side-by-side*) antara data rahasia penemu vs jawaban & bukti struk/foto pengklaim, serta form keputusan petugas.
- **Catat Penyerahan Barang (`admin/catat-penyerahan.php`)**: Pencatatan resmi serah terima barang kepada pemilik sah lengkap dengan nomor Berita Acara (`#BA-YYYYMMDD-XXX`) yang siap dicetak.
- **Kelola Seluruh Laporan (`admin/kelola-laporan.php`)**: Master tabel inventaris, pencarian cepat, filter multi-kriteria, pengubahan status cepat, dan hapus laporan.
- **Rekap & Arsip Laporan (`admin/rekap-laporan.php`)**: Statistik distribusi kategori, tingkat keberhasilan pengembalian barang (*return rate*), dan tombol cetak laporan resmi / ekspor PDF.

---

## 🗄️ Arsitektur Database (Dual-Engine XAMPP)

Aplikasi dilengkapi modul database pintar di `includes/db.php`:
1. **Engine Utama: MySQL / MariaDB (XAMPP)**
   - Host: `127.0.0.1:3306`
   - User: `root`, Password: `(kosong)`
   - Nama Database: `sip_barang_kampus`
   - Skrip SQL lengkap tersedia di file: [sip_barang_kampus.sql](file:///D:/project-RPL/tugas-web/sip_barang_kampus.sql).
2. **Engine Fallback Otomatis: SQLite (`includes/sip_barang.sqlite`)**
   - Jika MySQL belum dinyalakan di XAMPP Control Panel, sistem otomatis beralih ke SQLite lokal secara *seamless*.
   - **Menjamin web 100% langsung berjalan tanpa error 500 walau MySQL belum di-start!**

### Tabel Database:
- `users`: Data pengguna (id, nama, nim, prodi, email, phone, password, role ['mahasiswa', 'admin']).
- `categories`: Kategori barang dan ikon.
- `locations`: Daftar titik lokasi kampus.
- `items`: Master data laporan barang hilang dan temuan (termasuk ciri khusus private).
- `claims`: Pengajuan klaim kepemilikan oleh mahasiswa.
- `notifications`: Notifikasi aktivitas untuk pengguna.
- `otp_codes`: Kode OTP untuk registrasi dan lupa kata sandi.

---

## 🔑 Akun Awal

Kredensial bawaan tidak lagi ditulis di dokumen maupun ditampilkan di halaman masuk.
Saat instalasi baru, kata sandi admin diambil dari variabel lingkungan
`SIP_ADMIN_PASSWORD`; bila tidak diisi, sistem membuat kata sandi acak yang tidak
diketahui siapa pun. Minta admin mengganti kata sandi setelah masuk pertama kali.

> Untuk produksi, wajib ganti kata sandi akun contoh dan aktifkan HTTPS.

---

## 🚀 Cara Menjalankan Project

### Cara 1: Menggunakan Script Cepat (Rekomendasi)
1. Buka folder `tugas-web`.
2. Klik ganda file [serve.bat](file:///D:/project-RPL/tugas-web/serve.bat).
3. Browser akan otomatis terbuka ke `http://localhost:8000`.

### Cara 2: Menggunakan Apache XAMPP (`htdocs`)
1. Salin folder `tugas-web` ke `C:\xampp\htdocs\tugas-web`.
2. Buka **XAMPP Control Panel**, klik **Start** pada modul **Apache** dan **MySQL**.
3. (Opsional) Buka `http://localhost/phpmyadmin`, buat database `sip_barang_kampus`, lalu import [sip_barang_kampus.sql](file:///D:/project-RPL/tugas-web/sip_barang_kampus.sql).
4. Akses melalui browser: `http://localhost/tugas-web/`

---

## 📂 Struktur Direktori Proyek

```
tugas-web/
├── admin/                     # Portal Admin & Petugas Keamanan
│   ├── includes/
│   │   ├── admin_header.php
│   │   ├── admin_sidebar.php
│   │   └── admin_footer.php
│   ├── index.php              # Dashboard Admin & Metrik
│   ├── verifikasi-laporan.php # Verifikasi laporan masuk
│   ├── verifikasi-klaim.php   # Side-by-side validasi klaim & bukti
│   ├── catat-penyerahan.php   # Berita acara serah terima barang
│   ├── kelola-laporan.php     # Manajemen inventaris seluruh barang
│   └── rekap-laporan.php      # Laporan statistik & cetak arsip
├── assets/
│   ├── css/style.css          # Design system tokens, warna & komponen
│   ├── js/main.js             # Skrip interaksi (filter, modal, OTP, upload)
│   ├── uploads/               # Direktori penyimpanan file foto barang & bukti klaim
│   └── vendor/
│       ├── tailwind.js        # Tailwind CSS lokal (100% offline-ready)
│       └── lucide.min.js      # Lucide Icons lokal
├── includes/
│   ├── auth.php               # Manajemen session, login, register, role check
│   ├── data.php               # Data helper functions & query wrapper
│   ├── db.php                 # Dual-driver database (MySQL XAMPP + SQLite fallback)
│   ├── header.php             # Header global mahasiswa
│   ├── navbar.php             # Navigasi atas responsif & status login dinamis
│   └── footer.php             # Footer global & toast notifikasi
├── sessions/                  # Direktori session lokal yang aman
├── ui/                        # Desain Figma & Design System
├── daftar.php                 # Form pendaftaran akun mahasiswa
├── detail.php                 # Halaman detail barang & pemicu klaim
├── index.php                  # Halaman beranda utama
├── keluar.php                 # Logout handler
├── klaim-berhasil.php         # Konfirmasi klaim diajukan
├── lapor-kehilangan.php       # Form lapor barang hilang (dengan upload foto)
├── lapor-temuan.php           # Form lapor barang temuan
├── lupa-sandi.php             # Reset kata sandi
├── masuk.php                  # Halaman login mahasiswa & admin
├── modal-klaim.php            # Standalone modal preview klaim barang
├── notifikasi.php             # Pusat notifikasi pengguna
├── pencarian.php              # Katalog & pencarian barang
├── riwayat.php                # Tab riwayat laporan & klaim saya
├── serve.bat                  # Skrip 1-klik menjalankan PHP server XAMPP
├── sip_barang_kampus.sql      # Skema database MySQL & data awal
└── verifikasi-otp.php         # Halaman verifikasi kode OTP
```
