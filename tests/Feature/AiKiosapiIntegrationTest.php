<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AiKiosapiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure the KiosAPI key is present for these tests.
        config(['services.kiosapi.key' => 'test-kiosapi-key']);
        config(['services.kiosapi.url' => 'https://kiosapi.com/v1/chat/completions']);
        config(['services.kiosapi.model' => 'deepseek-v4-flash']);
    }

    private function mockResponse(string $content, int $status = 200): void
    {
        Http::fake([
            'https://kiosapi.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => $content]],
                ],
            ], $status),
        ]);
    }

    public function test_successful_response_content_is_extracted(): void
    {
        $this->mockResponse('Saldo kamu adalah Rp 5.000.000.');

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Berapa saldo saya?']);

        $response->assertOk();
        $response->assertJson(['reply' => 'Saldo kamu adalah Rp 5.000.000.']);
    }

    public function test_http_request_shape_is_correct(): void
    {
        Http::fake([
            'https://kiosapi.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'ok']],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $request->url() === 'https://kiosapi.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-kiosapi-key')
                && $request->hasHeader('Accept', 'application/json')
                && $body['model'] === 'deepseek-v4-flash'
                && $body['temperature'] === 0.7
                && count($body['messages']) === 2;
        });
    }

    public function test_malformed_response_without_choices_returns_error(): void
    {
        Http::fake([
            'https://kiosapi.com/v1/chat/completions' => Http::response(['foo' => 'bar'], 200),
        ]);

        Log::shouldReceive('debug')
            ->once()
            ->with('AI chat timing', \Mockery::any());
        Log::shouldReceive('error')
            ->once()
            ->with('KiosAPI returned malformed response', \Mockery::any());

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        $response->assertOk();
        $this->assertNotEquals('ok', $response->json('reply'));
        $this->assertStringContainsString('Maaf', $response->json('reply'));
    }

    public function test_malformed_non_json_response_returns_error(): void
    {
        Http::fake([
            'https://kiosapi.com/v1/chat/completions' => Http::response('<html>error</html>', 200),
        ]);

        Log::shouldReceive('debug')
            ->once()
            ->with('AI chat timing', \Mockery::any());
        Log::shouldReceive('error')
            ->once()
            ->with('KiosAPI returned malformed response', \Mockery::any());

        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/ai/chat', ['message' => 'Halo']);
    }

    public function test_missing_api_key_returns_500_and_no_request(): void
    {
        config(['services.kiosapi.key' => null]);

        Http::fake();

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        $response->assertStatus(500);
        Http::assertNothingSent();
    }

    public function test_http_429_rate_limited_returns_friendly_message(): void
    {
        $this->mockResponse('', 429);

        Log::shouldReceive('debug')
            ->once()
            ->with('AI chat timing', \Mockery::any());
        Log::shouldReceive('warning')
            ->once()
            ->with('KiosAPI 429: rate limit or quota exceeded', \Mockery::any());

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        $response->assertOk();
        $this->assertStringContainsString('sibuk', $response->json('reply'));
    }

    public function test_http_401_returns_friendly_message_and_logs_warning(): void
    {
        $this->mockResponse('', 401);

        Log::shouldReceive('debug')
            ->once()
            ->with('AI chat timing', \Mockery::any());
        Log::shouldReceive('warning')
            ->once()
            ->with('KiosAPI 401: invalid or missing API key', \Mockery::any());

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        $response->assertOk();
        $this->assertStringContainsString('Maaf', $response->json('reply'));
    }

    public function test_http_500_server_error_returns_friendly_message(): void
    {
        $this->mockResponse('', 500);

        Log::shouldReceive('debug')
            ->once()
            ->with('AI chat timing', \Mockery::any());
        Log::shouldReceive('error')
            ->once()
            ->with('KiosAPI 5xx: provider/server error', \Mockery::any());

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        $response->assertOk();
        $this->assertStringContainsString('Maaf', $response->json('reply'));
    }

    public function test_connection_exception_returns_offline_message(): void
    {
        Http::fake([
            'https://kiosapi.com/v1/chat/completions' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
            },
        ]);

        Log::shouldReceive('debug')
            ->once()
            ->with('AI chat timing', \Mockery::any());
        Log::shouldReceive('error')
            ->once()
            ->with('KiosAPI connection error', \Mockery::any());

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        $response->assertOk();
        $this->assertStringContainsString('koneksi', $response->json('reply'));
    }

    public function test_transaction_intent_with_json_block_still_goes_to_confirm_flow(): void
    {
        $json = "Catat makan siang 25 ribu.\n"
            . "<<<JSON\n"
            . '{"intent":"transaction","title":"Makan Siang","amount":25000,"type":"expense","category":"Makanan & Minuman","date":"' . Carbon::now()->format('Y-m-d') . "\"}\n"
            . "JSON>>>";
        $this->mockResponse($json);

        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Catat makan siang 25 ribu']);

        $response->assertOk();
        $transaction = $response->json('transaction');
        $this->assertNotNull($transaction);
        $this->assertEquals('Makan Siang', $transaction['title']);
        $this->assertEquals(25000, $transaction['amount']);
        // Must NOT create the transaction without server confirmation.
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_live_provider_contract_accepted(): void
    {
        // This test documents the expected OpenAI-compatible request/response
        // contract used by KiosAPI. It runs against Http::fake and asserts the
        // fixture we ship matches what the real provider is expected to return.
        $this->mockResponse('Ini adalah balasan dari AI.');
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai/chat', ['message' => 'Halo']);

        $response->assertOk();
        $this->assertEquals('Ini adalah balasan dari AI.', $response->json('reply'));
    }
}
