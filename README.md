# Kalender Penerima Manfaat

Aplikasi satu halaman berbasis PHP dan MySQL/MariaDB untuk mencatat status operasional serta rincian penerima manfaat per tanggal.

## Kebutuhan

- PHP 8.1 atau lebih baru dengan ekstensi `pdo_mysql` dan `mbstring`.
- MySQL 8 atau MariaDB 10.4 atau lebih baru.
- Apache/Nginx atau server PHP lokal.

## Menjalankan dengan XAMPP

1. Nyalakan Apache dan MySQL dari XAMPP Control Panel.
2. Impor `database/schema.sql` melalui phpMyAdmin atau jalankan `mysql -u root < database/schema.sql` dari direktori proyek. Sesuaikan perintah dengan akun MySQL Anda bila memakai kata sandi.
3. Salin folder proyek ke direktori web server, lalu buka `http://localhost/manfaat/`.
4. Jika pengaturan database bukan nilai bawaan XAMPP (`127.0.0.1:3306`, database `manfaat`, user `root`, tanpa kata sandi), atur environment `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, dan `DB_PASSWORD` untuk proses PHP.

PHP development server juga bisa digunakan dari direktori proyek dengan `php -S 127.0.0.1:8000`, setelah database dibuat dan skema diimpor.

## Perilaku data

- Setiap tanggal memiliki paling banyak satu laporan; rincian disimpan terpisah dan total selalu dihitung dari `beneficiary_details.quantity`.
- Tanggal baru ditampilkan sebagai `Belum Diisi` dengan total 0. Tidak ada data contoh yang otomatis dianggap sebagai laporan nyata.
- Semua perubahan harus disimpan melalui tombol **Simpan**. Menutup panel tidak menghapus data tersimpan; perubahan yang belum disimpan memerlukan konfirmasi.
- Validasi jumlah dan nama dilakukan di browser serta server. Penyimpanan menggunakan transaksi dan token CSRF sesi.
- Belum ada autentikasi admin. Jangan publikasikan aplikasi ke jaringan tanpa menambahkan login dan pembatasan akses.

## Skema

Skema tabel dan indeks unik tanggal tersedia di `database/schema.sql`. Kredensial lokal tidak ditulis ke source code; gunakan environment variable untuk konfigurasi non-default.
