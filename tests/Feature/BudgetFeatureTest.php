<?php

namespace Tests\Feature;

use App\Models\Budget;
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
            'month'   => $now->month,
            'year'    => $now->year,
            'amount'  => 5000000,
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
            'amount'  => 1500000,
        ]);

        $this->actingAs($user)
            ->post(route('budgets.store'), ['amount' => '2.500.000'])
            ->assertRedirect();

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'amount'  => 2500000,
        ]);
    }

    public function test_guest_cannot_set_budget(): void
    {
        $this->post(route('budgets.store'), ['amount' => 1000000])
            ->assertRedirect(route('login'));
    }
}
