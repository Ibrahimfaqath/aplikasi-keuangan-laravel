<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_set_monthly_budget(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('budgets.store'), ['amount' => 5000000])
            ->assertRedirect()
            ->assertSessionHas('success');

        $now = Carbon::now();
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'month' => $now->month,
            'year' => $now->year,
            'amount' => 5000000,
        ]);
    }

    public function test_budget_is_upserted_not_duplicated_per_month(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('budgets.store'), ['amount' => 3000000]);
        $this->actingAs($user)->post(route('budgets.store'), ['amount' => 4000000]);

        $this->assertEquals(1, Budget::where('user_id', $user->id)->count());
        $this->assertEquals(4000000, Budget::where('user_id', $user->id)->value('amount'));
    }

    public function test_budget_accepts_idr_formatted_amount(): void
    {
        $user = User::factory()->create();

        // Input ramah pengguna: "Rp 1.500.000" & "2.500.000" harus tersimpan sebagai angka murni
        $this->actingAs($user)
            ->post(route('budgets.store'), ['amount' => 'Rp 1.500.000'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'amount' => 1500000,
        ]);

        $this->actingAs($user)
            ->post(route('budgets.store'), ['amount' => '2.500.000'])
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'amount' => 2500000,
        ]);
    }

    public function test_user_can_set_category_budget(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('budgets.store'), [
                'amount' => 1000000,
                'category' => 'Makanan & Minuman',
                'months' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $now = Carbon::now();
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'month' => $now->month,
            'year' => $now->year,
            'category' => 'Makanan & Minuman',
            'amount' => 1000000,
        ]);
    }

    public function test_user_can_set_multi_month_budget(): void
    {
        $user = User::factory()->create();
        $now = Carbon::now();

        $this->actingAs($user)
            ->post(route('budgets.store'), [
                'amount' => 500000,
                'category' => 'Transportasi',
                'months' => 3,
            ])
            ->assertRedirect();

        for ($i = 0; $i < 3; $i++) {
            $monthOf = $now->copy()->addMonthsNoOverflow($i);
            $this->assertDatabaseHas('budgets', [
                'user_id' => $user->id,
                'month' => $monthOf->month,
                'year' => $monthOf->year,
                'category' => 'Transportasi',
                'amount' => 500000,
            ]);
        }

        $this->assertEquals(3, Budget::where('user_id', $user->id)->count());
    }

    public function test_multi_month_budget_cycles_year_boundary(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 12, 15, 12, 0, 0));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('budgets.store'), [
                'amount' => 2000000,
                'category' => 'Tagihan & Utilitas',
                'months' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'month' => 12,
            'year' => 2026,
            'amount' => 2000000,
        ]);
        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'month' => 1,
            'year' => 2027,
            'amount' => 2000000,
        ]);

        Carbon::setTestNow();
    }

    public function test_category_budget_rejects_income_or_unknown_category(): void
    {
        $user = User::factory()->create();

        // "Gaji" hanya kategori pemasukan — tidak boleh jadi budget pengeluaran.
        $this->actingAs($user)
            ->post(route('budgets.store'), [
                'amount' => 1000000,
                'category' => 'Gaji',
                'months' => 1,
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_budget_rejects_invalid_month_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('budgets.store'), [
            'amount' => 1000000,
            'months' => 0,
        ])->assertSessionHasErrors('months');

        $this->actingAs($user)->post(route('budgets.store'), [
            'amount' => 1000000,
            'months' => 13,
        ])->assertSessionHasErrors('months');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_overall_and_category_budgets_can_coexist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('budgets.store'), ['amount' => 5000000]);
        $this->actingAs($user)->post(route('budgets.store'), [
            'amount' => 1000000,
            'category' => 'Makanan & Minuman',
            'months' => 1,
        ]);

        $now = Carbon::now();
        $this->assertEquals(2, Budget::where('user_id', $user->id)
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->count());
    }

    public function test_user_can_delete_budget(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('budgets.store'), [
            'amount' => 1000000,
            'category' => 'Makanan & Minuman',
            'months' => 1,
        ]);

        $budget = Budget::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('budgets.destroy', $budget))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('budgets', 0);
    }

    public function test_dashboard_shows_category_budget_progress(): void
    {
        $user = User::factory()->create();

        $now = Carbon::now();
        Budget::create([
            'user_id' => $user->id,
            'month' => $now->month,
            'year' => $now->year,
            'category' => 'Makanan & Minuman',
            'amount' => 1000000,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Nasi Goreng',
            'category' => 'Makanan & Minuman',
            'amount' => 250000,
            'type' => 'expense',
            'transaction_date' => $now->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Anggaran per Kategori');
        $response->assertSee('Makanan & Minuman');
        $response->assertSee('dari Rp 1.000.000');
    }

    public function test_guest_cannot_set_budget(): void
    {
        $this->post(route('budgets.store'), ['amount' => 1000000])
            ->assertRedirect(route('login'));
    }
}
