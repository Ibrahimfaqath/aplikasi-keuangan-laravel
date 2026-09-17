<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Benih data realistis (deterministik) untuk akun demo publik.
     * Idempotent: bisa dijalankan berulang tanpa menduplikasi data.
     */
    public function run(): void
    {
        Model::unguard();

        $email = config('demo.email');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('demo.name'),
                'password' => bcrypt(config('demo.password')),
                'email_verified_at' => now(),
            ]
        );

        $required = $this->requiredVariations();
        $seedDay = [1 => 3, 4 => 16, 9 => 24];
        $now = now();

        $ids = [];

        for ($back = 5; $back >= 0; $back--) {
            $monthStart = $now->copy()->subMonthsNoOverflow($back)->startOfMonth();

            foreach ($required as $title => $data) {
                [$type, $category, $vary] = $data;

                // Hari ditentukan dari hash title+bulan (bukan random global)
                // agar hasil re-run identik (idempotent).
                $day = abs(crc32($title.$monthStart->year.$monthStart->month)) % $monthStart->daysInMonth + 1;
                $day = min($day, 28);

                if ($monthStart->copy()->addDays($day - 1)->isFuture()) {
                    continue;
                }

                $skipBase = $vary && $day % 5 === 0;
                $date = $monthStart->copy()->addDays($day - 1);
                $id = (string) $monthStart->year
                    .str_pad((string) $monthStart->month, 2, '0', STR_PAD_LEFT)
                    .$title;

                if ($skipBase) {
                    $id .= '-0';
                }

                if (isset($ids[$id])) {
                    continue;
                }

                $ids[$id] = true;

                $amount = $this->amountFor($title, $type, $category, $vary, $date, $monthStart, $day, $seedDay);

                // Idempotensi dikunci (user_id, title, tanggal) — bukan amount,
                // karena kolom DECIMAL(15,2) disimpan sebagai string ("1900000.00")
                // sehingga pencocokan jumlah selalu gagal pada run kedua.
                Transaction::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'title' => $title,
                        'transaction_date' => $date->toDateString(),
                    ],
                    [
                        'category' => $category,
                        'type' => $type,
                        'amount' => $amount,
                    ]
                );
            }
        }

        $this->seedBudgets($user);

        $this->command?->info('Data demo untuk '.$email.' berhasil di-seed.');
    }

    /**
     * Item bulanan: [title, type, kategori, apakah nominalnya bervariasi antar bulan]
     */
    private function requiredVariations(): array
    {
        return [
            'Gaji Bulan Ini' => ['income', 'Gaji', false],
            'Pendapatan Online' => ['income', 'Bisnis', true],
            'Bonus Kinerja' => ['income', 'Bonus', true],
            'Cuan Investasi Reksadana' => ['income', 'Investasi', true],
            'Tukang Parkir' => ['expense', 'Lainnya', true],
            'Kontrakan' => ['expense', 'Tagihan & Utilitas', false],
            'Internet' => ['expense', 'Tagihan & Utilitas', false],
            'Listrik' => ['expense', 'Tagihan & Utilitas', true],
            'Streaming' => ['expense', 'Hiburan', false],
            'BPJS Kesehatan' => ['expense', 'Kesehatan', false],
            'Nasi Padang' => ['expense', 'Makanan & Minuman', true],
            'Makan Siang Kantor' => ['expense', 'Makanan & Minuman', true],
            'Ngopi Bareng' => ['expense', 'Makanan & Minuman', true],
            'Beli Sayur & Buah' => ['expense', 'Makanan & Minuman', true],
            'Ojek Online' => ['expense', 'Transportasi', true],
            'Bensin Motor' => ['expense', 'Transportasi', true],
            'Tol & Parkir' => ['expense', 'Transportasi', true],
            'Belanja Sembako' => ['expense', 'Belanja', true],
            'Belanja Online' => ['expense', 'Belanja', true],
            'Sabun & Detergen' => ['expense', 'Belanja', true],
            'Nonton Bioskop' => ['expense', 'Hiburan', true],
            'Kuota & Paket Data' => ['expense', 'Hiburan', true],
            'Obat & Vitamin' => ['expense', 'Kesehatan', true],
            'UMKM Binaan' => ['expense', 'Keluarga', true],
            'Kado untuk Orang Tua' => ['expense', 'Keluarga', true],
            'Kursus Online' => ['expense', 'Pendidikan', true],
            'Konsultasi Tanya Dokter' => ['expense', 'Kesehatan', true],
            'Tambal Ban' => ['expense', 'Transportasi', true],
            'Laundry' => ['expense', 'Keluarga', true],
        ];
    }

    private function amountFor(
        string $title,
        string $type,
        string $category,
        bool $vary,
        Carbon $date,
        Carbon $monthStart,
        int $day,
        array $seedDay
    ): int {
        // Nominal tidak tergantung tanggal, hanya title + bulan/year → stabil per bulan.
        mt_srand((int) ($monthStart->year * 100 + $monthStart->month).crc32($title));

        try {
            $amount = match (true) {
                $title === 'Gaji Bulan Ini' => 1500000 + $monthStart->month * 100000,
                $title === 'Pendapatan Online' => $vary ? mt_rand(250000, 750000) : 0,
                $title === 'Bonus Kinerja' => $vary && $day % 3 === 0 ? mt_rand(400000, 900000) : 0,
                $title === 'Cuan Investasi Reksadana' => $vary && $day % 2 === 0 ? mt_rand(80000, 350000) : 0,
                $title === 'Tukang Parkir' => $day > 20 ? 4000 : 0,
                $title === 'Kontrakan' => $day === 1 ? 2000000 : 0,
                $title === 'Internet' => $day === 5 ? 450000 : 0,
                $title === 'Listrik' => $vary ? ($day % 30 === 0 ? mt_rand(280000, 560000) : 0) : 0,
                $title === 'Streaming' => $day === 7 ? 120000 : 0,
                $title === 'BPJS Kesehatan' => $day === 10 ? 610000 : 0,
                $title === 'Nasi Padang' => ($day % 4 !== 0 && $day < 28) ? mt_rand(25000, 50000) : 0,
                $title === 'Makan Siang Kantor' => $day < 25 ? 25000 : 0,
                $title === 'Ngopi Bareng' => ($day % 4 !== 0 && $day % 5 !== 0) ? mt_rand(40000, 90000) : 0,
                $title === 'Beli Sayur & Buah' => $day !== 14 && $day !== 27 ? mt_rand(50000, 130000) : 0,
                $title === 'Ojek Online' => ($day % 3 !== 0 && $day < 29) ? mt_rand(18000, 42000) : 0,
                $title === 'Bensin Motor' => $day % 4 === 0 ? mt_rand(50000, 100000) : 0,
                $title === 'Tol & Parkir' => $day < 28 ? ($day % 6 === 0 ? mt_rand(15000, 45000) : 0) : 0,
                $title === 'Belanja Sembako' => in_array($day, [1, 8, 15, 22], true) ? mt_rand(120000, 280000) : 0,
                $title === 'Belanja Online' => $day % 9 === 0 ? mt_rand(100000, 350000) : 0,
                $title === 'Sabun & Detergen' => $day === 18 ? mt_rand(65000, 120000) : 0,
                $title === 'Nonton Bioskop' => $day % 10 === 1 ? mt_rand(80000, 150000) : 0,
                $title === 'Kuota & Paket Data' => $day % 6 === 0 ? mt_rand(50000, 120000) : 0,
                $title === 'Obat & Vitamin' => $day === 11 || $day === 24 ? mt_rand(30000, 90000) : 0,
                $title === 'UMKM Binaan' => $day === 2 ? mt_rand(100000, 400000) : 0,
                $title === 'Kado untuk Orang Tua' => $day === 20 ? mt_rand(150000, 600000) : 0,
                $title === 'Kursus Online' => $day === 12 ? mt_rand(180000, 450000) : 0,
                $title === 'Konsultasi Tanya Dokter' => $day % 15 === 0 ? mt_rand(70000, 150000) : 0,
                $title === 'Tambal Ban' => ($day % 11 === 0 && $day < 28) ? mt_rand(15000, 40000) : 0,
                $title === 'Laundry' => $day === 25 ? mt_rand(60000, 110000) : 0,
                default => 0,
            };
        } finally {
            mt_srand((int) ($seedDay[$day] ?? 1) * 100);
        }

        return (int) round($amount / 10) * 10;
    }

    private function seedBudgets(User $user): void
    {
        $now = now();

        $currentExpense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [
                $now->startOfMonth()->toDateString(),
                $now->endOfMonth()->toDateString(),
            ])
            ->sum('amount');

        Budget::updateOrCreate(
            [
                'user_id' => $user->id,
                'month' => (int) $now->month,
                'year' => (int) $now->year,
            ],
            ['amount' => max(2000000, (int) round($currentExpense / 0.8 / 1000) * 1000)]
        );

        foreach ([1, 2, 3] as $back) {
            $month = $now->copy()->subMonthsNoOverflow($back);

            $expense = Transaction::where('user_id', $user->id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [
                    $month->startOfMonth()->toDateString(),
                    $month->endOfMonth()->toDateString(),
                ])
                ->sum('amount');

            Budget::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month' => (int) $month->month,
                    'year' => (int) $month->year,
                ],
                ['amount' => max(2000000, (int) round($expense * 1.1 / 1000) * 1000)]
            );
        }
    }
}
