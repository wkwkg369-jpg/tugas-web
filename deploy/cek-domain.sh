#!/usr/bin/env bash
#
# Cek kesiapan domain untuk SIP-Barang Kampus.
#
#   ./deploy/cek-domain.sh                 # pakai arialfian.my.id
#   ./deploy/cek-domain.sh domainku.com
#
set -uo pipefail

DOMAIN="${1:-arialfian.my.id}"
PUBIP="${PUBIP:-103.155.224.225}"

line() { printf '%s\n' "------------------------------------------------------------"; }

line
echo "Domain   : $DOMAIN"
echo "IP publik: $PUBIP"
line

echo "1) DNS A record"
A="$(dig +short A "$DOMAIN" @1.1.1.1 2>/dev/null | tr '\n' ' ')"
echo "   A = ${A:-<kosong>}"
if [ -z "${A// /}" ]; then
    echo "   -> belum ada A record. Tambahkan: A @ -> $PUBIP (TTL 300)"
elif ! printf '%s' "$A" | grep -q "$PUBIP"; then
    echo "   -> A record belum menunjuk $PUBIP"
else
    echo "   -> OK, menunjuk ke gateway $PUBIP"
fi

echo
echo "2) Nginx lokal dengan Host asli (jalur edge -> VPS:80)"
printf '   http 127.0.0.1/  -> %s\n' "$(curl -s -H "Host: $DOMAIN" -o /tmp/_cd.html -w '%{http_code}' http://127.0.0.1/ 2>/dev/null)"
printf '   judul halaman    -> %s\n' "$(grep -oE '<title>[^<]+</title>' /tmp/_cd.html 2>/dev/null | head -1 || echo '-')"

echo
echo "3) Akses publik"
printf '   https://%s -> %s\n' "$DOMAIN" "$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "https://$DOMAIN" 2>/dev/null)"
printf '   http://%s  -> %s\n' "$DOMAIN" "$(curl -s -o /dev/null -w '%{http_code} %{redirect_url}' --max-time 15 "http://$DOMAIN" 2>/dev/null)"

echo
echo "4) Host terakhir yang diterima nginx (bukti edge meneruskan)"
tail -5 /var/log/nginx/sip-access.log 2>/dev/null | sed 's/^/   /' || echo "   (log belum ada)"

echo
echo "5) Layanan"
for s in nginx sip-kampus.service sip-kampus-tunnel.service; do
    printf '   %-28s %s\n' "$s" "$(systemctl is-active "$s" 2>/dev/null)"
done

line
echo "Catatan: bila langkah 3 masih 502/gagal sementara langkah 2 OK,"
echo "masalahnya di DNS/panel (edge belum meneruskan), bukan di aplikasi."
