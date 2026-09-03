<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AiConfirmTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_is_created_on_confirmation(): void
    {
        $user = User::factory()->create();

        // Set up pending transaction from AI flow (simulating AI detection)
        Session::put('pending_transaction', [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Beli Kopi',
                'amount' => 15000,
                'type' => 'expense',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
        ]);
    }

    public function test_transaction_is_not_created_on_cancel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai/cancel');

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_confirmation_fails_with_invalid_category(): void
    {
        $user = User::factory()->create();

        // Set up pending transaction from AI flow
        Session::put('pending_transaction', [
            'title' => 'Beli Apa',
            'amount' => 10000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Transaksi Aneh',
                'amount' => 1000,
                'type' => 'expense',
                'category' => 'Kategori Tidak Valid',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_confirmation_fails_with_invalid_type(): void
    {
        $user = User::factory()->create();

        // Set up pending transaction from AI flow
        Session::put('pending_transaction', [
            'title' => 'Beli Apa',
            'amount' => 10000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Transaksi Aneh',
                'amount' => 1000,
                'type' => 'invalid_type',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_confirmation_fails_with_invalid_amount(): void
    {
        $user = User::factory()->create();

        // Set up pending transaction from AI flow
        Session::put('pending_transaction', [
            'title' => 'Beli Apa',
            'amount' => 10000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Transaksi Aneh',
                'amount' => 0,
                'type' => 'expense',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_ownership_is_always_auth_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Set up pending transaction for user A from AI flow
        Session::put('pending_transaction', [
            'title' => 'Gaji',
            'amount' => 5000000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($userA)
            ->postJson('/ai/confirm', [
                'title' => 'Gaji',
                'amount' => 5000000,
                'type' => 'income',
                'category' => 'Gaji',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userA->id,
            'title' => 'Gaji',
        ]);

        // User B cannot confirm user A's pending transaction
        // First, clear the pending session and set up user B's own pending
        Session::forget('pending_transaction');
        Session::put('pending_transaction', [
            'title' => 'Belanja',
            'amount' => 50000,
            'type' => 'expense',
            'category' => 'Belanja',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $responseB = $this->actingAs($userB)
            ->postJson('/ai/confirm', [
                'title' => 'Belanja',
                'amount' => 50000,
                'type' => 'expense',
                'category' => 'Belanja',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $responseB->assertOk();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userB->id,
            'title' => 'Belanja',
        ]);

        // Verify user A's transaction still only belongs to user A
        $this->assertDatabaseHas('transactions', [
            'user_id' => $userA->id,
            'title' => 'Gaji',
        ]);

        // User B should NOT have user A's transaction
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $userB->id,
            'title' => 'Gaji',
        ]);
    }

    private function fakeAiTransactionReply(array $candidate): void
    {
        config(['services.kiosapi.key' => 'test-kiosapi-key']);
        config(['services.kiosapi.url' => 'https://kiosapi.com/v1/chat/completions']);
        config(['services.kiosapi.model' => 'deepseek-v4-flash']);

        $content = 'Baik, ini ringkasannya. Mohon konfirmasi ya!'
            . "\n<<<JSON\n" . json_encode($candidate) . "\nJSON>>>";

        Http::fake([
            'https://kiosapi.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => $content]]],
            ], 200),
        ]);
    }

    public function test_end_to_end_ai_shape_with_date_key_can_be_confirmed(): void
    {
        // Regression test: the real AI returns {"date": "..."} (see system
        // prompt), and the frontend POSTs that object verbatim to /ai/confirm.
        $user = User::factory()->create();

        $this->fakeAiTransactionReply([
            'intent' => 'transaction',
            'title' => 'Makan Siang',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        $chat = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Saya beli makan 15000 hari ini.']);
        $chat->assertOk();

        $candidate = $chat->json('transaction');
        $this->assertNotNull($candidate);
        // Server normalizes the AI "date" alias to the canonical key.
        $this->assertArrayHasKey('transaction_date', $candidate);
        $this->assertEquals(Carbon::now()->format('Y-m-d'), $candidate['transaction_date']);

        // Frontend sends the candidate back verbatim.
        $confirm = $this->actingAs($user)->postJson('/ai/confirm', $candidate);
        $confirm->assertOk();
        $confirm->assertJson(['success' => true]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'title' => 'Makan Siang',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
        ]);
        $this->assertEquals(1, Transaction::where('user_id', $user->id)->count());
    }

    public function test_pending_candidate_is_stored_in_session_after_chat(): void
    {
        $user = User::factory()->create();

        $this->fakeAiTransactionReply([
            'intent' => 'transaction',
            'title' => 'Makan Siang',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Saya beli makan 15000 hari ini.'])
            ->assertOk();

        $pending = Session::get('pending_transaction');
        $this->assertNotNull($pending);
        $this->assertEquals('Makan Siang', $pending['title']);
        $this->assertEquals(Carbon::now()->format('Y-m-d'), $pending['transaction_date']);
    }

    public function test_confirm_without_pending_candidate_creates_nothing(): void
    {
        $user = User::factory()->create();

        Session::forget('pending_transaction');

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Serangan',
                'amount' => 999999,
                'type' => 'expense',
                'category' => 'Belanja',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_double_confirmation_creates_only_one_transaction(): void
    {
        $user = User::factory()->create();

        Session::put('pending_transaction', [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $payload = [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ];

        $this->actingAs($user)->postJson('/ai/confirm', $payload)->assertOk();
        $this->assertEquals(1, Transaction::where('user_id', $user->id)->count());

        // Second attempt must be rejected: pending was cleared.
        $retry = $this->actingAs($user)->postJson('/ai/confirm', $payload);
        $retry->assertStatus(400);
        $this->assertEquals(1, Transaction::where('user_id', $user->id)->count());
    }

    public function test_confirm_rejects_request_tampered_against_pending(): void
    {
        $user = User::factory()->create();

        Session::put('pending_transaction', [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        // Attacker edits the amount before confirming.
        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Beli Kopi',
                'amount' => 1500000,
                'type' => 'expense',
                'category' => 'Makanan & Minuman',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('transactions', 0);
        // Pending candidate must survive a failed attempt.
        $this->assertNotNull(Session::get('pending_transaction'));
    }

    public function test_confirm_rejects_category_invalid_for_type(): void
    {
        $user = User::factory()->create();

        Session::put('pending_transaction', [
            'title' => 'Coba',
            'amount' => 10000,
            'type' => 'expense',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/ai/confirm', [
                'title' => 'Coba',
                'amount' => 10000,
                'type' => 'expense',
                'category' => 'Gaji',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cancel_clears_pending_candidate(): void
    {
        $user = User::factory()->create();

        Session::put('pending_transaction', [
            'title' => 'Beli Kopi',
            'amount' => 15000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->actingAs($user)->postJson('/ai/cancel')->assertOk();

        $this->assertNull(Session::get('pending_transaction'));
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_confirmed_transaction_always_belongs_to_auth_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Session::put('pending_transaction', [
            'title' => 'Gaji',
            'amount' => 5000000,
            'type' => 'income',
            'category' => 'Gaji',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->actingAs($userB)
            ->postJson('/ai/confirm', [
                'title' => 'Gaji',
                'amount' => 5000000,
                'type' => 'income',
                'category' => 'Gaji',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ])
            ->assertOk();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $userB->id,
            'title' => 'Gaji',
        ]);
        $this->assertDatabaseMissing('transactions', [
            'user_id' => $userA->id,
            'title' => 'Gaji',
        ]);
    }
}