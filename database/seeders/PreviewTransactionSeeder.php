<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh untuk review UI di mesin lokal.
 *
 * Sengaja TIDAK dipanggil dari DatabaseSeeder: file ini berisi data fiktif
 * milik satu akun pribadi, dan tidak boleh ikut ter-seed di server produksi.
 *
 * Cara pakai (email dibaca dari env, jadi tidak ada email pribadi yang
 * tersimpan di repo):
 *
 *   PREVIEW_EMAIL=... php artisan db:seed --class=PreviewTransactionSeeder
 *
 * Kalau akun itu sudah punya transaksi, seeder berhenti -- tidak menimpa
 * data yang sudah ada. Set PREVIEW_RESET=1 kalau memang mau mulai ulang.
 */
class PreviewTransactionSeeder extends Seeder
{
    /** [judul, kategori, nominal, tanggal] */
    private const INCOME = [
        ['Gaji Agustus 2026', 'Gaji', 12_500_000, '2026-08-25'],
        ['Gaji September 2026', 'Gaji', 12_500_000, '2026-09-25'],
        ['Bonus kinerja kuartal III', 'Bonus', 3_750_000, '2026-09-12'],
        ['Proyek desain identitas merek', 'Bisnis', 2_500_000, '2026-09-08'],
        ['Konsultasi keuangan untuk UKM', 'Bisnis', 1_800_000, '2026-08-18'],
        ['Dividen reksa dana negara', 'Investasi', 640_000, '2026-08-12'],
        ['HadiahUltah keponakan', 'Hadiah', 500_000, '2026-08-05'],
        ['Penjualan buku bekas', 'Lainnya', 275_000, '2026-07-26'],
    ];

    private const EXPENSE = [
        // -- Juli 2026 --------------------------------------------------------
        ['Listrik PLN bulan Juli', 'Tagihan & Utilitas', 352_000, '2026-07-05'],
        ['Internet rumah', 'Tagihan & Utilitas', 349_000, '2026-07-05'],
        ['PDAM', 'Tagihan & Utilitas', 165_000, '2026-07-06'],
        ['Belanja bulanan supermarket', 'Belanja', 1_180_000, '2026-07-03'],
        ['Baju dan sepatu kerja', 'Belanja', 620_000, '2026-07-14'],
        ['Bensin pertalite', 'Transportasi', 350_000, '2026-07-08'],
        ['Ojek online', 'Transportasi', 64_000, '2026-07-16'],
        ['Makan di luar', 'Makanan & Minuman', 185_000, '2026-07-18'],
        ['Groceries bulanan', 'Makanan & Minuman', 780_000, '2026-07-25'],
        ['Kontribusi untuk orang tua', 'Keluarga', 750_000, '2026-07-20'],
        ['Langganan Netflix dan Spotify', 'Hiburan', 186_000, '2026-07-10'],
        ['Obat dan vitamin', 'Kesehatan', 235_000, '2026-07-22'],
        ['Kursus online', 'Pendidikan', 499_000, '2026-07-13'],

        // -- Agustus 2026 ------------------------------------------------------
        ['Listrik PLN bulan Agustus', 'Tagihan & Utilitas', 371_000, '2026-08-05'],
        ['Internet rumah', 'Tagihan & Utilitas', 349_000, '2026-08-05'],
        ['PDAM', 'Tagihan & Utilitas', 172_000, '2026-08-06'],
        ['Belanja bulanan supermarket', 'Belanja', 1_240_000, '2026-08-03'],
        ['Meja kerja dan kursi ergonomis', 'Belanja', 890_000, '2026-08-16'],
        ['Bensin dan tune-up motor', 'Transportasi', 420_000, '2026-08-09'],
        ['Ojek online', 'Transportasi', 58_000, '2026-08-21'],
        ['Makan siang kantor', 'Makanan & Minuman', 135_000, '2026-08-24'],
        ['Groceries bulanan', 'Makanan & Minuman', 810_000, '2026-08-28'],
        ['Beli hadiah keponakan', 'Keluarga', 320_000, '2026-08-22'],
        ['Membership gym', 'Hiburan', 250_000, '2026-08-14'],
        ['Konsultasi dokter', 'Kesehatan', 300_000, '2026-08-19'],
        ['Buku kerja dan stationary', 'Pendidikan', 275_000, '2026-08-07'],
        ['Setoran tabungan', 'Lainnya', 1_000_000, '2026-08-30'],

        // -- September 2026 ---------------------------------------------------
        ['Listrik PLN bulan September', 'Tagihan & Utilitas', 385_000, '2026-09-05'],
        ['Internet rumah', 'Tagihan & Utilitas', 349_000, '2026-09-05'],
        ['PDAM', 'Tagihan & Utilitas', 178_000, '2026-09-06'],
        ['Belanja bulanan supermarket', 'Belanja', 1_250_000, '2026-09-03'],
        ['Kemeja kantor', 'Belanja', 450_000, '2026-09-24'],
        ['Bensin pertalite', 'Transportasi', 300_000, '2026-09-11'],
        ['Ojek online', 'Transportasi', 72_000, '2026-09-23'],
        ['Makan siang kantor', 'Makanan & Minuman', 135_000, '2026-09-23'],
        ['Groceries bulanan', 'Makanan & Minuman', 795_000, '2026-09-29'],
        ['Kontribusi untuk orang tua', 'Keluarga', 750_000, '2026-09-20'],
        ['Nonton film di bioskop', 'Hiburan', 90_000, '2026-09-19'],
        ['Obat dan suplemen', 'Kesehatan', 185_000, '2026-09-15'],
        ['Setoran tabungan', 'Lainnya', 1_000_000, '2026-09-30'],

        // -- Oktober 2026 (awal bulan, sesuai "hari ini") ----------------------
        ['Kopi susu dan roti sarapan', 'Makanan & Minuman', 38_000, '2026-10-01'],
        ['Bensin pertalite', 'Transportasi', 100_000, '2026-10-01'],
    ];

    public function run(): void
    {
        $email = env('PREVIEW_EMAIL');

        if (! $email) {
            $this->command?->warn('PREVIEW_EMAIL belum diisi — seeder dilewati.');

            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->command?->error("Akun {$email} tidak ditemukan.");

            return;
        }

        $lama = Transaction::where('user_id', $user->id)->count();

        if ($lama > 0 && ! env('PREVIEW_RESET')) {
            $this->command?->warn("Akun {$email} sudah punya {$lama} transaksi — dibiarkan. Set PREVIEW_RESET=1 untuk mengulang.");

            return;
        }

        if ($lama > 0) {
            Transaction::where('user_id', $user->id)->delete();
            Budget::where('user_id', $user->id)->delete();
        }

        $now = now();
        $rows = [];

        // Tipe transaksi di model masih berupa string biasa ('income'/'expense'),
        // belum punya konstanta -- jadi ditulis literal di sini.
        foreach (['income' => self::INCOME, 'expense' => self::EXPENSE] as $type => $items) {
            foreach ($items as [$judul, $kategori, $nominal, $tanggal]) {
                $rows[] = [
                    'user_id' => $user->id,
                    'title' => $judul,
                    'category' => $kategori,
                    'amount' => $nominal,
                    'type' => $type,
                    'transaction_date' => $tanggal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Dipecah per 20 baris supaya satu PreparedStatement tidak terlalu panjang.
        foreach (array_chunk($rows, 20) as $chunk) {
            Transaction::query()->insert($chunk);
        }

        // Anggaran bulan berjalan supaya kartu "Anggaran" di dashboard ikut terisi.
        $bulan = Carbon::now();
        $anggaran = [
            ['Makanan & Minuman', 2_500_000],
            ['Transportasi', 1_500_000],
            ['Tagihan & Utilitas', 1_200_000],
            ['', 8_000_000],
        ];

        foreach ($anggaran as [$kategori, $nominal]) {
            Budget::query()->create([
                'user_id' => $user->id,
                'amount' => $nominal,
                'month' => $bulan->month,
                'year' => $bulan->year,
                'category' => $kategori,
            ]);
        }

        $this->command?->info(sprintf(
            '%d transaksi + %d anggaran untuk %s',
            count($rows),
            count($anggaran),
            $email
        ));
    }
}
