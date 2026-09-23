<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::disk('local')->deleteDirectory('backups');
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('backups');

        parent::tearDown();
    }

    private function service(): DatabaseBackupService
    {
        return app(DatabaseBackupService::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('backups.index'))->assertRedirect(route('login'));
        $this->post(route('backups.store'))->assertRedirect(route('login'));
    }

    public function test_index_renders_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('backups.index'));

        $response->assertOk();
        $response->assertSee('Pencadangan & Restore');
        $response->assertSee('Belum ada backup');
    }

    public function test_store_creates_backup_file_and_lists_it(): void
    {
        $user = User::factory()->create();
        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Gaji bulanan',
            'category' => 'Gaji',
            'amount' => 5000000,
            'type' => 'income',
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->post(route('backups.store'));

        $response->assertRedirect(route('backups.index'));
        $response->assertSessionHas('success');

        $backup = $this->service()->latest();
        $this->assertNotNull($backup);

        $this->assertFileExists($this->service()->pathFor($backup['filename']));

        $page = $this->actingAs($user)->get(route('backups.index'));
        $page->assertSee($backup['filename']);
    }

    public function test_backup_contains_tables_and_data(): void
    {
        $user = User::factory()->create();
        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Pengeluaran Warung',
            'category' => 'Makanan & Minuman',
            'amount' => 25000,
            'type' => 'expense',
            'transaction_date' => now()->format('Y-m-d'),
        ]);

        $sql = $this->service()->sql();

        $this->assertStringContainsString('INSERT INTO `transactions`', $sql);
        $this->assertStringContainsString('Pengeluaran Warung', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `users`', $sql);
    }

    public function test_download_returns_backup_content(): void
    {
        $user = User::factory()->create();
        $backup = $this->service()->take();

        $response = $this->actingAs($user)->get(route('backups.download', $backup['filename']));

        $response->assertOk();
        $this->assertStringContainsString('INSERT INTO `users`', $response->streamedContent());
    }

    public function test_download_unknown_filename_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('backups.download', 'tidak-ada.sql'))->assertNotFound();
        $this->actingAs($user)->get(route('backups.download', '..%2F.env'))->assertNotFound();
    }

    public function test_prune_keeps_only_newest_files(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service()->take(3);
        }

        $this->assertCount(3, $this->service()->all());
    }

    public function test_demo_user_cannot_create_or_download_backup(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);
        $backup = $this->service()->take();

        $this->actingAs($demo)
            ->post(route('backups.store'))
            ->assertRedirect();

        $this->assertEquals(1, $this->service()->all()->count());

        $this->actingAs($demo)
            ->get(route('backups.download', $backup['filename']))
            ->assertRedirect();
    }

    public function test_cron_petunjuk_is_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('backups.index'));

        $response->assertSee('schedule:run');
        $response->assertSee('Cara Restore');
    }
}
