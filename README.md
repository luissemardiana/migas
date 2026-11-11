# MIGAS Training

Website pelatihan industri berbasis PHP 8, MySQL, PDO, Tailwind CSS, dan JavaScript.

## Menjalankan dengan XAMPP

1. Aktifkan Apache dan MySQL.
2. Buka phpMyAdmin, buat database `migas_training` dengan collation `utf8mb4_unicode_ci`, pilih database tersebut, lalu impor `database/schema.sql`. Berkas ini membuat tabel katalog, jadwal, dan permintaan serta mengisi delapan program dan tiga tanggal usulan awal.
3. Pengaturan lokal memakai `127.0.0.1`, database `migas_training`, user `root`, dan password kosong. Atur environment variables di bawah jika konfigurasi MySQL berbeda.
4. Pastikan ekstensi `pdo_mysql` aktif di PHP.
5. Buka `http://localhost/migas/public/`.

## Deployment

- Konfigurasi Docker + Caddy untuk VPS JCP tersedia di [`deploy/README.md`](deploy/README.md), bersama contoh Docker Compose dan reverse proxy.
- Atur document root situs ke folder `public/`. Folder `config/`, `database/`, dan `resources/` harus tetap berada satu tingkat di atas `public/` dan tidak dapat diakses langsung melalui web.
- Gunakan PHP 8.1+ dengan ekstensi `pdo_mysql` dan `mbstring`, serta MySQL 8+/MariaDB yang mendukung InnoDB dan `utf8mb4`.
- Minta admin database membuat database aplikasi, lalu impor `database/schema.sql` ke database tersebut. Untuk database lama, jalankan `database/add-training-batches.sql`.
- Atur variabel lingkungan PHP berikut di panel hosting atau konfigurasi server. Jangan simpan kredensial produksi di berkas publik.

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=migas_training
DB_USER=nama_user_database
DB_PASSWORD=password_database
```

- User database aplikasi cukup diberi hak `SELECT` pada `training_programs` dan hak `INSERT` pada `training_requests`. Jalankan perubahan schema dan seed memakai akun admin database saat deploy.
- Aktifkan HTTPS untuk situs produksi. Form menggunakan session cookie aman pada HTTPS, token CSRF, validasi sisi server, serta prepared statements.
- Tailwind CSS dan font Plus Jakarta Sans dimuat dari CDN, jadi server atau browser perlu akses internet agar tampilan lengkap. Video dan gambar hero berada di `public/assets/` dan disajikan lokal.

Jika koneksi katalog belum tersedia, halaman tetap menampilkan delapan program cadangan. Formulir hanya menampilkan sukses setelah permintaan benar benar tersimpan ke database; kesalahan koneksi dicatat ke log PHP dan ditampilkan sebagai pesan ramah.
