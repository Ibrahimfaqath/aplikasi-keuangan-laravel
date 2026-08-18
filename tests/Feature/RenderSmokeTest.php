<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_pages_render(): void
    {
        $user = User::factory()->create();
        $tx = Transaction::create([
            'user_id' => $user->id,
            'title' => 'Test',
            'category' => 'Lainnya',
            'amount' => 10000,
            'type' => 'expense',
            'transaction_date' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('transactions.create'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('transactions.edit', $tx->id))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('ai.index'))
            ->assertOk();
    }
}
