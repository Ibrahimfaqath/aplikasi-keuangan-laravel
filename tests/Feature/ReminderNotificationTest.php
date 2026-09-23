<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeBudget(User $user, float|int $amount, string $category = ''): Budget
    {
        $now = Carbon::now();

        return Budget::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'month' => $now->month,
            'year' => $now->year,
            'category' => $category,
        ]);
    }

    private function makeExpense(User $user, float|int $amount, string $category = 'Makanan & Minuman', string $date = ''): void
    {
        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Pengeluaran Test',
            'category' => $category,
            'amount' => $amount,
            'type' => 'expense',
            'transaction_date' => $date !== '' ? $date : Carbon::now()->format('Y-m-d'),
        ]);
    }

    public function test_guest_is_redirected_to_login_from_notifications(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_budget_at_exactly_80_percent_triggers_near_warning(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $user = User::factory()->create();
        $this->makeBudget($user, 100000);
        $this->makeExpense($user, 80000, 'Makanan & Minuman', '2026-09-05');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('count', 1);
        $response->assertJsonPath('items.0.type', 'budget-near');
        $response->assertJsonPath('items.0.title', 'Anggaran bulanan hampir penuh');
    }

    public function test_budget_under_80_percent_produces_no_warning(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $user = User::factory()->create();
        $this->makeBudget($user, 100000);
        $this->makeExpense($user, 79000, 'Makanan & Minuman', '2026-09-05');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('count', 0);
    }

    public function test_over_budget_triggers_over_warning(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $user = User::factory()->create();
        $this->makeBudget($user, 100000);
        $this->makeExpense($user, 120000, 'Makanan & Minuman', '2026-09-05');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('count', 1);
        $response->assertJsonPath('items.0.type', 'budget-over');
        $response->assertJsonPath('items.0.title', 'Anggaran bulanan terlampaui');
    }

    public function test_category_budget_triggers_independent_warning(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        $user = User::factory()->create();
        $this->makeBudget($user, 100000, '');
        $this->makeBudget($user, 50000, 'Makanan & Minuman');
        $this->makeBudget($user, 50000, 'Transportasi');
        $this->makeExpense($user, 60000, 'Makanan & Minuman', '2026-09-05');
        $this->makeExpense($user, 10000, 'Transportasi', '2026-09-06');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        // Overall 70k/100k (70%, aman); Makanan 60k/50k (over); Transportasi 10k/50k (aman).
        $response->assertOk()->assertJsonPath('count', 1);
        $response->assertJsonPath('items.0.type', 'budget-over');
        $response->assertJsonPath('items.0.title', 'Anggaran Makanan & Minuman terlampaui');
    }

    public function test_no_transactions_today_triggers_daily_reminder_after_18h(): void
    {
        Carbon::setTestNow('2026-09-10 20:00:00');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('count', 1);
        $response->assertJsonPath('items.0.type', 'daily');
        $response->assertJsonPath('items.0.href', route('transactions.create'));
    }

    public function test_daily_reminder_not_shown_before_18h(): void
    {
        Carbon::setTestNow('2026-09-10 12:00:00');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('count', 0);
    }

    public function test_daily_reminder_not_shown_when_today_has_transactions(): void
    {
        Carbon::setTestNow('2026-09-10 20:00:00');
        $user = User::factory()->create();
        $this->makeExpense($user, 15000, 'Makanan & Minuman', '2026-09-10');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('count', 0);
    }

    public function test_budget_and_daily_reminders_can_combine(): void
    {
        Carbon::setTestNow('2026-09-10 20:00:00');
        $user = User::factory()->create();
        $this->makeBudget($user, 100000);
        $this->makeExpense($user, 90000, 'Makanan & Minuman', '2026-09-05');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk()->assertJsonPath('count', 2);
        $response->assertJsonPath('items.0.type', 'budget-near');
        $response->assertJsonPath('items.1.type', 'daily');
    }

    public function test_notification_bell_is_present_on_dashboard_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('notificationBell', false);
        $response->assertSee('Notifikasi', false);
    }

    public function test_notifications_work_for_demo_user(): void
    {
        Carbon::setTestNow('2026-09-10 20:00:00');
        $demo = User::factory()->create(['email' => config('demo.email')]);

        $response = $this->actingAs($demo)->getJson(route('notifications.index'));

        $response->assertOk();
        $response->assertJsonPath('count', 1);
    }
}
