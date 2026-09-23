<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TransactionImportTest extends TestCase
{
    use RefreshDatabase;

    private function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $content);
    }

    private function validCsv(): string
    {
        // Header persis seperti template & export (untuk round-trip).
        return implode("\n", [
            'Tanggal Transaksi;Keterangan / Judul;Kategori;Jenis Transaksi;Nominal',
            '2026-09-23;Gaji Bulanan;Gaji;Pemasukan;5000000',
            '2026-09-22;Makan Siang;Makanan & Minuman;Pengeluaran;25000',
            '',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_import_routes(): void
    {
        $this->get(route('transactions.import'))->assertRedirect(route('login'));
        $this->get(route('transactions.import-template'))->assertRedirect(route('login'));
        $this->post(route('transactions.import-preview'))->assertRedirect(route('login'));
        $this->post(route('transactions.import-store'))->assertRedirect(route('login'));
    }

    public function test_import_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.import'));

        $response->assertOk();
        $response->assertSee('Import Transaksi');
        $response->assertSee('Unduh Contoh Template');
    }

    public function test_template_download_returns_csv_attachment(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.import-template'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="template_transaksi.csv"');
        $response->assertSee('Tanggal Transaksi');
    }

    public function test_preview_lists_valid_and_invalid_rows(): void
    {
        $user = User::factory()->create();
        $csv = "Tanggal Transaksi;Keterangan / Judul;Kategori;Jenis Transaksi;Nominal\n"
            ."2026-09-23;Gaji Bulanan;Gaji;Pemasukan;5000000\n"
            ."2026-09-22;;Makanan & Minuman;Pengeluaran;25000\n";

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertOk();
        $response->assertViewHas('valid', function (array $rows) {
            return count($rows) === 1 && $rows[0]['title'] === 'Gaji Bulanan' && (float) $rows[0]['amount'] === 5000000.0;
        });
        $response->assertViewHas('invalid', fn (array $rows) => count($rows) === 1);
        $response->assertViewHas('token');
        $response->assertViewHas('filename', 'import.csv');
    }

    public function test_preview_accepts_comma_delimiter(): void
    {
        $user = User::factory()->create();
        $csv = "Tanggal,Keterangan,Kategori,Jenis,Nominal\n"
            ."2026-09-23,Beli Kopi,Makanan & Minuman,Pengeluaran,15000\n";

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertOk();
        $response->assertViewHas('valid', fn (array $rows) => count($rows) === 1 && $rows[0]['title'] === 'Beli Kopi');
    }

    public function test_preview_rejects_missing_required_columns_with_friendly_error(): void
    {
        $user = User::factory()->create();
        $csv = "Tanggal;Keterangan;Nominal\n2026-09-23;Kopi;5000\n";

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertRedirect();
        $response->assertSessionHas('error', function (string $message) {
            return str_contains($message, 'Kategori');
        });
    }

    public function test_preview_rejects_more_than_max_rows(): void
    {
        $user = User::factory()->create();
        $rows = ['Tanggal Transaksi;Keterangan / Judul;Kategori;Jenis Transaksi;Nominal'];
        for ($i = 1; $i <= CsvImportService::MAX_ROWS + 1; $i++) {
            $rows[] = "2026-09-23;Transaksi $i;Makanan & Minuman;Pengeluaran;25000";
        }

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile(implode("\n", $rows))]);

        $response->assertRedirect();
        $response->assertSessionHas('error', fn (string $message) => str_contains($message, '500 baris'));
    }

    public function test_preview_normalizes_rupiah_amount_formats(): void
    {
        $user = User::factory()->create();
        $csv = "Tanggal Transaksi;Keterangan / Judul;Kategori;Jenis Transaksi;Nominal\n"
            ."2026-09-23;Belanja Mingguan;Belanja;Pengeluaran;Rp 250.000\n"
            ."2026-09-24;Makan Malam;Makanan & Minuman;Pengeluaran;45000,50\n";

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertOk();
        $response->assertViewHas('valid', function (array $rows) {
            return count($rows) === 2
                && (float) $rows[0]['amount'] === 250000.0
                && (float) $rows[1]['amount'] === 45000.50;
        });
    }

    public function test_preview_infers_type_from_negative_amount_sign(): void
    {
        $user = User::factory()->create();
        $csv = "Tanggal Transaksi;Keterangan / Judul;Kategori;Nominal\n"
            ."2026-09-23;Bayar Tagihan Listrik;Tagihan & Utilitas;-350000\n";

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertOk();
        $response->assertViewHas('valid', fn (array $rows) => count($rows) === 1 && $rows[0]['type'] === 'expense');
    }

    public function test_preview_matches_category_case_insensitively_to_canonical_name(): void
    {
        $user = User::factory()->create();
        $csv = "Tanggal Transaksi;Keterangan / Judul;Kategori;Jenis Transaksi;Nominal\n"
            ."2026-09-23;Beli Sayur;makanan & minuman;pengeluaran;20000\n";

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertOk();
        $response->assertViewHas('valid', fn (array $rows) => $rows[0]['category'] === 'Makanan & Minuman');
    }

    public function test_preview_rejects_category_that_does_not_match_type(): void
    {
        $user = User::factory()->create();
        $csv = "Tanggal Transaksi;Keterangan / Judul;Kategori;Jenis Transaksi;Nominal\n"
            ."2026-09-23;Gaji Palsu;Gaji;Pengeluaran;100000\n";

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertOk();
        $response->assertViewHas('valid', fn (array $rows) => count($rows) === 0);
        $response->assertViewHas('invalid', fn (array $rows) => count($rows) === 1);
    }

    public function test_store_imports_all_valid_rows_and_clears_draft(): void
    {
        $user = User::factory()->create();

        $preview = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($this->validCsv())]);
        $token = $preview->viewData('token');

        $this->actingAs($user)
            ->post(route('transactions.import-store'), ['token' => $token])
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, Transaction::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'title' => 'Gaji Bulanan',
            'type' => 'income',
            'amount' => 5000000,
        ]);
        $this->assertNull(session('import_draft'));

        // Audit log sumber "import".
        $log = AuditLog::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('import', $log->source);
    }

    public function test_store_without_active_draft_redirects_back(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('transactions.import-store'), ['token' => 'becek-tidak-ada-di-session'])
            ->assertRedirect(route('transactions.import'))
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::where('user_id', $user->id)->count());
    }

    public function test_store_requires_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('transactions.import-store'))
            ->assertSessionHasErrors('token');
    }

    public function test_demo_user_cannot_preview_or_store(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);

        $this->actingAs($demo)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($this->validCsv())])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($demo)
            ->post(route('transactions.import-store'), ['token' => 'apa-saja'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Transaction::where('user_id', $demo->id)->count());
    }

    public function test_import_round_trip_with_export_headings(): void
    {
        $user = User::factory()->create();
        $csv = implode("\n", [
            'Tanggal Transaksi,Keterangan / Judul,Kategori,Jenis Transaksi,Nominal (Rp),Status Bukti Upload',
            '2026-09-23,Gaji Bulanan,Gaji,Pemasukan,5000000,Tidak Ada',
            '2026-09-22,Makan Siang,Makanan & Minuman,Pengeluaran,25000,Tidak Ada',
            '',
        ]);

        $response = $this->actingAs($user)
            ->post(route('transactions.import-preview'), ['file' => $this->csvFile($csv)]);

        $response->assertOk();
        $response->assertViewHas('valid', fn (array $rows) => count($rows) === 2);
    }
}
