# Deploy MIGAS pada VPS JCP

VPS JCP menjalankan Docker dan Caddy. Konfigurasi ini menjalankan aplikasi dan database dalam container terpisah, lalu menghubungkan container web ke jaringan Caddy yang sudah ada. Container situs lain tidak perlu dihentikan.

## Prasyarat

- Domain atau subdomain MIGAS sudah diarahkan ke IP VPS.
- Jaringan Docker `n8n_default` tersedia.
- Docker Compose plugin tersedia.

## Persiapan kode dan rahasia

Simpan source di `/opt/migastraining`, lalu buat file environment produksi:

```bash
cd /opt/migastraining
cp deploy/.env.example deploy/.env
openssl rand -base64 36
nano deploy/.env
chmod 600 deploy/.env
```

Gunakan dua password acak yang berbeda. Jangan commit atau membagikan `deploy/.env`.

## Jalankan aplikasi

```bash
docker compose --env-file deploy/.env -f deploy/compose.yaml up -d --build
docker compose --env-file deploy/.env -f deploy/compose.yaml ps
docker compose --env-file deploy/.env -f deploy/compose.yaml logs --tail=100 web db
```

Saat volume database baru dibuat, `database/schema.sql` otomatis membuat tabel dan data awal. Jangan hapus volume `migastraining-db` saat pembaruan karena berisi data formulir.

## Hubungkan domain di Caddy

Tambahkan blok di `/opt/n8n/Caddyfile`, lalu ganti contoh domain dengan domain MIGAS yang sudah diarahkan ke VPS:

```caddyfile
migas.example.com {
    encode zstd gzip
    reverse_proxy migastraining-web:80
}
```

Validasi dan muat ulang Caddy:

```bash
docker exec n8n-caddy-1 caddy validate --config /etc/caddy/Caddyfile
docker exec n8n-caddy-1 caddy reload --config /etc/caddy/Caddyfile
```

Caddy akan meminta sertifikat HTTPS otomatis setelah DNS domain mengarah ke VPS dan port 80/443 dapat diakses.

## Pembaruan aplikasi

```bash
cd /opt/migastraining
git pull --ff-only
docker compose --env-file deploy/.env -f deploy/compose.yaml up -d --build
```
