<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTypeFilterTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(User $user, array $overrides = []): Transaction
    {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'title' => 'Beli Kopi',
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'amount' => 15000,
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ], $overrides));
    }

    private function seedTwoTypes(User $user): void
    {
        $this->makeTransaction($user, [
            'title' => 'Gaji Bulanan',
            'type' => 'income',
            'category' => 'Gaji',
            'amount' => 5000000,
        ]);
        $this->makeTransaction($user, ['title' => 'Belanja Sayur', 'amount' => 75000]);
    }

    /**
     * Guard regresi: handler radio Tipe harus MEMANGGIL applyFilters().
     *
     * `onchange="applyFilters"` (tanpa kurung) adalah expression statement di
     * inline handler -- hanya membaca referensi fungsi, tidak pernah
     * menjalankannya, jadi klik Tipe tidak mengirim request apa pun.
     * Sementara `onchange` pada x-custom-select adalah NAMA fungsi yang
     * dipanggil oleh kode Alpine, jadi di sana tanpa kurung memang benar.
     */
    public function test_type_radio_handler_actually_calls_apply_filters(): void
    {
        $user = User::factory()->create();
        $this->seedTwoTypes($user);

        $html = $this->actingAs($user)->get(route('transactions.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/onchange="applyFilters\(\)"/', $html);
        $this->assertDoesNotMatchRegularExpression('/onchange="applyFilters"/', $html);
    }

    public function test_type_filter_full_page_renders_only_matching_transactions(): void
    {
        $user = User::factory()->create();
        $this->seedTwoTypes($user);

        $response = $this->actingAs($user)->get(route('transactions.index', ['type' => 'income']));

        $response->assertOk();
        $response->assertSee('Gaji Bulanan');
        $response->assertDontSee('Belanja Sayur');

        // Pilihan aktif harus ikut ter-render supaya state URL dan UI sinkron.
        $this->assertMatchesRegularExpression(
            '/<input[^>]*name="type"[^>]*value="income"[^>]*checked[^>]*>/s',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*name="type"[^>]*value="expense"[^>]*checked[^>]*>/s',
            $response->getContent()
        );
    }

    public function test_type_filter_partial_endpoint_returns_filtered_table_and_stats(): void
    {
        $user = User::factory()->create();
        $this->seedTwoTypes($user);

        $response = $this->actingAs($user)
            ->get(route('transactions.index', ['type' => 'income', 'partial' => 1]))
            ->assertOk();

        $stats = $response->json('stats');
        $this->assertEqualsWithDelta(5000000, (float) ($stats['totalIncome'] ?? 0), 0.01);
        $this->assertEqualsWithDelta(0, (float) ($stats['totalExpense'] ?? 0), 0.01);
        $this->assertEqualsWithDelta(5000000, (float) ($stats['totalBalance'] ?? 0), 0.01);

        $this->assertSame(1, $response->json('total'));
        $this->assertSame([], $response->json('categoryExpenses'));

        $tableHtml = $response->json('tableHtml');
        $this->assertIsString($tableHtml);
        $this->assertStringContainsString('Gaji Bulanan', $tableHtml);
        $this->assertStringNotContainsString('Belanja Sayur', $tableHtml);
    }

    public function test_type_filter_scope_stays_within_current_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->seedTwoTypes($owner);
        $this->makeTransaction($other, ['title' => 'Milik Orang Lain', 'type' => 'income']);

        $response = $this->actingAs($owner)
            ->get(route('transactions.index', ['type' => 'income', 'partial' => 1]))
            ->assertOk();

        $this->assertSame(1, $response->json('total'));
        $this->assertStringNotContainsString('Milik Orang Lain', $response->json('tableHtml'));
    }

    /**
     * Link pagination dihasilkan dari query string request parsial, jadi ikut
     * membawa `type` (harus dipertahankan) sekaligus `partial=1` (dibuang
     * oleh app.js sebelum URL dipush ke history).
     */
    public function test_pagination_links_keep_the_type_filter(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 15) as $i) {
            $this->makeTransaction($user, ['title' => 'Pemasukan '.$i, 'type' => 'income', 'category' => 'Gaji']);
        }

        $response = $this->actingAs($user)
            ->get(route('transactions.index', ['type' => 'income', 'partial' => 1]))
            ->assertOk();

        $tableHtml = $response->json('tableHtml');
        $this->assertStringContainsString('type=income', $tableHtml);
        $this->assertStringNotContainsString('page=2&amp;partial', $tableHtml);
    }
}
