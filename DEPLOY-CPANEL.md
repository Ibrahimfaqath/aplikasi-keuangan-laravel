# Panduan Deployment dompetku ke cPanel

Panduan ini untuk men-deploy aplikasi **dompetku** (Laravel 13) ke hosting cPanel.

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
APP_KEY=base64:...   # WAJIB beda dari kunci lokal — generate pakai `php artisan key:generate --show`
APP_DEBUG=false
APP_URL=https://finance.almahir.cloud

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=almahir_keuangan
DB_USERNAME=almahir_user
DB_PASSWORD=password_database_kamu
```

> `APP_DEBUG=false` wajib di produksi. Key harus unik per environment.

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

- **Lokasi live yang BENAR (jangan tertukar):**
  - FTP root akun ini = `/home3/almahir/ibrahim_projects` (jail FTP).
  - `/home3/almahir/ibrahim_projects/finance.almahir.cloud/` = **docroot subdomain** (front controller + listen ke Laravel). Berisi `build/` (aset yang dilayani `<link href="/build/...">`) dan `storage/` (bukti transaksi).
  - `/home3/almahir/ibrahim_projects/laravel_finance/` = **root project Laravel** yang dipakai (`.env`, `vendor/`, `storage/`).
  - Bukti: `index.php` docroot me-load `../laravel_finance/vendor/autoload.php` dan `../laravel_finance/bootstrap/app.php`.
  - Di FTP, path relatifnya adalah `/finance.almahir.cloud` dan `/laravel_finance`.
  - ⚠️ Folder `home3/almahir/ibrahim_projects/` di dalam FTP root adalah **copy lama / sampah deploy** dan tidak dipakai subdomain. Jangan deploy ke sana.
- **Upload bukti transaksi**: disk `public` diarahkan langsung ke folder docroot `/finance.almahir.cloud/storage` lewat `app/Providers/AppServiceProvider.php` (aktif hanya saat `APP_ENV=production`). Jadi `storage:link` **tidak diperlukan** dan tidak akan melayani upload. Pastikan folder `/finance.almahir.cloud/storage` bisa ditulis web server.
- **`app/Providers/AppServiceProvider.php` kini ikut di-deploy** (tidak lagi di-exclude di `deploy.yml`) — isinya aman untuk lokal maupun produksi karena konfigurasi storage docroot hanya aktif ketika `APP_ENV=production`.
- **`APP_DEBUG=false`** di produksi. `APP_KEY` produksi harus **berbeda dari lokal** (jangan pakai key yang sama dengan `.env` development).
- **Aset build** hasil `npm run build` harus identik di dua tempat: `/laravel_finance/public/build/` (untuk resolve manifest) dan `/finance.almahir.cloud/build/` (yang dilayani web). Workflow deploy meng-upload keduanya (docroot pakai `--delete`).
- **`.htaccess`** yang dipakai web ada di docroot subdomain (`/finance.almahir.cloud/.htaccess`) dan berisi handler PHP cPanel `ea-php83`.
- **HTTPS**: pastikan SSL aktif; gunakan URL `https://finance.almahir.cloud` langsung.
- **Update berikutnya**: cukup upload ulang folder `app/`, `routes/`, `resources/`, `public/build` (dan `composer.lock` jika ada perubahan dependency), lalu jalankan `php artisan optimize:clear` lalu `php artisan optimize` (atau hapus manual `bootstrap/cache/*.php` + `storage/framework/views/*.php` lewat File Manager bila SSH tidak tersedia).
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

## Cara Memverifikasi Deploy Otomatis (GitHub Actions)

Kalau perubahan di repo ter-push tapi tidak tampil di live, cek dulu apakah `FTP_PATH` benar:

1. **Penanda unik di file ini**: baris berikut hanya ada di repo, bukan di server. Kalau setelah deploy otomatis penanda ini ADA di `/laravel_finance/DEPLOY-CPANEL.md`, berarti `FTP_PATH` sudah benar (mendarat di `/laravel_finance`).

   `VERIFIKASI-FTP_PATH-2026-09-14`

2. **Cek tanpa login**: buka tab **Actions** → run "Deploy to cPanel" terbaru → semua step harus hijau, termasuk *"Mirror build ke docroot + bersihkan cache Laravel (satu koneksi)"*.
3. **Cek mtime**: File Manager → `/laravel_finance/config/services.php` — mtime-nya harus mengikuti waktu run terakhir dan berisi teks `langchain`.
4. Kalau semuanya sudah, cukup **Re-run** workflow/`push` berikutnya akan otomatis menyinkronkan kode ke live.

## Alternatif: Deployment via Git

Kalau cPanel-mu punya fitur **Git Version Control**:

1. Di cPanel Git Version Control, clone repo kamu ke `/home/<user>/dompetku`
2. Sama seperti langkah 4–7, tapi sebelum migrasi jalankan:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Update kode selanjutnya cukup `git pull` di cPanel + `php artisan optimize:clear`
