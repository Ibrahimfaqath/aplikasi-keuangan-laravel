# Panduan Deployment DompetKu ke cPanel

Panduan ini untuk men-deploy aplikasi **DompetKu** (Laravel 13) ke hosting cPanel.

## 1. Persyaratan

- **PHP 8.3+** — aplikasi ini butuh PHP ^8.3 (ceklis lewat **Select PHP Version** di cPanel)
- **MySQL** — database sudah dikonfigurasi pakai MySQL (`DB_CONNECTION=mysql`)
- **Ekstensi PHP**: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` (untuk gambar/PDF), `zip`
- **Terminal/SSH cPanel** (fitur "Terminal" di cPanel) — dipakai untuk migrasi & optimasi. Hampir semua hosting cPanel cloud punya fitur ini. Kalau tidak ada, hubungi host.

## 2. Siapkan Database di cPanel

1. Buka **cPanel → MySQL Databases**
2. Buat database baru, misal `dompetku_db`
3. Buat user baru + **set semua privileges** untuk database itu
4. Catat: nama database, user, dan password

## 3. Upload & Extract

File `dompetku-deploy.zip` (44 MB) sudah berisi seluruh aplikasi **termasuk `vendor/` dan asset hasil build**, jadi **tidak perlu** menjalankan composer di server.

1. Login cPanel → **File Manager** → masuk ke home directory
2. Buat folder, misal `dompetku`, lalu upload `dompetku-deploy.zip` ke dalamnya
   > File 44 MB — kalau File Manager gagal, gunakan **FTP** (FileZilla) dan upload ke `/home/<user>/dompetku/`
3. Klik kanan zip → **Extract**
4. Hapus file zip setelah extract

**Struktur folder yang disarankan:**
```
/home/<user>/dompetku/          ← seluruh project (di LUAR public_html)
    └── public/                 ← document root diarahkan ke sini
```

## 4. Arahkan Document Root ke Folder `public`

Ini cara paling bersih & aman (file sensitif tidak terekspos).

1. cPanel → **Domains** → klik **Manage** pada domain kamu
2. Pada **Document Root**, ubah menjadi: `/home/<user>/dompetku/public`
3. Save

> Kalau host-mu tidak mengizinkan ubah document root: upload project ke `public_html/dompetku/`, lalu buat file `.htaccess` di `public_html` yang me-redirect ke folder `public` (kurang disarankan).

## 5. Buat File `.env` (PENTING)

File `.env` **tidak** ikut di-zip (sengaja — demi keamanan, kunci & kredensial tidak boleh bocor).

1. Di File Manager, buka folder `dompetku` → salin `.env.example` → rename menjadi `.env`
2. Klik kanan `.env` → **Edit** → isi minimal:

```ini
APP_NAME="DompetKu"
APP_ENV=production
APP_KEY=            # diisi dengan perintah key:generate (langkah 6)
APP_DEBUG=false
APP_URL=https://domain-kamu.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dompetku_db
DB_USERNAME=dompetku_user
DB_PASSWORD=password_database_kamu
```

## 6. Jalankan Perintah Terminal

Buka **cPanel → Terminal** (atau SSH), lalu arahkan ke folder project:

```bash
cd ~/dompetku

# Buat kunci aplikasi (menghasilkan APP_KEY)
php artisan key:generate

# Buat tabel database
php artisan migrate --force

# Symlink folder upload bukti (storage/app/public -> public/storage)
php artisan storage:link

# Optimasi produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> `storage:link` wajib dijalankan, kalau tidak gambar bukti transaksi tidak tampil.

## 7. Permissions

Pastikan folder berikut bisa ditulis oleh web server:

```bash
chmod -R 775 storage bootstrap/cache
```

## 8. Verifikasi

Buka `https://domain-kamu.com` — harusnya diarahkan ke halaman login. Coba:

- ✅ Registrasi & login
- ✅ Tambah transaksi + upload bukti (cek gambarnya tampil)
- ✅ Export PDF & Excel
- ✅ Set anggaran bulanan

## Catatan Penting

- **Data lama**: kalau ada transaksi di database lokal, ekspor dari localhost (mysqldump / phpMyAdmin) lalu import ke database cPanel — jangan mulai dari migrasi kosong.
- **JANGAN timpa `app/Providers/AppServiceProvider.php` di server** — file itu punya kustomisasi khusus cPanel (`usePublicPath` ke folder docroot subdomain + arah disk `public` ke `keuangan.almahir.cloud/storage`). Versi lokal TIDAK punya kustomisasi ini; kalau ketimpa, upload bukti transaksi akan 404.
- **`.htaccess` di docroot subdomain** (bukan `public/.htaccess`) sudah berisi optimasi: kompresi Brotli/gzip, cache browser 1 tahun untuk aset build, dan header keamanan. Jangan timpa dengan versi default.
- **HTTPS**: pastikan SSL aktif; kalau `.htaccess` default tidak memaksa HTTPS, gunakan URL `https://...` langsung.
- **Update berikutnya**: cukup upload ulang folder `app/`, `routes/`, `resources/`, `public/build` (dan `composer.lock` jika ada perubahan dependency), lalu jalankan `php artisan optimize:clear` lalu `php artisan optimize`.
- **Jangan pernah meng-upload `.env` dari localhost** ke server — selalu buat yang baru.

## Cara Update Aplikasi (Tanpa Upload Ulang Penuh)

Kalau aplikasi sudah pernah di-deploy dan hanya ada perubahan kecil, upload **zip update** (`dompetku-update.zip`) yang berisi file yang berubah saja:

1. Upload `dompetku-update.zip` ke folder project (misal `~/dompetku`) via File Manager atau FTP
2. Extract — file lama yang sama akan tertimpa otomatis
3. Buka **cPanel Terminal**, lalu jalankan:

```bash
cd ~/dompetku

# Daftarkan class baru (misal DashboardController) ke autoloader
composer dump-autoload

# Bersihkan cache lama (config, route, view) — WAJIB setelah ada perubahan
php artisan optimize:clear

# Buat cache produksi baru
php artisan optimize
```

> **Kalau ada migration baru** (file baru di `database/migrations/`), tambahkan juga `php artisan migrate --force`.

## Alternatif: Deployment via Git

Kalau cPanel-mu punya fitur **Git Version Control**:

1. Di cPanel Git Version Control, clone repo kamu ke `/home/<user>/dompetku`
2. Sama seperti langkah 4–7, tapi sebelum migrasi jalankan:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Update kode selanjutnya cukup `git pull` di cPanel + `php artisan optimize:clear`
