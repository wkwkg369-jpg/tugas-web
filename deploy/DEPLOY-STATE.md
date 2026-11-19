# Deploy arialfian.my.id — SIP-Barang Kampus

**Live: https://arialfian.my.id**

## Versi yang terpasang

| Item | Nilai |
|---|---|
| Sumber kode | GitHub `wkwkg369-jpg/tugas-web`, branch `main`, commit **f3ccf1d** ("update fitur admin, auth, dan laporan") |
| Lokasi | `/var/www/sip-kampus` (milik `www-data`) |
| Database | SQLite `includes/sip_barang.sqlite` (fallback otomatis; MySQL tidak tersedia di VPS) |
| Web server | nginx + `php -S 127.0.0.1:8000` (service `sip-kampus.service`, jalan sebagai `www-data`) |

Versi sebelumnya (backend SQLite buatan sesi lalu) disimpan sebagai branch
`backup-lokal-20261119-1108` di repo dan salinan arsip di `/root/backup-deploy/`.

## Arsitektur

```
browser ──TLS(Let's Encrypt)──> edge Cloudra 103.155.224.225:443
                                  │
                                  └──TLS tanpa SNI──> VPS :80
                                                       │ nginx stream + ssl_preread
                                                       ├─ TLS  -> 127.0.0.1:8443 (vhost HTTPS)
                                                       └─ HTTP -> 127.0.0.1:8081 (vhost HTTP)
                                                                    │
                                                                    └─> php -S 127.0.0.1:8000
```

- `/etc/nginx/stream.d/sip.conf` — deteksi protokol (`$ssl_preread_protocol`)
- `/etc/nginx/sites-available/arialfian.my.id` — vhost 8443 (TLS) + 8081/8080 (HTTP)
- `libnginx-mod-stream` terpasang untuk `ssl_preread`

## Akun admin

Kata sandi admin **tidak** ditulis di repo/dokumen. Kata sandi hasil pengamanan
disimpan hanya di server: `/root/sip-admin-password.txt` (mode 600). Ganti segera
setelah masuk dan hapus berkas itu.

## Pengerasan keamanan (audit 19 Nov 2026)

- Nginx memblokir `/includes/`, `/sessions/`, `/deploy/`, `/ui/`, dotfile,
  serta berkas `.sqlite/.sql/.md/.log/.sh/.bat/.env` (dulu database & `.git` bisa diunduh publik).
- Semua handler admin mewajibkan login sebelum memproses aksi (dulu tambah/hapus
  pengguna dan verifikasi laporan bisa dipanggil tanpa login).
- Token CSRF di semua form POST dan aksi tautan (setujui/tolak/hapus/toggle).
- Kode OTP cadangan `123456`/`482901` dihapus; OTP tidak lagi ditampilkan di layar.
- Login: hanya hash password, batas 5 percobaan/15 menit, cek akun nonaktif,
  regenerasi ID sesi; cookie HttpOnly/SameSite/Secure.
- Unggahan divalidasi ekstensi + MIME + ukuran; laporan tamu tidak lagi
  meminjam identitas akun mahasiswa.

## Hasil uji (lewat domain publik)

- Publik: `/`, `/pencarian.php`, `/pencarian.php?q=tumbler`, `/detail.php?id=1`,
  `/masuk.php`, `/daftar.php`, `/lupa-sandi.php` → **200**; CSS/JS/vendor → **200**
- Login mahasiswa → 302 `/index.php`; `/riwayat.php`, `/notifikasi.php` → 200
- Login admin → 302 `/admin/index.php`; 8 halaman admin (dashboard, verifikasi laporan,
  verifikasi klaim, kelola laporan, kelola pengguna, kategori & lokasi, catat penyerahan,
  rekap) → **200** tanpa warning
- Kartu dashboard: Hilang 3 · Ditemukan 4 · Dalam Verifikasi 0 · Dikembalikan 1
- Upload foto laporan (multipart) → 302 ke riwayat, file tersimpan di `assets/uploads/`
  sebagai `www-data` (data uji sudah dihapus lagi)
- Sertifikat yang dilihat browser: `CN=arialfian.my.id`, Let's Encrypt, berlaku s/d 17 Feb 2027

## Data contoh

Repo GitHub dikirim dengan katalog **kosong** (0 item). Supaya halaman tidak kosong,
dijalankan `php deploy/seed-demo.php` yang menambahkan 8 laporan contoh
(4 ditemukan, 3 hilang, 1 dikembalikan). Skrip aman dijalankan ulang: berhenti sendiri
bila tabel `items` sudah berisi data.


## Perbedaan dari repo (patch lokal)

| File | Perubahan | Alasan |
|---|---|---|
| `admin/includes/admin_header.php` | auto-login admin demo **dimatikan**, diganti `require_admin('../masuk.php')` | Versi repo membuka `/admin/` tanpa login; di domain publik siapa pun bisa menyetujui klaim & menghapus laporan |
| `deploy/seed-demo.php` | baru | Katalog repo kosong (0 item); skrip ini mengisi 8 contoh |
| `.gitignore` | abaikan `sessions/sess_*` dan `assets/uploads/*` | berkas runtime tidak perlu ikut repo |

Uji: `/admin/index.php` tanpa login → 302 `/masuk.php`; login `admin@ui.ac.id` → 200;
login mahasiswa → 302 `/masuk.php`.

## Perawatan

```bash
# tarik update terbaru dari GitHub lalu terapkan
cd /var/www/sip-kampus
git fetch origin && git reset --hard origin/main
chown -R www-data:www-data .
systemctl restart sip-kampus.service

# cek kesehatan domain
./deploy/cek-domain.sh arialfian.my.id

# lihat log
journalctl -u sip-kampus.service -n 30 --no-pager
tail -f /var/log/nginx/sip-access.log
```

Catatan: `includes/sip_barang.sqlite` ikut ter-versi di repo, jadi `git reset --hard`
akan mengembalikan isi database ke kondisi commit tersebut (data contoh hilang bila
belum di-commit). Seeding ulang: `php deploy/seed-demo.php`.
