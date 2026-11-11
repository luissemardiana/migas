# Analisis User Side MIGAS Training — STEP 1

Tanggal audit: 6 November 2026, Asia/Jakarta.

Ruang lingkup: source project yang sudah ada, halaman produksi https://migastraining.duckdns.org/, metadata database MySQL produksi, dan konfigurasi deployment aplikasi. Audit dilakukan dengan pembacaan kode, SELECT metadata/jumlah baris, dan GET halaman. Tidak ada pengiriman formulir, perubahan schema, atau perubahan kode aplikasi dalam tahap ini.

## 1. Kesimpulan

Project saat ini merupakan website informasi pelatihan dengan katalog dan daftar minat berbasis database. Fondasinya dapat diteruskan, tetapi belum merupakan sistem pengelolaan peserta atau Learning Management System.

PHP, MySQL, URL publik, dan identitas visual yang ada dapat dipertahankan. Pengembangan utama membutuhkan model data peserta, autentikasi terpusat, pendaftaran per batch, kontrol akses, dan penyimpanan materi yang terlindungi. Halaman peserta harus memakai data tersebut, bukan statistik atau status simulasi.

## 2. Struktur dan runtime yang ditemukan

| Lokasi | Fungsi saat ini |
| --- | --- |
| `public/` | Document root; halaman PHP sebagai endpoint langsung |
| `public/assets/css/` | Styling bersama, carousel, hero, dan responsive rules |
| `public/assets/js/` | Interaksi, scrollspy, validasi form, dan pemulihan posisi home |
| `resources/views/components/` | Header, footer, carousel bersama |
| `resources/views/pages/home.php` | Template homepage |
| `config/database.php` | Koneksi PDO dengan konfigurasi environment |
| `config/programs.php` | Query katalog/detail dan data cadangan |
| `config/batches.php` | Query jadwal dan format tanggal |
| `database/` | Schema, seed program/jadwal, dan SQL pembaruan sebelumnya |
| `deploy/` | Docker PHP/Apache, MySQL, dan integrasi Caddy |

Runtime produksi yang diperiksa: PHP 8.3.36, MySQL 8.4.13. Ekstensi `pdo_mysql`, `mbstring`, `fileinfo`, dan `openssl` tersedia; `gd` dan `zip` tidak tersedia. Batas upload PHP saat audit adalah 2 MB.

Routing belum memakai framework atau router terpusat. Delapan halaman publik: `index.php`, `programs.php`, `program.php`, `public-training.php`, `in-house.php`, `instructors.php`, `insights.php`, dan `contact.php`.

`program.php?program=<slug>` memilih program. `contact.php?type=public|inhouse|guest-lecture` melayani tiga jenis permintaan. GET dan POST formulir ditangani pada endpoint yang sama.

## 3. Database produksi

| Tabel | Jumlah baris | Kegunaan |
| --- | ---: | --- |
| `training_programs` | 8 | Katalog; seluruh program aktif |
| `training_batches` | 3 | Jadwal; seluruhnya `proposed` |
| `training_requests` | 0 | Daftar minat dan permintaan perusahaan/institusi |

Relasi yang tersedia: `training_batches.program_slug` merujuk `training_programs.slug`. Permintaan memiliki nama/kontak dan program sebagai isian, tetapi belum memiliki `user_id` atau `batch_id` yang mengikat peserta ke kelas.

Jadwal di database:

- RCM: 15–16 Desember 2026.
- Vibration Analysis: 19–21 Januari 2027.
- Gas Engine Maintenance: 16–17 Februari 2027.

Ketiganya merupakan usulan kelas daring, belum batch resmi yang dapat dianggap telah menerima pendaftaran. Belum ada kolom harga, kapasitas, tenggat pendaftaran, jam sesi, instruktur, atau meeting link.

## 4. Pemetaan kebutuhan terhadap kondisi sekarang

| Area | Sudah tersedia | Pengembangan yang dibutuhkan |
| --- | --- | --- |
| Homepage | Hero kilang, 8 program, penjelasan pendekatan, CTA, bagian instruktur/wawasan, kontak/peta | Profil MIGAS yang lengkap, kategori, metode belajar, alasan memilih, statistik faktual, testimoni terverifikasi |
| Katalog | Carousel 8 program dari database | Pencarian, filter kategori/metode/tanggal/level, sorting, durasi, harga dan jadwal terdekat |
| Detail program | Judul, kategori, subjudul, CTA daftar minat | Deskripsi lengkap, tujuan, kurikulum/modul, syarat, peserta sasaran, instruktur, lokasi, harga, batch dan CTA pendaftaran |
| Jadwal publik | Daftar kartu batch, tanggal, durasi kalender, format dan status | Kalender, deadline, kuota, harga, lokasi resmi, jam sesi dan status pendaftaran |
| Instruktur | Halaman penjelasan; belum ada profil yang dipublikasikan | Data profil, keahlian, pengalaman, sertifikasi dan relasi program/batch |
| Artikel | Tiga topik usulan yang ditulis dalam kode | Artikel dari database, detail, kategori, pencarian dan artikel terkait |
| Tentang/kontak/FAQ | Kontak perusahaan, peta home, formulir tiga jenis permintaan | Halaman profil lengkap dan FAQ terkelola; bedakan pesan kontak umum dan pendaftaran peserta |
| Autentikasi | Session hanya untuk CSRF/pesan formulir | Register, login/logout, reset password, hashing, pengelolaan sesi dan pembatasan percobaan |
| Dashboard dan My Training | Belum ada | Ringkasan berdasarkan enrollment, kelas mendatang/aktif/selesai, kemajuan nyata |
| My Registration | Form daftar minat tersedia | Pendaftaran akun ke batch, nomor pendaftaran, status verifikasi/pembayaran, catatan admin dan pembatalan |
| Learning Center | Belum ada | Modul, video, dokumen, akses unduh terlindungi, tugas dan pencatatan penyelesaian eksplisit |
| Jadwal pribadi | Belum ada | Sesi dari kelas peserta, kalender, lokasi, instruktur dan meeting link dengan validasi akses |
| Kehadiran | Belum ada | Riwayat per peserta, check-in dan QR yang diaktifkan admin |
| Assessment | Belum ada | Quiz/ujian, attempt, jawaban, penilaian praktik, riwayat skor dan aturan publikasi hasil |
| Sertifikat | Belum ada | Eligibility, persetujuan admin, nomor sertifikat, PDF, QR verifikasi dan pencabutan |
| Notifikasi | Belum ada | Dropdown, notification center dan status dibaca |
| Profil | Belum ada | Edit profil, password, foto, perusahaan/jabatan, alamat dan data identitas seperlunya |

## 5. Fitur yang sudah bekerja dan perlu dipertahankan

- Visual navy/biru, aksen kuning/turquoise, font Inter/Manrope, hero kilang berulang dan carousel interaktif.
- Scrollspy dan pemulihan posisi/kartu program ketika kembali ke home, termasuk pencegahan kilasan bagian atas home.
- Footer dan Find Us hanya muncul pada home; akun Instagram tetap kosong sesuai permintaan sebelumnya.
- Query program menggunakan PDO, parameter binding pada pencarian slug, dan escaping output.
- Formulir memiliki validasi server, CSRF, persetujuan privasi, honeypot, prepared INSERT dan redirect setelah sukses.
- Tombol kirim terkunci sampai isian wajib dan persetujuan terpenuhi.
- Kesalahan koneksi form tidak dilaporkan sebagai keberhasilan penyimpanan.

## 6. Temuan yang memengaruhi implementasi

1. **Daftar minat belum sama dengan enrollment.** Tabel permintaan tidak memiliki akun peserta, batch terpilih, pembayaran, atau status persetujuan peserta. Data lama perlu dipertahankan sebagai permintaan, bukan otomatis dianggap pendaftaran disetujui.
2. **Fallback katalog bisa menampilkan data yang sudah dinonaktifkan.** `findProgram()` mencari data cadangan saat query berhasil tetapi program aktif tidak ditemukan; `allPrograms()` juga mengembalikan cadangan ketika hasil database kosong. Perilaku ini harus dibedakan dari gangguan koneksi agar keputusan publikasi database dihormati.
3. **Halaman detail belum memakai deskripsi lengkap dari database.** Isi kurikulum/metode/instruktur sebagian besar merupakan pemberitahuan menunggu konfirmasi.
4. **Belum ada panel atau alur admin dalam repository.** Verifikasi pendaftaran, pengisian nilai praktik, pengaktifan QR, dan penerbitan sertifikat memerlukan sumber keputusan admin. Endpoint peserta tidak boleh dapat mengubah keputusan tersebut sendiri.
5. **Session dan upload belum disiapkan untuk portal.** Session memakai file; compose belum menyediakan volume persistent untuk session atau materi. Berkas privat perlu ditempatkan di luar `public/`, pada penyimpanan persistent, dengan endpoint unduh berizin.
6. **Reset password belum memiliki integrasi email.** Kode aplikasi belum memiliki mailer atau konfigurasi SMTP. Token harus tersimpan dalam bentuk hash, sekali pakai, kedaluwarsa dan dikirim melalui layanan email yang benar.
7. **Aset styling masih bergantung pada layanan eksternal.** Tailwind runtime, font dan Lucide dimuat dari CDN; sebagian thumbnail dari Unsplash. Produksi sebaiknya memakai CSS hasil build dan versi dependency yang tetap untuk kestabilan.
8. **Ada masalah lebar home di mobile.** Pada viewport 390 px, lebar dokumen home terukur 414 px. Katalog, jadwal, dan kontak yang diperiksa terukur 390 px. Penyebab overflow home perlu diperbaiki saat pengerjaan responsive.
9. **Tombol kembali perlu konsisten.** Header menggunakan `index.php?return=home`, sedangkan tombol tambahan dalam source `in-house.php` masih memakai `index.php` langsung. Halaman baru perlu memakai helper navigasi yang sama.
10. **Dokumentasi perlu diperbarui.** README masih menyebut font lama dan contoh hak database yang belum mencakup seluruh akses jadwal. Instruksi deployment `git pull` juga perlu disesuaikan dengan perubahan lokal/server yang belum tercatat sebagai commit.

## 7. Struktur pengembangan yang disarankan

Pertahankan semua URL publik. Tambahkan route autentikasi dan peserta secara bertahap, misalnya `public/login.php`, `register.php`, `forgot-password.php`, `reset-password.php`, serta `public/participant/` untuk halaman peserta.

Pisahkan logika reusable ke `app/` untuk bootstrap, autentikasi/otorisasi, validasi, query, pendaftaran, progress dan sertifikat. Ini penambahan terarah pada project PHP yang sama, bukan migrasi framework atau pembuatan project baru. Template portal dan sidebar menggunakan komponen bersama di `resources/views/`.

Gunakan `storage/private/` untuk materi, foto sumber dan sertifikat sesuai kebutuhannya, dengan volume produksi yang persisten. Download materi/sertifikat melewati pemeriksaan login, enrollment, kepemilikan dan status publikasi; bukan URL file privat langsung.

## 8. Rencana model data

Nama berikut merupakan rancangan, belum tabel yang sudah dibuat.

| Kelompok | Rancangan tabel/relasi |
| --- | --- |
| Akun | `users`, `participant_profiles`, `password_reset_tokens`, penyimpanan sesi/revokasi, `auth_attempts` |
| Konten publik | `training_categories`, `instructors`, relasi program/instruktur, `articles`, `article_categories`, `faqs`, `testimonials` dengan status publikasi |
| Program | Perluasan `training_programs` untuk level, tujuan, persyaratan, sasaran, deskripsi dan kurikulum; metadata metode/durasi/harga mengikuti penempatan yang sesuai |
| Batch dan sesi | Perluasan `training_batches` untuk kuota/deadline/harga/status; `training_sessions` untuk waktu, lokasi, metode, instruktur, informasi praktik dan meeting link privat |
| Pendaftaran | `registrations` terikat ke `users` dan `training_batches`, riwayat status/catatan admin; `enrollments` sebagai dasar hak akses belajar; pembayaran jika dibutuhkan |
| Pembelajaran | `learning_modules`, `lessons`, `learning_materials`, `lesson_completions`, `assignments`, `assignment_submissions` |
| Kehadiran | `attendance_records`, sesi check-in/QR yang diaktifkan admin dengan token bertenggat |
| Evaluasi | `assessments`, `assessment_questions`, opsi jawaban, `assessment_attempts`, `assessment_answers`, hasil praktik |
| Sertifikat | `certificates`, keputusan persetujuan/eligibility dan token verifikasi publik; simpan PDF dan status pencabutan |
| Notifikasi/audit | `notifications`, `audit_logs`, antrean email/outbox jika pengiriman asinkron diperlukan |

Relasi utama: **peserta → pendaftaran → batch → program**. Enrollment menentukan akses sesi, materi dan evaluasi. Penyelesaian materi, tugas, kehadiran dan hasil ujian menyuplai perhitungan progress/kelulusan. Membuka file saja tidak otomatis menyelesaikan materi atau kelas.

Status pendaftaran peserta dipisahkan dari status daftar minat yang sudah ada. Kuota dan pendaftaran ganda perlu ditangani dengan transaksi dan constraint, bukan hanya tombol frontend. Statistik dan popularitas dihitung dari data yang benar, bukan angka promosi buatan.

## 9. Urutan implementasi setelah laporan ini

1. Siapkan migrasi tambahan dan database pengujian, bootstrap PHP bersama, autentikasi, CSRF global, kontrol akses/ownership, mailer, dan penyimpanan persistent.
2. Lengkapi katalog/detail/jadwal beserta pendaftaran per batch dan kerangka portal. UI dan query dikerjakan bersama agar setiap halaman mempunyai fungsi nyata.
3. Implementasikan My Registration, My Training, profil dan notifikasi menggunakan enrollment/status database.
4. Implementasikan Learning Center, sesi hybrid, unduh materi terlindungi dan progress eksplisit.
5. Implementasikan kehadiran, assessment dan aturan kelulusan; selanjutnya PDF sertifikat dan verifikasi QR dengan persetujuan admin.
6. Lengkapi konten publik yang tersedia, responsive layout, loading/empty/error states, lalu jalankan pengujian menyeluruh dan deployment bertahap.

Migrasi tidak boleh menghapus tiga tabel yang ada atau menjalankan ulang seed untuk menimpa konten produksi. Cadangkan database sebelum migrasi. Implementasi perlu menetapkan cara admin melakukan tindakan berizin karena modul admin belum tersedia dalam project ini.

## 10. Data dan integrasi yang belum tersedia

- Profil instruktur yang disetujui, silabus/modul, video/PDF/presentasi, soal ujian dan aturan penilaian.
- Kuota, harga, deadline, lokasi/jam resmi, serta aturan pembatalan dan persyaratan kelulusan.
- Materi artikel lengkap, testimoni berizin, profil perusahaan MIGAS dan FAQ yang disetujui.
- Pengirim email/SMTP untuk reset password dan notifikasi; metode pembayaran bila kelas berbayar.
- Template sertifikat resmi, penandatangan dan kebijakan verifikasi publik.
- Mekanisme atau akun admin untuk persetujuan pendaftaran, kehadiran, nilai praktik dan sertifikat.

Fitur dapat disiapkan dengan empty state dan status draft/menunggu publikasi. Data pribadi instruktur, testimoni, jumlah lulusan, nilai dan sertifikat tidak boleh diisi dengan data rekaan sebagai hasil akhir.

## 11. Pemeriksaan pada tahap analisis dan rencana STEP 4

Hasil pemeriksaan baca-saja saat ini:

- Delapan endpoint publik utama memberikan HTTP 200.
- Slug program tidak dikenal memberikan HTTP 404.
- Permintaan ke `/config/database.php` dan `/database/schema.sql` memberikan HTTP 404; folder internal bukan document root.
- Tampilan mobile diperiksa pada home, katalog, jadwal dan kontak; ditemukan overflow home sebagaimana dicatat di atas.
- Form kontak mempunyai CSRF, metode POST, persetujuan wajib dan tombol awal disabled.
- Tidak ada exception JavaScript pada empat halaman yang diperiksa.
- Koneksi PDO produksi berhasil; metadata dan jumlah baris dibaca langsung. Tidak dilakukan transaksi tulis atau klaim bahwa INSERT formulir telah diuji ulang dalam tahap ini.

Untuk STEP 4, gunakan database terpisah dan minimal dua akun peserta untuk menguji ownership/IDOR pada pendaftaran, materi, meeting link, kehadiran, jawaban dan PDF sertifikat. Uji juga token reset kedaluwarsa/dipakai ulang, sesi logout/password berubah, CSRF, upload berbahaya, kuota bersamaan, pengiriman ganda, batas waktu ujian, kerahasiaan kunci jawaban, dan sertifikat tanpa persetujuan. Periksa keyboard, desktop/tablet/mobile, status kosong/error/loading, serta rollback migrasi dan persistence sesudah container dibuat ulang.

## 12. Batas tahap ini

STEP 1 telah selesai. Hasil tahap ini adalah audit dan rancangan pengembangan. Fitur autentikasi, portal, migrasi baru, pengiriman email dan sertifikat belum diimplementasikan atau dideploy sebagai bagian dari tahap analisis ini.
