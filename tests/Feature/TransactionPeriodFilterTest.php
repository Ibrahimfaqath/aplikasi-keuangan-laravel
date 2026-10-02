<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filter periode di /transactions.
 *
 * Mostly penjaga regresi: definisi tiap periode dipindah dari switch inline
 * ke ReportingService::periodRange() supaya bisa dipakai kolom lain
 * (`deleted_at` di halaman Sampah). Test di sini mengunci bahwa pemindahan itu
 * tidak mengubah hasil di halaman aslinya.
 */
class TransactionPeriodFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Tanggal dikunci supaya seluruh test di file ini deterministik.
        // Tanpa ini, test yang bergantung pada POSISI HARI DALAM BULAN akan
        // lulus atau gagal tergantung tanggal test dijalankan -- persis yang
        // terjadi di test_period_7_days_keeps_open_ended_upper_bound (hanya
        // lulus tanggal 24-31).
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00'));

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeTransaction(string $title, string $date): Transaction
    {
        return Transaction::create([
            'user_id' => $this->user->id,
            'title' => $title,
            'type' => 'expense',
            'category' => 'Lainnya',
            'amount' => 10000,
            'transaction_date' => $date,
        ]);
    }

    /** @return array<int, string> judul transaksi yang lolos filter */
    private function titlesFor(array $filters): array
    {
        $query = (new ReportingService)
            ->getFilteredQuery($filters, $this->user->id);

        return $query->pluck('title')->all();
    }

    public function test_period_all_returns_everything(): void
    {
        $this->makeTransaction('LAMA', Carbon::now()->subYears(3)->toDateString());
        $this->makeTransaction('BARU', Carbon::now()->toDateString());

        $this->assertCount(2, $this->titlesFor(['period' => 'all']));
    }

    public function test_unknown_period_is_treated_as_all_instead_of_erroring(): void
    {
        $this->makeTransaction('LAMA', Carbon::now()->subYears(3)->toDateString());

        // Period dari URL tidak dipercaya; nilai ngawur harus diabaikan,
        // bukan membuat halaman 500.
        $this->assertCount(1, $this->titlesFor(['period' => 'nilai-ngawur']));
    }

    public function test_period_today_and_yesterday_are_disjoint(): void
    {
        $this->makeTransaction('HARI INI', Carbon::now()->toDateString());
        $this->makeTransaction('KEMARIN', Carbon::now()->subDay()->toDateString());

        $this->assertSame(['HARI INI'], $this->titlesFor(['period' => 'today']));
        $this->assertSame(['KEMARIN'], $this->titlesFor(['period' => 'yesterday']));
    }

    public function test_period_7_days_includes_today_and_does_not_cut_off_future_dates(): void
    {
        $this->makeTransaction('HARI INI', Carbon::now()->toDateString());
        $this->makeTransaction('6 HARI LALU', Carbon::now()->subDays(6)->toDateString());
        $this->makeTransaction('7 HARI LALU', Carbon::now()->subDays(7)->toDateString());

        $titles = $this->titlesFor(['period' => '7_days']);

        $this->assertContains('HARI INI', $titles);
        $this->assertContains('6 HARI LALU', $titles);
        $this->assertNotContains('7 HARI LALU', $titles);
    }

    public function test_period_7_days_keeps_open_ended_upper_bound(): void
    {
        // Transaksi di BULAN BERJUTA, bukan sekadar 7 hari ke depan. Kalau
        // hanya addWeek(), pada tanggal 1-23 sebuah bulan tanggal itu masih
        // jatuh di bulan ini -- jadi `this_month` akan memuatnya, dan itu
        // memang perilaku yang benar (this_month dibatasi akhir bulan ini),
        // bukan bug.
        $this->makeTransaction('MASA DEPAN', Carbon::now()->addMonth()->startOfMonth()->toDateString());

        // "7 hari terakhir" sengaja tidak dibatasi atas, jadi transaksi
        // bertanggal ke depan tetap ikut terhitung. Menutup batas atasnya
        // akan diam-diam mengubah angka ringkasan dashboard.
        $this->assertContains('MASA DEPAN', $this->titlesFor(['period' => '7_days']));
        $this->assertNotContains('MASA DEPAN', $this->titlesFor(['period' => 'this_month']));
    }

    public function test_period_this_month_covers_the_whole_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-30 14:00:00'));

        $this->makeTransaction('AWAL BULAN', '2026-09-01');
        $this->makeTransaction('TENGAH BULAN', '2026-09-15');
        $this->makeTransaction('AKHIR BULAN', '2026-09-30');
        $this->makeTransaction('BULAN LALU', '2026-08-31');

        // 30 September: batas atas harus mencakup sepanjang hari, bukan
        // berhenti di tengah malam.
        $this->assertCount(3, $this->titlesFor(['period' => 'this_month']));

        Carbon::setTestNow();
    }

    public function test_period_last_month_does_not_overflow_at_end_of_month(): void
    {
        // 31 Mei. Carbon::subMonth() paling iba kalender dan hasilnya luber
        // ke 1 Mei, sehingga filter "Bulan Lalu" ikut menampilkan Mei.
        Carbon::setTestNow(Carbon::parse('2026-05-31 10:00:00'));

        $this->makeTransaction('MEI', '2026-05-15');
        $this->makeTransaction('APRIL', '2026-04-15');

        $this->assertSame(['APRIL'], $this->titlesFor(['period' => 'last_month']));

        Carbon::setTestNow();
    }

    public function test_period_this_year_spans_january_to_december(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 10:00:00'));

        $this->makeTransaction('JANUARI', '2026-01-01');
        $this->makeTransaction('DESEMBER', '2026-12-31');
        $this->makeTransaction('TAHUN LALU', '2025-12-31');

        $this->assertCount(2, $this->titlesFor(['period' => 'this_year']));

        Carbon::setTestNow();
    }

    public function test_period_custom_ignores_unparsable_dates_instead_of_throwing(): void
    {
        $this->makeTransaction('APA SAJA', Carbon::now()->subYear()->toDateString());

        $titles = $this->titlesFor([
            'period' => 'custom',
            'start_date' => 'bukan tanggal',
            'end_date' => 'juga bukan',
        ]);

        $this->assertCount(1, $titles);
    }

    public function test_period_custom_bounds_are_inclusive(): void
    {
        $this->makeTransaction('AWAL', '2026-03-01');
        $this->makeTransaction('DALAM', '2026-03-15');
        $this->makeTransaction('AKHIR', '2026-03-31');
        $this->makeTransaction('LUAR', '2026-04-01');

        $titles = $this->titlesFor([
            'period' => 'custom',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ]);

        sort($titles);

        $this->assertSame(['AKHIR', 'AWAL', 'DALAM'], $titles);
    }
}
