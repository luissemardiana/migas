# Portal peserta MIGAS Training

Implementasi tetap memakai project PHP/MySQL yang sama. URL lama, carousel, animasi kilang, scrollspy, form daftar minat, dan pemulihan posisi home dipertahankan.

## Halaman dan alur

- `auth.php?mode=register|login|forgot|reset|logout`: akun, sesi dua jam, reset token 30 menit sekali pakai, pembatasan percobaan akun/IP.
- `programs.php#catalog-search`: pencarian, filter kategori/metode/level/tanggal, urutan popularitas berdasarkan pendaftaran disetujui atau jadwal terdekat.
- `program.php?program=SLUG`: deskripsi, silabus, persyaratan, sasaran, instruktur, dan batch dari database.
- `public-training.php`: kalender dan daftar batch. Usulan tetap daftar minat. Batch resmi memerlukan kapasitas, harga (0 jika gratis), dan batas pendaftaran sebelum bisa didaftarkan.
- `enroll.php?batch=ID`: memerlukan akun dan persetujuan. Transaksi mengunci batch untuk pemeriksaan kuota dan pendaftaran ganda.
- `portal.php?view=overview|training|registrations|learning|schedule|attendance|assessments|certificates|notifications|profile`: sepuluh menu peserta.
- `article.php?slug=SLUG`, `instructors.php`, `about.php`, `privacy.php`: konten terbit dan informasi layanan.
- `download.php`: semua file privat memerlukan sesi dan pemeriksaan pemilik/kelas. File tidak berada di document root.
- `verify.php?token=TOKEN`: verifikasi publik dengan token acak; tidak memuat email, nomor telepon, atau identitas tambahan. QR dibuat lokal.

## Instalasi / upgrade

1. Cadangkan database dan kode sebelum upgrade.
2. Jalankan `composer install --no-dev --prefer-dist` untuk pengembangan non-Docker. Docker memasang versi dari `composer.lock`.
3. Database lama menggunakan `database/schema.sql`. **Jangan jalankan ulang seed schema.sql pada produksi yang sudah berisi data.**
4. `php bin/migrate.php` menerapkan tabel tambahan secara idempoten. Tidak menghapus/mengubah delapan program, jadwal usulan, maupun lead lama.
5. Docker menjalankan migrasi saat startup sebelum Apache. Kegagalan migrasi menghentikan startup, bukan mengirim sukses palsu.
6. Deploy dengan `docker compose --env-file deploy/.env -f deploy/compose.yaml up -d --build web`.
7. Volume `migastraining-private` menyimpan unggahan dan sesi. Cadangkan bersama volume database. Jangan gunakan `down -v`.
8. `GET /health.php` mengembalikan `ok` hanya bila koneksi database dan versi migrasi tersedia.

Environment: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD, APP_URL. `PRIVATE_STORAGE` opsional untuk pengembangan; default `storage/private`. Folder harus dapat ditulis akun proses PHP dan tidak dipublikasikan web server.

## Email

Sesuai arahan pengguna, SMTP baru disiapkan, belum diaktifkan. Isi SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASSWORD, SMTP_FROM, SMTP_ENCRYPTION di `deploy/.env`, lalu recreate web. Gunakan APP_URL HTTPS resmi. Jangan commit kredensial. Tanpa konfigurasi, halaman pemulihan menjelaskan bahwa email belum diaktifkan dan mengarahkan ke kontak. Tidak ada klaim bahwa email sudah terkirim.

## Pengelolaan oleh admin melalui server

Portal yang diminta adalah sisi peserta. Pengelolaan penerbitan dan persetujuan tersedia melalui CLI server yang memeriksa role admin dan mencatat audit; tidak ada akun admin atau kata sandi bawaan.

Daftarkan akun milik pengelola melalui website. Pemilik VPS lalu menjalankan:

```sh
docker exec migastraining-web php bin/manage.php promote EMAIL_PENGELOLA
docker exec migastraining-web php bin/manage.php help
```

Gunakan ID akun admin untuk perintah berikut. File JSON harus disalin ke container atau berada dalam storage privat. Jangan memasukkan password ke argumen shell.

```sh
php bin/manage.php import ADMIN_ID TABLE /path/data.json
php bin/manage.php review ADMIN_ID /path/review.json
php bin/manage.php grade ADMIN_ID /path/grade.json
php bin/manage.php attendance ADMIN_ID /path/attendance.json
php bin/manage.php open-attendance ADMIN_ID /path/session.json
php bin/manage.php store-file ADMIN_ID /path/material.pdf
php bin/manage.php issue ADMIN_ID /path/certificate.json
php bin/manage.php revoke ADMIN_ID CERTIFICATE_ID
```

Tabel impor yang diizinkan tercantum eksplisit dalam `app/administration.php`: program_details, batch_settings, training_batches, instructors, program_instructors, articles, testimonials, faqs, training_sessions, lessons, assignments, assessments, assessment_questions. Sertakan ID/primary key untuk memperbarui. Konten teks ditampilkan sebagai teks biasa (bukan HTML tak tepercaya). URL materi wajib HTTP/HTTPS. Soal tidak boleh diubah setelah ada percobaan peserta.

Contoh bentuk data (nilai contoh hanya dokumentasi, tidak disisipkan ke produksi):

```json
{"id": 123, "status": "approved", "remarks": "Persyaratan sudah diverifikasi.", "payment": "paid"}
```

`review`: id pendaftaran, status under_review/approved/rejected/cancelled, remarks, payment not_required/unpaid/pending/paid. Approval membuat akses kelas; status lain mencabut akses. Pembayaran berbayar tidak boleh not_required.

`grade`: `{submission_id,status,feedback}` untuk tugas (passed/revision); atau `{assessment_id,user_id,score,feedback}` untuk praktik. Evaluasi daring dinilai server; satu percobaan per evaluasi. Jawaban terlambat tidak dinilai. Pembahasan hanya setelah closes_at lewat dan reveal_answers aktif.

`attendance`: `{session_id,user_id,status}` dengan present/late/absent/excused. `open-attendance`: `{session_id,opens_at,closes_at}` menghasilkan tautan dan SVG QR privat untuk dibagikan pengelola kepada kelas, terbatas waktu dan peserta yang berhak.

`store-file` menghasilkan nama acak untuk file_path pada lessons. PDF/PPTX/MP4 maksimal 200 MB melalui CLI; unggahan tugas peserta hanya PDF 2 MB. Foto profil JPG/PNG/WebP 2 MB. Materi video dapat berupa file privat atau tautan layanan video HTTPS; kontrol akses hosting video eksternal harus diatur pemilik konten.

`issue`: `{enrollment_id,number,pdf_path}`. PDF sertifikat resmi harus disiapkan pengelola (bukan sertifikat palsu yang dibuat otomatis). Syarat: kelas berakhir, pendaftaran disetujui, biaya sudah lunas jika berbayar, semua materi ditandai selesai, semua tugas lulus, seluruh sesi hadir/terlambat, seluruh evaluasi terbit lulus, dan persetujuan admin eksplisit. Sertifikat menyimpan snapshot nama/program, nomor unik, file privat, QR verifikasi, dan audit penerbit. File PDF dapat diunduh peserta; pencabutan langsung menutup download dan verifikasi.

## Pengingat

Jalankan `php bin/reminders.php` setiap 15 menit. Pengingat portal dikirim untuk sesi dalam 24 jam mendatang, unik per peserta/sesi/waktu. Contoh cron host:

```cron
*/15 * * * * root /usr/bin/docker exec --user www-data migastraining-web php /var/www/html/bin/reminders.php >/dev/null 2>&1
```

## Pengujian

Pengujian tulis **hanya** pada database baru bernama `migas_test_*` dan browser localhost; guard mencegah pemakaian database produksi.

1. Atur DB_* ke database uji baru, PRIVATE_STORAGE dan TEST_FIXTURE_FILE ke folder uji.
2. `php tests/portal-integration.php` membuat schema/fixture terisolasi dan menguji backend.
3. Jalankan PHP built-in server dengan environment yang sama pada localhost:8088.
4. Instal Playwright di lingkungan pengujian; atur NODE_PATH bila dependensinya terpisah.
5. `node tests/portal-browser.cjs` untuk akses, halaman, dan responsive 390/1440 px; TEST_SCREENSHOT_DIR diperlukan.
6. `node tests/portal-flows.cjs` untuk formulir, upload, reset, QR, pencabutan, filter, dan consent.

Data yang belum diberikan pengelola (profil instruktur, kurikulum resmi, materi, artikel, testimoni, batch resmi, sertifikat bertanda tangan) sengaja tidak diisi data fiktif. Peserta melihat keadaan kosong yang menjelaskan langkah berikutnya.
