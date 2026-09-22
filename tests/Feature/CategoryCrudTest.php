<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DemoMode;
use Carbon\Carbon;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_custom_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/categories', ['name' => 'Jualan Online', 'type' => 'income'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);
    }

    public function test_duplicate_category_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/categories', ['name' => 'Gaji', 'type' => 'income'])
            ->assertSessionHasErrors('name');
    }

    public function test_same_name_allowed_in_other_type(): void
    {
        $user = User::factory()->create();

        // "Lainnya" sudah ada sebagai pengeluaran bawaan — ditolak di jenis itu.
        $this->actingAs($user)
            ->post('/categories', ['name' => 'Lainnya', 'type' => 'expense'])
            ->assertSessionHasErrors('name');

        // "Hadiah" hanya ada sebagai pemasukan bawaan — boleh dibuat di pengeluaran.
        $this->actingAs($user)
            ->post('/categories', ['name' => 'Hadiah', 'type' => 'expense'])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Hadiah',
            'type' => 'expense',
        ]);
    }

    public function test_user_can_delete_own_custom_category(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category->id))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_other_users_category(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $category = Category::create([
            'user_id' => $owner->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        $this->actingAs($attacker)
            ->delete(route('categories.destroy', $category->id))
            ->assertNotFound();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_demo_user_cannot_add_or_delete_category(): void
    {
        $user = User::factory()->create(['email' => config('demo.email')]);

        $this->actingAs($user)
            ->post('/categories', ['name' => 'Hack', 'type' => 'expense'])
            ->assertRedirect()
            ->assertSessionHas('error', DemoMode::ERROR);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category->id))
            ->assertRedirect()
            ->assertSessionHas('error', DemoMode::ERROR);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_custom_category_usable_in_transaction(): void
    {
        $user = User::factory()->create();
        Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        $this->actingAs($user)->post('/transactions', [
            'title' => 'Jualan Distro',
            'category' => 'Jualan Online',
            'amount' => 50000,
            'type' => 'income',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ])->assertRedirect('/transactions');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'title' => 'Jualan Distro',
            'category' => 'Jualan Online',
        ]);
    }

    public function test_default_categories_seeded_globally(): void
    {
        $this->seed(CategorySeeder::class);

        $expected = count(Transaction::INCOME_CATEGORIES) + count(Transaction::EXPENSE_CATEGORIES);

        $this->assertDatabaseCount('categories', $expected)
            ->assertDatabaseHas('categories', ['user_id' => null, 'name' => 'Gaji', 'type' => 'income'])
            ->assertDatabaseHas('categories', ['user_id' => null, 'name' => 'Makanan & Minuman', 'type' => 'expense']);
    }
}
