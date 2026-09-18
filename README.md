# dompetku — Aplikasi Keuangan Pribadi

Aplikasi web untuk mencatat pemasukan & pengeluaran, mengatur anggaran bulanan, melihat grafik tren, export laporan PDF/Excel, dan bertanya ke asisten AI.

Dibangun dengan **Laravel 13 + Breeze + Tailwind CSS + Alpine.js + Chart.js + Vite**.

## Fitur Utama

- **Transaksi CRUD** — judul, kategori, nominal (support format `Rp 1.500.000`), tipe income/expense, tanggal, upload bukti (JPEG/PNG, otomatis dioptimasi)
- **Dashboard** — total saldo, pemasukan, pengeluaran, grafik tren (minggu/bulan/tahun), donat per kategori, transaksi terakhir
- **Filter & Pencarian** — cari judul, filter tipe/kategori/periode, pagination
- **Anggaran Bulanan** — set batas belanja, progress bar, sisa/hari, peringatan over-budget
- **Export Laporan** — PDF (DomPDF) & Excel (Maatwebsite), mengikuti filter aktif
- **AI Assistant (dompetku AI)** — chat keuangan berbasis data nyata user, deteksi niat transaksi + konfirmasi aman anti-duplikat (via KiosAPI OpenAI-compatible atau service LangChain lokal)
- **Voice Input** — parser lokal Bahasa Indonesia (`25 ribu`, `5 juta`, `Rp 25.000`, bahkan `dua puluh lima ribu`) tanpa perlu API
- **UI Profesional** — dark/light mode, privacy toggle saldo, skeleton loading, responsif mobile, SEO meta + PWA manifest
- **Public Demo Account** — tombol "Coba Demo" di landing/login, masuk otomatis dengan data contoh, read-only (tidak bisa ubah data)

## Tech Stack

- Backend: PHP 8.3, Laravel 13, Eloquent ORM
- Frontend: Blade, Tailwind CSS 3, Alpine.js, Chart.js 4, Vite
- Auth: Laravel Breeze (Blade)
- Laporan: `barryvdh/laravel-dompdf`, `maatwebsite/excel`
- AI: `KiosAPI` (`agnes-2.5-flash`, OpenAI-compatible) atau service LangChain lokal (`langchain-svc/server.js`) + fallback parser lokal `App\Services\TransactionParser`
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

## Akun Demo Publik ("Coba Demo")

Landing & halaman login punya tombol **Coba Demo** untuk mencoba aplikasi tanpa daftar. Tombol login otomatis sebagai akun demo dan semua form-nya dikunci read-only — aman untuk dicoba siapa pun.

Siapkan data demonya (idempotent, bisa diulang):

```bash
php artisan db:seed --class=DemoDataSeeder
```

Atau ikut serta saat `db:seed` biasa dijalankan. Data mencakup transaksi ±6 bulan terakhir (gaji, tagihan, dll.) + anggaran bulan berjalan dan 3 bulan sebelumnya.

Konfigurasi ada di `.env`:

```ini
DEMO_MODE_ENABLED=true          # false = tombol & guard demo nonaktif
DEMO_ACCOUNT_EMAIL=demo@dompetku.app
DEMO_ACCOUNT_NAME="Demo User"
DEMO_ACCOUNT_PASSWORD=UbahIni2026!   # hanya dipakai seeder
```

Catatan:
- Akun demo hanyalah user biasa — semua query tetap di-scope per `user_id`, jadi data demo dan data user lain tidak pernah tercampur.
- Login demo dibatasi 10×/menit per IP (`throttle:demo`).
- Jika akun email demo sudah ada (mis. didaftarkan user asli), seeder tidak menimpa data mereka.

## Konfigurasi .env Penting

```ini
APP_NAME="DompetKu"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite

# AI Assistant
# Pilih salah satu jalur biar chat AI jalan:
# 1) Langsung ke KiosAPI (daftar di kiosapi.com) — isi KIOSAPI_API_KEY.
#    Model default agnes-2.5-flash, bisa diganti sesuai ketersediaan di KiosAPI.
KIOSAPI_API_KEY=
KIOSAPI_BASE_URL=https://kiosapi.com/v1/chat/completions
KIOSAPI_MODEL=agnes-2.5-flash

# 2) Atau pakai service LangChain lokal (langchain-svc/server.js):
#    biarkan KIOSAPI_API_KEY kosong dan set LANGCHAIN_SERVICE_URL.
#    Token wajib disamakan dengan LANGCHAIN_INTERNAL_TOKEN di langchain-svc/.env.
# LANGCHAIN_SERVICE_URL=http://127.0.0.1:8787
# LANGCHAIN_SERVICE_TOKEN=
```

Catatan: Model di `KIOSAPI_MODEL` hanyalah default dari `.env` — `AiAssistantService` memakainya sebagai model OpenAI-compatible. Jika keduanya (`KIOSAPI_API_KEY` & `LANGCHAIN_SERVICE_URL`) kosong, chat AI akan balas pesan ramah `belum dikonfigurasi` — aplikasi tetap jalan normal.

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

- [x] Rate-limit `/ai/*` + export (throttle 30/menit per-user + pesan 429 ramah Indonesia)
- [x] Validasi `storeTransactions` (cek kategori vs tipe + simpan atomik)
- [x] Export Excel scope per-user eksplisit + enkripsi session
- [ ] Soft-delete transaksi + riwayat aktivitas
- [ ] Retry/circuit-breaker AI + timeout lebih pendek
- [ ] Kategori custom per user
- [x] CI: Pint + PHPUnit + build check
