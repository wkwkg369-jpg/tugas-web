# SIP-Barang Kampus (Sistem Informasi Pelaporan & Pencarian Barang Hilang)

Frontend antarmuka web untuk pelaporan kehilangan dan pencarian barang di lingkungan kampus. Dibuat menggunakan **PHP** murni, terstruktur rapi, modular, dan siap dihubungkan ke backend/database (MySQL/PDO).

Desain disesuaikan 100% dengan Figma Design System di folder `ui/design halaman mahasiswa`.

---

## 🚀 Cara Menjalankan Project

### Opsi 1: Klik Langsung (Windows / Laragon / XAMPP)
Cukup klik ganda file **`serve.bat`**. Skrip ini akan otomatis mendeteksi PHP dan membuka web di browser (`http://localhost:8000`).

### Opsi 2: Menggunakan Terminal / Command Prompt
Buka terminal di folder project ini dan jalankan:
```bash
# Menggunakan Laragon PHP:
C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe -S localhost:8000

# Atau jika PHP sudah terdaftar di PATH / XAMPP:
php -S localhost:8000
```
Buka browser ke [http://localhost:8000](http://localhost:8000).

---

## 📁 Struktur File & Mapping ke Desain Figma

| Halaman Figma | File PHP | Deskripsi Fitur |
|---|---|---|
| **Mahasiswa / Beranda** | [index.php](file:///c:/Project/Sip-web%20kerkom/index.php) | Hero banner, search bar cepat, tombol CTA lapor hilang & temu, kartu 6 barang terbaru dilaporkan, dan 4 langkah cara kerja sistem. |
| **Mahasiswa / Pencarian Barang** | [pencarian.php](file:///c:/Project/Sip-web%20kerkom/pencarian.php) | Katalog barang hilang & temuan, search bar real-time, panel filter status laporan, kategori, dropdown lokasi kampus, filter tanggal kejadian, dan paginasi. |
| **Mahasiswa / Detail Barang** | [detail.php](file:///c:/Project/Sip-web%20kerkom/detail.php) | Galeri foto, kartu info pelapor terverifikasi, peringatan keamanan klaim palsu, detail spesifikasi barang, tempat penyimpanan pos keamanan, dan pemicu klaim barang. |
| **Mahasiswa / Modal Klaim** | [modal-klaim.php](file:///c:/Project/Sip-web%20kerkom/modal-klaim.php) | Modal popup pengajuan bukti kepemilikan, pertanyaan verifikasi ciri khusus, upload dokumen/foto pendukung. (Juga terintegrasi interaktif di `detail.php`). |
| **Mahasiswa / Klaim Berhasil** | [klaim-berhasil.php](file:///c:/Project/Sip-web%20kerkom/klaim-berhasil.php) | Konfirmasi pengajuan klaim sukses, ringkasan ID klaim (#KLM-2026-8821), status verifikasi, dan tombol pantau status riwayat. |
| **Mahasiswa / Lapor Kehilangan** | [lapor-kehilangan.php](file:///c:/Project/Sip-web%20kerkom/lapor-kehilangan.php) | Formulir lapor kehilangan: nama barang, kategori, ciri umum (publik), ciri khusus rahasia (private/admin only), lokasi terakhir, tanggal & waktu, dan upload foto barang. |
| **Mahasiswa / Lapor Temuan** | [lapor-temuan.php](file:///c:/Project/Sip-web%20kerkom/lapor-temuan.php) | Formulir lapor temuan: kategori barang, upload foto temuan, lokasi penemuan, tanggal & waktu, dan tempat penyimpanan barang saat ini (pos satpam/disimpan sendiri). |
| **Mahasiswa / Riwayat Saya** | [riwayat.php](file:///c:/Project/Sip-web%20kerkom/riwayat.php) | Tab interaktif "Laporan Saya (3)" dan "Klaim Saya (1)", tabel data riwayat, status badge warna-warni, dan tombol detail. |
| **Mahasiswa / Notifikasi** | [notifikasi.php](file:///c:/Project/Sip-web%20kerkom/notifikasi.php) | Pusat notifikasi aktivitas pelaporan dan klaim dengan badge status belum dibaca (unread dot), waktu relatif, dan ikon penanda. |
| **Mahasiswa / Masuk** | [masuk.php](file:///c:/Project/Sip-web%20kerkom/masuk.php) | Halaman login dengan layout split 2 kolom (ilustrasi kampus + kartu form masuk), show/hide kata sandi, remember me, link lupa sandi dan daftar. |
| **Mahasiswa / Masuk dan Daftar** | [daftar.php](file:///c:/Project/Sip-web%20kerkom/daftar.php) | Formulir registrasi mahasiswa: Nama lengkap, NIM, dropdown program studi & fakultas, email kampus, nomor WhatsApp, kata sandi. |
| **Mahasiswa / Lupa Kata Sandi** | [lupa-sandi.php](file:///c:/Project/Sip-web%20kerkom/lupa-sandi.php) | Halaman reset kata sandi dengan input email kampus dan tombol kirim kode OTP. |
| **Mahasiswa / Verifikasi OTP** | [verifikasi-otp.php](file:///c:/Project/Sip-web%20kerkom/verifikasi-otp.php) | Input 6 digit OTP interaktif (auto-focus, paste, backspace, hitung mundur kirim ulang). Tersedia state switcher untuk melihat state: Normal (`?state=default`), Salah (`?state=salah`), dan Berhasil (`?state=berhasil`). |

---

## 🎨 Asset & Komponen Desain

- **`includes/header.php`**: Header HTML bersama, memuat font Inter, konfigurasi Tailwind, CSS kustom, dan library ikon Lucide.
- **`includes/navbar.php`**: Navigasi atas dengan menu aktif, badge notifikasi (angka 2 merah), tombol Masuk / Profil, dan mobile menu.
- **`includes/footer.php`**: Footer kampus, copyright, dan toast notifikasi global (`#toastNotification`).
- **`includes/data.php`**: Kumpulan fungsi mock data array (`get_mock_items()`, `get_categories()`, `get_locations()`, `get_mock_notifications()`, `render_status_badge()`).
- **`assets/css/style.css`**: Design tokens resmi (warna primary `#2563eb`, status colors, border radius 10px & 16px, custom shadow, input/button styles).
- **`assets/js/main.js`**: Logika interaktif frontend (live filter pencarian, tab riwayat, toggle modal klaim, toast notification, input OTP, drag-and-drop upload preview).
- **`assets/vendor/`**: Berisi file `tailwind.js` dan `lucide.min.js` lokal sehingga aplikasi dapat berjalan 100% tanpa internet / offline.

---

## 🛠️ Panduan untuk Rekan Pengembang Backend

Struktur ini sudah disiapkan agar sangat mudah diintegrasikan dengan database MySQL / PostgreSQL dan session PHP:

1. **Mengganti Mock Data ke Database**:
   - Buka [includes/data.php](file:///c:/Project/Sip-web%20kerkom/includes/data.php).
   - Di sini fungsi seperti `get_mock_items()` mengembalikan array data. Rekan backend dapat membuat koneksi database (misal `includes/db.php`) dan mengganti return array dengan query `PDO::fetchAll()`.

2. **Form Submission Endpoints**:
   - Semua form telah memiliki atribut `action="..."`, `method="POST"`, serta `name="..."` pada setiap input field yang semantik dan terstandarisasi.
   - Misalnya di [lapor-kehilangan.php](file:///c:/Project/Sip-web%20kerkom/lapor-kehilangan.php):
     - `nama_barang` (text)
     - `kategori_barang` (select)
     - `ciri_umum` (textarea - public)
     - `ciri_khusus` (textarea - private/secret proof)
     - `lokasi` (text)
     - `waktu_kejadian` (datetime-local)
     - `foto_barang` (file)

3. **Status Laporan Badge**:
   - Fungsi `render_status_badge($status)` di [includes/data.php](file:///c:/Project/Sip-web%20kerkom/includes/data.php) secara otomatis mewarnai badge sesuai status dari database:
     - `Hilang`: Merah
     - `Ditemukan`: Hijau
     - `Dalam Verifikasi`: Kuning
     - `Dikembalikan`: Indigo
     - `Ditutup`: Abu-abu
