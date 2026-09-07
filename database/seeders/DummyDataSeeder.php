<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder DATA DUMMY khusus development/testing.
 *
 * - Target akun: ibrahimfaqath@gmail.com
 * - Password hanya di-set saat akun BARU dibuat (tidak pernah mengganti password akun yang sudah ada).
 * - Hanya mempengaruhi data milik akun tersebut.
 * - Idempotent: boleh dijalankan berulang tanpa menggandakan transaksi/budget.
 *
 * Catatan: budget aplikasi ini hanya menyimpan SATU batas per (bulan, tahun) per user,
 * bukan per kategori. Seeder membuat batas untuk bulan berjalan + bulan sebelumnya.
 */
class DummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private const TARGET_EMAIL = 'ibrahimfaqath@gmail.com';

    private const DUMMY_PASSWORD = '11111111';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::TARGET_EMAIL],
            [
                'name' => 'Ibrahim Faqath',
                'password' => Hash::make(self::DUMMY_PASSWORD),
                'email_verified_at' => Carbon::now(),
            ]
        );

        $this->seedTransactions($user);
        $this->seedBudgets($user);

        $transactionCount = Transaction::where('user_id', $user->id)->count();
        $budgetCount = Budget::where('user_id', $user->id)->count();

        $this->command?->info(sprintf(
            'Dummy data siap untuk %s (%d transaksi, %d budget).',
            $user->email,
            $transactionCount,
            $budgetCount
        ));
    }

    private function seedTransactions(User $user): void
    {
        foreach ($this->transactionSpecs() as $spec) {
            [$title, $type, $category, $amount, $offset] = $spec;

            $date = Carbon::today()->subDays($offset)->toDateString();

            Transaction::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => $title,
                    'type' => $type,
                    'category' => $category,
                    'amount' => $amount,
                    'transaction_date' => $date,
                ],
                [
                    'image' => null,
                ]
            );
        }
    }

    private function seedBudgets(User $user): void
    {
        $now = Carbon::now();

        Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $now->month,
                'year' => $now->year,
            ],
            ['amount' => 3500000]
        );

        $previous = $now->copy()->subMonthNoOverflow();

        Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => $previous->month,
                'year' => $previous->year,
            ],
            ['amount' => 6000000]
        );
    }

    /**
     * Spesifikasi data dummy. Item: [title, type, category, amount, days_ago].
     * Kategori hanya memakai konstanta yang tersedia di Transaction.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: int, 4: int}>
     */
    private function transactionSpecs(): array
    {
        return [
            // ---------- PEMASUKAN ----------
            ['Gaji bulanan utama',            'income',   'Gaji',             8900000, 7],
            ['Bonus kinerja kuartal',         'income',   'Bonus',            2500000, 3],
            ['Penjualan produk digital',      'income',   'Bisnis',           750000,  5],
            ['Jasa desain freelance',         'income',   'Bisnis',           1200000, 13],
            ['Jualan online',                 'income',   'Bisnis',           325000,  28],
            ['Dividen saham',                 'income',   'Investasi',        450000,  18],
            ['Kupon obligasi',                'income',   'Investasi',        250000,  23],
            ['Uang hadiah undian',            'income',   'Hadiah',           300000,  20],
            ['Komisi marketplace',            'income',   'Bisnis',           425000,  26],
            ['Uang saku bulanan',             'income',   'Lainnya',          600000,  6],
            ['Refund tiket',                  'income',   'Lainnya',          150000,  16],

            // ---------- PENGELUARAN ----------
            ['Sarapan + kopi pagi',           'expense',  'Makanan & Minuman',  28000, 0],
            ['Naik Gojek ke kantor',          'expense',  'Transportasi',       24000, 0],
            ['Belanja mingguan supermarket',  'expense',  'Belanja',            385000, 1],
            ['Nonton bioskop',                'expense',  'Hiburan',            90000,  1],
            ['Makan siang di resto',          'expense',  'Makanan & Minuman',  55000,  2],
            ['Bensin Pertamax',               'expense',  'Transportasi',       165000, 3],
            ['Obat vitamin',                  'expense',  'Kesehatan',          45000,  3],
            ['Tagihan listrik rumah',         'expense',  'Tagihan & Utilitas', 520000, 4],
            ['Internet WiFi bulanan',         'expense',  'Tagihan & Utilitas', 350000, 4],
            ['Cemilan sore',                  'expense',  'Makanan & Minuman',  35000,  5],
            ['Pulsa & kuota',                 'expense',  'Tagihan & Utilitas', 100000, 5],
            ['Uang saku adik',                'expense',  'Keluarga',           200000, 6],
            ['Makan malam keluarga',          'expense',  'Makanan & Minuman',  240000, 7],
            ['Makan siang warteg',            'expense',  'Makanan & Minuman',  32000,  8],
            ['Sarapan nasi uduk',             'expense',  'Makanan & Minuman',  20000,  9],
            ['Bayar tagihan rumah kontrakan', 'expense',  'Tagihan & Utilitas', 1500000, 10],
            ['Tol + parkir',                  'expense',  'Transportasi',       60000,  10],
            ['Beli buku teknik',              'expense',  'Pendidikan',         120000, 11],
            ['Langganan streaming',           'expense',  'Hiburan',            129000, 12],
            ['Kopi + pastry',                 'expense',  'Makanan & Minuman',  45000,  13],
            ['Beli kuota kerja',              'expense',  'Tagihan & Utilitas', 75000,  25],
            ['Belanja kebutuhan dapur',       'expense',  'Makanan & Minuman',  180000, 14],
            ['Konsultasi dokter gigi',        'expense',  'Kesehatan',          350000, 15],
            ['Nonton konser',                 'expense',  'Hiburan',            250000, 16],
            ['Kursus online',                 'expense',  'Pendidikan',         275000, 18],
            ['Baju olahraga',                 'expense',  'Belanja',            210000, 19],
            ['Donasi',                        'expense',  'Lainnya',            100000, 24],
            ['Snack kantor',                  'expense',  'Makanan & Minuman',  60000,  20],
            ['Tiket wisata keluarga',         'expense',  'Hiburan',            150000, 21],
            ['Makan bambu kota',              'expense',  'Makanan & Minuman',  120000, 21],
            ['Isi token listrik',             'expense',  'Tagihan & Utilitas', 150000, 22],
            ['Vitamin & suplemen',            'expense',  'Kesehatan',          95000,  23],
            ['Belanja bulanan',               'expense',  'Belanja',            620000, 24],
            ['Servis motor',                  'expense',  'Transportasi',       275000, 26],
            ['Es kopi kekinian',              'expense',  'Makanan & Minuman',  30000,  26],
            ['Pembayaran kartu kredit',       'expense',  'Tagihan & Utilitas', 400000, 27],
            ['Hadiah ulang tahun teman',      'expense',  'Keluarga',           150000, 27],
            ['Nasi padang lunch',             'expense',  'Makanan & Minuman',  40000,  28],
            ['Belanja sayur tradisional',     'expense',  'Makanan & Minuman',  75000,  29],
        ];
    }
}
