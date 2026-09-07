# DompetKu — Aplikasi Keuangan Pribadi

Aplikasi web untuk mencatat pemasukan & pengeluaran, mengatur anggaran bulanan, melihat grafik tren, export laporan PDF/Excel, dan bertanya ke asisten AI.

Dibangun dengan **Laravel 13 + Breeze + Tailwind CSS + Alpine.js + Chart.js + Vite**.

## Fitur Utama

- **Transaksi CRUD** — judul, kategori, nominal (support format `Rp 1.500.000`), tipe income/expense, tanggal, upload bukti (JPEG/PNG, otomatis dioptimasi)
- **Dashboard** — total saldo, pemasukan, pengeluaran, grafik tren (minggu/bulan/tahun), donat per kategori, transaksi terakhir
- **Filter & Pencarian** — cari judul, filter tipe/kategori/periode, pagination
- **Anggaran Bulanan** — set batas belanja, progress bar, sisa/hari, peringatan over-budget
- **Export Laporan** — PDF (DomPDF) & Excel (Maatwebsite), mengikuti filter aktif
- **AI Assistant (DompetKu AI)** — chat keuangan berbasis data nyata user, deteksi niat transaksi + konfirmasi aman anti-duplikat (via KiosAPI OpenAI-compatible)
- **Voice Input** — parser lokal Bahasa Indonesia (`25 ribu`, `5 juta`, `Rp 25.000`, bahkan `dua puluh lima ribu`) tanpa perlu API
- **UI Profesional** — dark/light mode, privacy toggle saldo, skeleton loading, responsif mobile, SEO meta + PWA manifest

## Tech Stack

- Backend: PHP 8.3, Laravel 13, Eloquent ORM
- Frontend: Blade, Tailwind CSS 3, Alpine.js, Chart.js 4, Vite
- Auth: Laravel Breeze (Blade)
- Laporan: `barryvdh/laravel-dompdf`, `maatwebsite/excel`
- AI: `KiosAPI` (`deepseek-v4-flash`, OpenAI-compatible) + fallback parser lokal `App\Services\TransactionParser`
- Database: SQLite (development) / MySQL (production cPanel)
- Testing: PHPUnit 12 (`tests/Feature`, `tests/Unit`)

## Cara Jalan di Lokal

```bash
# 1. Install dependency
composer install
npm install

# 2. Siapkan .env
cp .env.example .env
php artisan key:generate

# 3. Migrasi database (default SQLite)
php artisan migrate

# 4. Jalanin dev server + vite (atau: composer dev)
php artisan serve
npm run dev
```

Atau sekali jalan:

```bash
composer setup   # install + key + migrate + build
composer dev     # serve + queue + pail + vite
```

Buka: `http://localhost:8000`

## Konfigurasi .env Penting

```ini
APP_NAME="DompetKu"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

# AI Assistant (wajib isi biar chat AI jalan, daftar di kiosapi.com)
KIOSAPI_API_KEY=
KIOSAPI_BASE_URL=https://kiosapi.com/v1/chat/completions
KIOSAPI_MODEL=deepseek-v4-flash
```

Tanpa `KIOSAPI_API_KEY`, chat AI akan balas pesan ramah `belum dikonfigurasi` — aplikasi tetap jalan normal.

## Testing

```bash
php artisan test
# atau
composer test
```

Test mencakup: auth, transaksi (scope per-user), budget, parser voice, AI chat/confirm/cancel.

## Struktur Penting

```
app/Http/Controllers/  -> TransactionController, BudgetController, AiController
app/Services/          -> ReportingService, FinancialContextBuilder, AiAssistantService, TransactionParser, AmountFormatter
app/Models/            -> Transaction, Budget, User
routes/web.php         -> landing, transactions resource, budgets, ai, profile
resources/views/       -> transactions/index, ai/index, landing, layouts
database/migrations/   -> users, transactions, budgets + index optimasi
```

## Deploy ke cPanel

Lihat panduan lengkap: [`DEPLOY-CPANEL.md`](DEPLOY-CPANEL.md)

Intinya: arahkan document root ke `public/`, buat `.env` produksi baru (jangan upload `.env` lokal), jalankan `migrate --force` + `storage:link` + `optimize`.

## Catatan Keamanan

- Semua data transaksi di-scope per `user_id` — user tidak bisa akses data user lain
- Konfirmasi AI wajib cocok field-for-field dengan kandidat server (anti tembak API langsung)
- Upload hanya `jpeg,png,jpg` max 20MB, disimpan di `storage/app/public/receipts`
- Jangan pernah commit `.env` asli ke git (sudah di-`.gitignore`)

## Roadmap

- [x] Rate-limit `/ai/chat` (throttle 30/menit + pesan 429 ramah Indonesia)
- [x] Validasi `storeTransactions` (cek kategori vs tipe + simpan atomik)
- [x] Export Excel scope per-user eksplisit + enkripsi session
- [ ] Soft-delete transaksi + riwayat aktivitas
- [ ] Retry/circuit-breaker AI + timeout lebih pendek
- [ ] Kategori custom per user
- [ ] CI: Pint + PHPUnit + build check
