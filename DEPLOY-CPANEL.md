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
- **`app/Providers/AppServiceProvider.php` ikut di-deploy** — isinya aman untuk lokal maupun produksi karena konfigurasi storage docroot hanya aktif ketika `APP_ENV=production`.
- **`APP_DEBUG=false`** di produksi. `APP_KEY` produksi harus **berbeda dari lokal** (jangan pakai key yang sama dengan `.env` development).
- **Aset build** hasil `npm run build` harus identik di dua tempat: `/laravel_finance/public/build/` (untuk resolve manifest `@vite`) dan `/finance.almahir.cloud/build/` (yang dilayani web). Keduanya harus di-mirror manual via FTP (docroot pakai `--delete`), karena cPanel Git Deploy tidak menyentuh docroot.
- **`.htaccess`** yang dipakai web ada di docroot subdomain (`/finance.almahir.cloud/.htaccess`) dan berisi handler PHP cPanel `ea-php83`.
- **HTTPS**: pastikan SSL aktif; gunakan URL `https://finance.almahir.cloud` langsung.
- **Update berikutnya**: upload ulang folder yang berubah (`app/`, `routes/`, `resources/`, `config/`, `database/seeders/`, `public/build`, dan `composer.lock` jika dependency berubah), lalu **mirror `public/build` ke docroot** `/finance.almahir.cloud/build` juga. Detail lengkap ada di bagian **Cara Update Aplikasi (Tanpa Upload Ulang Penuh)** di bawah.
- **Jangan pernah meng-upload `.env` dari localhost** ke server — selalu buat yang baru.

## Cara Update Aplikasi (Tanpa Upload Ulang Penuh)

Deploy otomatis GitHub → cPanel **tidak aktif** di server ini (tidak ada `.git` di `/laravel_finance`), jadi update dilakukan lewat **FTP manual**:

1. Build aset produksi di lokal:
   ```bash
   npm run build
   ```
2. Upload file yang berubah ke `/laravel_finance` (folder `app/`, `routes/`, `resources/`, `config/`, `database/seeders/`, `tests/`, `public/build/`, dll). **Jangan pernah upload `.env`.**
3. Mirror aset build ke **dua** lokasi:
   - `/laravel_finance/public/build/` — untuk resolve manifest `@vite`
   - `/finance.almahir.cloud/build/` — yang dilayani web; pakai `--delete` agar aset lama terhapus
4. Bersihkan cache di server. Kalau **cPanel Terminal/SSH** tersedia:
   ```bash
   cd /home3/almahir/ibrahim_projects/laravel_finance
   php artisan optimize:clear
   php artisan optimize
   ```
   Kalau tidak ada Terminal, hapus manual via File Manager:
   - `bootstrap/cache/*.php` (kecuali `.gitignore`)
   - `storage/framework/views/*.php` (kecuali `.gitignore`)
   - lalu panggil `https://finance.almahir.cloud/__flush.php` untuk `opcache_reset()`
5. **Kalau ada migration baru** (file baru di `database/migrations/`), jalankan `php artisan migrate --force` lewat Terminal.
6. **Kalau ada seeder baru** (mis. `DemoDataSeeder`), jalankan `php artisan db:seed --class=DemoDataSeeder`.

> **Seeder tanpa SSH**: dua opsi yang sudah terbukti. (a) Upload skrip PHP sementara ber-token acak ke docroot yang meng-`require` `../laravel_finance/vendor/autoload.php`, bootstrap `../laravel_finance/bootstrap/app.php`, panggil `Artisan::call('db:seed', ['--class' => '...', '--force' => true])`, lalu **hapus dirinya sendiri** — segera hapus juga via FTP sebagai cadangan. (b) Tambahkan task seeder ke `.cpanel.yml` lalu klik **Deploy** di cPanel Git Version Control (hanya jika fitur itu aktif).

> **Catatan path**: `index.php` docroot memuat `../laravel_finance/vendor/autoload.php`, jadi `bootstrap/app.php` menetapkan base path ke `/laravel_finance` — skrip bantuan di docroot tetap bisa bootstrap Laravel dengan benar.

## Verifikasi Setelah Deploy (FTP)

1. **Aset baru terlayani**: `curl https://finance.almahir.cloud/build/manifest.json` harus menampilkan hash yang sama dengan `public/build/manifest.json` lokal.
2. **Halaman live**: `/` dan `/login` balas HTTP 200 dan memuat nama file aset terbaru (mis. `app-XXXXXXXX.css`), bukan hash lama.
3. **Fitur baru berjalan**: uji alur terkait (mis. tombol "Coba Demo" di `/login` → redirect ke `/transactions` + badge "Mode Demo").
4. **Cache bersih**: pastikan `storage/framework/views/` hanya berisi `.gitignore` sebelum request pertama pasca-deploy.
5. **Git sinkron**: `git log --oneline -1 origin/main` menunjuk commit yang di-deploy (untuk jejak perubahan).

## Alternatif: Deployment via Git (cPanel Git Version Control)

Server ini **belum** mengaktifkan cPanel Git Version Control (tidak ada `.git` di `/laravel_finance`), sehingga `.cpanel.yml` **tidak berjalan otomatis** saat `git push`. Kalau nanti diaktifkan:

1. Hubungkan repo di cPanel → Git Version Control, lalu set `DEPLOYPATH=/laravel_finance` (sudah ada di `.cpanel.yml`).
2. `.cpanel.yml` sudah memuat task: copy file ke `$DEPLOYPATH`, jaga `.env` yang ada, bersihkan compiled view + cache, chmod `storage`/`bootstrap/cache`, dan `db:seed --class=DemoDataSeeder` (idempoten — aman diulang).
3. Aset `public/build` tetap harus di-mirror manual ke docroot `/finance.almahir.cloud/build` karena Git Deploy tidak menyentuh docroot.
4. Untuk update berikutnya cukup `git push` + klik **Deploy** (atau aktifkan auto-deploy).
