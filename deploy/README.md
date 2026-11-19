# Menyambungkan domain IDCloudHost ke aplikasi

Ada dua kondisi yang menentukan caranya. Cek dulu: **apakah mesin tempat aplikasi ini
berjalan punya IP publik sendiri?**

```bash
hostname -I          # contoh: 10.1.0.67  -> IP privat, di belakang NAT
curl -s https://api.ipify.org   # IP keluar (bisa jadi milik gateway bersama)
```

Kalau `hostname -I` hanya menampilkan `10.x`, `172.16-31.x`, atau `192.168.x`, mesin itu
**tidak punya IP publik** → jalur A tidak mungkin, pakai jalur B.

---

## Jalur A — VPS punya IP publik (cara paling standar)

### 1. Catat IP publik VPS
Panel IDCloudHost → **Compute / VPS** → detail server → `Public IP`.

### 2. Arahkan domain ke IP itu
Panel IDCloudHost → **Domains** → pilih domain → **Zone Editor / Kelola DNS**, tambahkan:

| Type | Name | Value | TTL |
|---|---|---|---|
| A | `@` | `IP-PUBLIK-VPS` | 300 |
| A | `www` | `IP-PUBLIK-VPS` | 300 |

Hapus A record lama yang masih menunjuk ke IP parking/website lama.
Propagasi biasanya 5–30 menit (TTL 300). Cek dengan:

```bash
dig +short domainkamu.com A
dig +short www.domainkamu.com A
```

### 3. Pasang web server di VPS

Opsi tercepat (Caddy, HTTPS otomatis):

```bash
sudo apt update && sudo apt install -y caddy
sudo cp deploy/caddy.sip.conf /etc/caddy/Caddyfile
sudo nano /etc/caddy/Caddyfile        # ganti domainkamu.com
sudo systemctl reload caddy
```

Opsi umum (nginx + certbot):

```bash
sudo apt update && sudo apt install -y nginx certbot python3-certbot-nginx
sudo cp deploy/nginx-sip-kampus.conf /etc/nginx/sites-available/sip-kampus.conf
sudo nano /etc/nginx/sites-available/sip-kampus.conf   # ganti domainkamu.com
sudo ln -sf /etc/nginx/sites-available/sip-kampus.conf /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d domainkamu.com -d www.domainkamu.com
```

### 4. Pastikan aplikasi tetap hidup
Aplikasi dilayani `php -S 127.0.0.1:8000` lewat unit `sip-kampus.service`
(lihat README). Nginx/Caddy meneruskan request ke situ.

### 5. Buka firewall untuk 80 & 443
```bash
sudo ufw allow 80/tcp && sudo ufw allow 443/tcp    # bila memakai ufw
```
Di panel IDCloudHost juga cek **Firewall / Security Group** VPS: izinkan inbound
TCP 80 dan 443.

### 6. Uji
```bash
curl -sI https://domainkamu.com | head -3          # 200
curl -s https://domainkamu.com | grep -oE '<title>[^<]+</title>'
```

---

## Jalur B — mesin di belakang NAT (tanpa IP publik)

Domain tidak bisa diarahkan langsung ke mesin ini karena tidak ada port 80/443 yang
masuk. Solusinya **Cloudflare Tunnel**: domain tetap milik Anda, trafik masuk lewat
Cloudflare lalu diteruskan ke mesin ini via koneksi keluar.

### 1. Pindahkan nameserver domain ke Cloudflare
1. Daftar/masuk [dash.cloudflare.com](https://dash.cloudflare.com) → **Add a site** →
   masukkan domain → pilih **Free**.
2. Cloudflare menampilkan 2 nameserver, mis. `ada.ns.cloudflare.com` dan
   `bob.ns.cloudflare.com`.
3. Di IDCloudHost: **Domains → Manage Nameservers** → ganti NS lama dengan dua NS
   Cloudflare tersebut → simpan.
4. Tunggu status domain di Cloudflare berubah **Active** (5 menit–24 jam, biasanya
   < 1 jam).

### 2. Buat tunnel
Dashboard Cloudflare → **Zero Trust → Networks → Tunnels → Create a tunnel** →
pilih **Cloudflared** → beri nama `sip-kampus` → di halaman berikut tersedia
**token** dan perintah:

```bash
sudo cloudflared service install <TOKEN-DARI-DASHBOARD>
```

### 3. Arahkan domain ke tunnel
Masih di halaman tunnel → **Public Hostname → Add a public hostname**:

| Field | Isi |
|---|---|
| Subdomain | `sip` (atau kosongkan untuk domain utama) |
| Domain | `domainkamu.com` |
| Type | `HTTP` |
| URL | `localhost:8000` |

### 4. Matikan tunnel sementara (quick tunnel) yang lama
```bash
sudo systemctl disable --now sip-kampus-tunnel.service
```
Unit `sip-kampus.service` (server PHP) tetap dipakai.

### 5. Uji
```bash
curl -sI https://sip.domainkamu.com | head -3
```

Hasil: `https://sip.domainkamu.com` → mesin ini, HTTPS gratis, tanpa port forwarding.

---

## Jalur C — aplikasi dipindah ke hosting IDCloudHost

Kalau yang dibeli **shared hosting** (bukan VPS): upload isi folder ini ke
`public_html`, pilih PHP 8.1+, lalu pastikan `data/` dan `uploads/` bisa ditulis
(permission 755/775). Database SQLite dibuat otomatis saat pertama diakses.
Tambahkan domain lewat cPanel → **Addon Domains**.

---

## Ringkasan cepat

| Kondisi | Yang dipakai | Hasil |
|---|---|---|
| VPS punya IP publik | A record → IP + nginx/caddy + certbot | `https://domainkamu.com` |
| Mesin di belakang NAT | NS ke Cloudflare + Cloudflare Tunnel | `https://sip.domainkamu.com` |
| Shared hosting | Upload ke `public_html` | `https://domainkamu.com` |
