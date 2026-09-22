<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use App\Services\AiAssistantService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiAssistantServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AiAssistantService
    {
        return app(AiAssistantService::class);
    }

    private function candidate(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Belanja',
            'amount' => 25000,
            'type' => 'expense',
            'category' => 'Makanan & Minuman',
            'transaction_date' => Carbon::today()->format('Y-m-d'),
        ], $overrides);
    }

    #[Test]
    public function normal_date_is_kept_as_is(): void
    {
        $result = $this->service()->normalizeCandidate($this->candidate(['transaction_date' => '2023-05-10']));

        $this->assertSame('2023-05-10', $result['transaction_date']);
    }

    #[Test]
    public function future_date_is_clamped_to_today(): void
    {
        $result = $this->service()->normalizeCandidate($this->candidate(['transaction_date' => '2099-01-01']));

        $this->assertSame(Carbon::today()->format('Y-m-d'), $result['transaction_date']);
    }

    #[Test]
    public function impossible_past_date_is_clamped_to_today(): void
    {
        $result = $this->service()->normalizeCandidate($this->candidate(['transaction_date' => '1999-12-31']));

        $this->assertSame(Carbon::today()->format('Y-m-d'), $result['transaction_date']);
    }

    #[Test]
    public function unparseable_date_falls_back_to_today(): void
    {
        $result = $this->service()->normalizeCandidate($this->candidate(['transaction_date' => 'bukan-tanggal']));

        $this->assertSame(Carbon::today()->format('Y-m-d'), $result['transaction_date']);
    }

    #[Test]
    public function legacy_date_alias_is_respected(): void
    {
        $result = $this->service()->normalizeCandidate($this->candidate([
            'transaction_date' => null,
            'date' => '2024-01-15',
        ]));

        $this->assertSame('2024-01-15', $result['transaction_date']);
    }

    #[Test]
    public function custom_category_is_accepted_for_its_owner(): void
    {
        $user = User::factory()->create();
        Category::create(['user_id' => $user->id, 'name' => 'Jualan Online', 'type' => 'income']);

        $result = $this->service()->normalizeCandidate(
            $this->candidate(['type' => 'income', 'category' => 'Jualan Online']),
            $user->id
        );

        $this->assertNotNull($result);
        $this->assertSame('Jualan Online', $result['category']);
    }

    #[Test]
    public function other_users_custom_category_is_rejected(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Category::create(['user_id' => $owner->id, 'name' => 'Jualan Online', 'type' => 'income']);

        $result = $this->service()->normalizeCandidate(
            $this->candidate(['type' => 'income', 'category' => 'Jualan Online']),
            $other->id
        );

        $this->assertNull($result);
    }

    #[Test]
    public function default_category_still_accepted_without_userid(): void
    {
        $result = $this->service()->normalizeCandidate($this->candidate());

        $this->assertNotNull($result);
        $this->assertSame('Makanan & Minuman', $result['category']);
    }
}
