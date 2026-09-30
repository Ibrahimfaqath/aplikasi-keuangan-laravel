<?php

namespace Tests\Feature;

use App\Http\Controllers\TransactionController;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransactionSoftDeleteTest extends TestCase
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

    public function test_delete_moves_transaction_to_trash_not_hard_delete(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect()
            ->assertSessionHas('success');

        // Baris masih ada di DB dengan deleted_at terisi (soft delete).
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertSame(1, Transaction::withTrashed()->where('user_id', $user->id)->count());
        $this->assertSame(0, Transaction::where('user_id', $user->id)->count());
    }

    public function test_soft_deleted_transaction_is_hidden_from_index_page(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user, ['title' => 'RAHASIA Dihapus']);

        $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertDontSee('RAHASIA Dihapus');
    }

    public function test_trashed_page_lists_deleted_transactions_only(): void
    {
        $user = User::factory()->create();
        $deleted = $this->makeTransaction($user, ['title' => 'Masuk Sampah']);
        $active = $this->makeTransaction($user, ['title' => 'Tetap Aktif']);

        $this->actingAs($user)->delete(route('transactions.destroy', $deleted));

        $response = $this->actingAs($user)->get(route('transactions.trashed'));

        $response->assertOk();
        $response->assertSee('Masuk Sampah');
        $response->assertDontSee('Tetap Aktif');
    }

    public function test_trashed_page_supports_search_filter(): void
    {
        $user = User::factory()->create();
        $this->makeTransaction($user, ['title' => 'Makan Malam'])
            ->delete();
        $this->makeTransaction($user, ['title' => 'Bensin Motor'])
            ->delete();

        $response = $this->actingAs($user)->get(route('transactions.trashed', ['search' => 'Bensin']));

        $response->assertOk();
        $response->assertSee('Bensin Motor');
        $response->assertDontSee('Makan Malam');
    }

    public function test_restore_brings_transaction_back_to_active(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);
        $transaction->delete();

        $this->actingAs($user)
            ->post(route('transactions.restore', $transaction))
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertSame(1, Transaction::where('user_id', $user->id)->count());

        // Setelah dipulihkan, muncul lagi di dashboard.
        $this->actingAs($user)->get(route('transactions.index'))->assertSee('Beli Kopi');
    }

    public function test_restore_preserves_receipt_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('receipts/bukti.jpg', 'data');

        $transaction = $this->makeTransaction($user, ['image' => 'receipts/bukti.jpg']);
        $transaction->delete();

        // Gambar TIDAK dihapus saat soft delete (agar bisa dipulihkan).
        $this->assertTrue(Storage::disk('public')->exists('receipts/bukti.jpg'));
        $this->assertNotNull($transaction->fresh()->image);

        $this->actingAs($user)->post(route('transactions.restore', $transaction));

        $this->assertTrue(Storage::disk('public')->exists('receipts/bukti.jpg'));
        $this->assertSame('receipts/bukti.jpg', $transaction->fresh()->image);
    }

    public function test_force_destroy_removes_transaction_permanently(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);
        $transaction->delete();

        $this->actingAs($user)
            ->delete(route('transactions.force-destroy', $transaction))
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertSame(0, Transaction::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_force_destroy_removes_receipt_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('receipts/bukti.jpg', 'data');

        $transaction = $this->makeTransaction($user, ['image' => 'receipts/bukti.jpg']);
        $transaction->delete();

        $this->actingAs($user)->delete(route('transactions.force-destroy', $transaction));

        $this->assertFalse(Storage::disk('public')->exists('receipts/bukti.jpg'));
    }

    public function test_user_cannot_soft_delete_other_users_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $transaction = $this->makeTransaction($owner);

        $this->actingAs($intruder)
            ->delete(route('transactions.destroy', $transaction))
            ->assertNotFound();

        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_restore_other_users_trashed_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $transaction = $this->makeTransaction($owner);
        $transaction->delete();

        $this->actingAs($intruder)
            ->post(route('transactions.restore', $transaction))
            ->assertNotFound();

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_user_cannot_force_destroy_other_users_trashed_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $transaction = $this->makeTransaction($owner);
        $transaction->delete();

        $this->actingAs($intruder)
            ->delete(route('transactions.force-destroy', $transaction))
            ->assertNotFound();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    public function test_guest_cannot_access_trashed_routes(): void
    {
        $route = route('transactions.trashed');

        $this->get($route)->assertRedirect(route('login'));
        $this->post(route('transactions.restore', 1))->assertRedirect(route('login'));
        $this->delete(route('transactions.force-destroy', 1))->assertRedirect(route('login'));
    }

    public function test_demo_user_cannot_restore_or_force_destroy(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);
        $transaction = $this->makeTransaction($demo);
        $transaction->delete();

        $this->actingAs($demo)
            ->post(route('transactions.restore', $transaction))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);

        $this->actingAs($demo)
            ->delete(route('transactions.force-destroy', $transaction))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_trashed_page_groups_rows_by_deleted_at_bucket(): void
    {
        $user = User::factory()->create();

        // Jam 3 pagi dipakai sengaja: kalau pengelompokan dihitung per 24 jam,
        // "semalam jam 3" masih terhitung "Hari ini" walau user membaca
        // "Kemarin" sebagai seluruh hari kemarin.
        $old = Carbon::parse('2026-07-17 03:00');
        $buckets = [
            'Hari ini' => Carbon::now()->setTime(9, 30),
            'Kemarin' => Carbon::now()->subDay()->setTime(3, 0),
            '3 hari lalu' => Carbon::now()->subDays(3)->setTime(3, 0),
            // Tanggal dikunci, bukan relatif: kalau relatif, label bulan ikut
            // bergeser tiap kali test ini dijalankan. Nama bulan juga ditulis
            // dalam Bahasa Indonesia — format('F') diam-diam memberi "July".
            'Juli 2026' => $old,
        ];

        foreach (array_keys($buckets) as $title) {
            $transaction = $this->makeTransaction($user, ['title' => 'Item '.$title]);
            $transaction->delete();
            $transaction->forceFill(['deleted_at' => $buckets[$title]])->saveQuietly();
        }

        $html = $this->actingAs($user)->get(route('transactions.trashed'))->getContent();

        foreach (array_keys($buckets) as $label) {
            $this->assertStringContainsString($label, $html, "Bucket waktu [$label] tidak muncul.");
        }

        $this->assertStringNotContainsString('July 2026', $html);
    }

    public function test_trashed_page_distinguishes_empty_trash_from_unmatched_filter(): void
    {
        $user = User::factory()->create();

        // 1. Sampah benar-benar kosong → ajakan kembali ke daftar transaksi.
        $this->actingAs($user)->get(route('transactions.trashed'))
            ->assertOk()
            ->assertSee('Sampah kosong')
            ->assertDontSee('Tidak ada yang cocok');

        // 2. Ada isinya, tapi filter tidak cocok → harus minta reset filter,
        //    bukan menyuruh user mengira datanya hilang.
        $this->makeTransaction($user, ['title' => 'Bensin Motor'])->delete();

        $this->actingAs($user)->get(route('transactions.trashed', ['search' => 'tidak-ada']))
            ->assertOk()
            ->assertSee('Tidak ada yang cocok')
            ->assertSee('Reset filter')
            ->assertDontSee('Sampah kosong');
    }

    public function test_trashed_page_reports_expense_and_income_separately(): void
    {
        $user = User::factory()->create();

        $expense = $this->makeTransaction($user, ['title' => 'Bayar Listrik', 'amount' => 350000]);
        $expense->delete();
        $income = $this->makeTransaction($user, [
            'title' => 'Gaji Lama', 'amount' => 5000000, 'type' => 'income', 'category' => 'Gaji',
        ]);
        $income->delete();

        $response = $this->actingAs($user)->get(route('transactions.trashed'));

        $response->assertOk()
            ->assertSee('Pengeluaran')
            ->assertSee('Pemasukan');

        // "Total Nominal" pernah menjumlahkan pemasukan + pengeluaran, angka yang
        // tidak punya arti apa pun di aplikasi keuangan. Jangan dibangkitan lagi.
        $response->assertDontSee('Total Nominal');
    }

    public function test_force_destroy_uses_named_confirmation_modal_not_native_confirm(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user, ['title' => 'Beli Bensin']);
        $transaction->delete();

        $response = $this->actingAs($user)->get(route('transactions.trashed'));

        // confirm() native tidak bisa di-style dan tidak pernah menyebut transaksi
        // mana yang dihapus, padahal ini aksi yang benar-benar menghapus data.
        $response->assertOk()
            ->assertSee('konfirmasi-hapus-permanen', escape: false)
            ->assertSee('Beli Bensin')
            ->assertDontSee('return confirm(', escape: false);
    }

    public function test_trashed_period_filter_reads_deleted_at_not_transaction_date(): void
    {
        $user = User::factory()->create();

        // Dua transaksi yang sengaja dibalik: tanggal transaksinya berlawanan
        // dengan tanggal dibuangnya. Kalau filter periode ikut memakai
        // transaction_date, hasil di bawah akan terbalik.
        $baruDibuang = $this->makeTransaction($user, [
            'title' => 'BARU DIBUANG',
            'transaction_date' => Carbon::now()->subDays(60)->toDateString(),
        ]);
        $baruDibuang->delete();
        $baruDibuang->forceFill(['deleted_at' => Carbon::now()->setTime(10, 0)])->saveQuietly();

        $lamaDibuang = $this->makeTransaction($user, [
            'title' => 'LAMA DIBUANG',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);
        $lamaDibuang->delete();
        $lamaDibuang->forceFill(['deleted_at' => Carbon::now()->subDays(40)->setTime(10, 0)])->saveQuietly();

        $this->actingAs($user)->get(route('transactions.trashed', ['period' => 'today']))
            ->assertOk()
            ->assertSee('BARU DIBUANG')
            ->assertDontSee('LAMA DIBUANG');

        // Sebaliknya, "Bulan Ini" harus menjatuhkan yang dibuang 40 hari lalu
        // meskipun tanggal transaksinya memang bulan ini.
        $this->actingAs($user)->get(route('transactions.trashed', ['period' => 'this_month']))
            ->assertOk()
            ->assertDontSee('LAMA DIBUANG');
    }

    public function test_transactions_page_period_filter_still_reads_transaction_date(): void
    {
        // Penjaga regresi: ReportingService kini menerima kolom tanggal sebagai
        // parameter. Halaman /transactions harus tetap menyaring tanggal
        // transaksi — kalau tidak, filter periode di dashboard diam-diam
        // berubah makna.
        $user = User::factory()->create();

        $this->makeTransaction($user, [
            'title' => 'TRANSAKSI BULAN INI',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $luarBulanIni = $this->makeTransaction($user, [
            'title' => 'TRANSAKSI BULAN LALU',
            'transaction_date' => Carbon::now()->subMonth()->toDateString(),
        ]);
        $luarBulanIni->delete();
        // Dibuang hari ini, jadi kalau kolomnya salah jadi ikut tampil.
        $luarBulanIni->forceFill(['deleted_at' => Carbon::now()])->saveQuietly();

        $this->actingAs($user)->get(route('transactions.index', ['period' => 'this_month']))
            ->assertOk()
            ->assertSee('TRANSAKSI BULAN INI')
            ->assertDontSee('TRANSAKSI BULAN LALU');
    }

    public function test_unknown_date_column_falls_back_instead_of_reaching_sql(): void
    {
        // Nama kolom masuk ke SQL, jadi input di luar daftar putih harus
        // diam-diam diabaikan — bukan diteruskan ke kueri.
        $user = User::factory()->create();
        $this->makeTransaction($user);

        $query = (new ReportingService)
            ->getFilteredQuery(['period' => 'this_month'], $user->id, false, '1); DROP TABLE transactions; --');

        $this->assertSame(1, $query->count());
    }

    public function test_trashed_partial_endpoint_returns_only_the_table_chunk(): void
    {
        $user = User::factory()->create();
        $this->makeTransaction($user, ['title' => 'Bensin Motor'])->delete();
        $this->makeTransaction($user, ['title' => 'Kopi'])->delete();

        $response = $this->actingAs($user)
            ->getJson(route('transactions.trashed', ['partial' => 1]));

        $response->assertOk()
            ->assertJsonStructure(['total', 'tableHtml'])
            ->assertJsonPath('total', 2);

        $html = $response->json('tableHtml');

        $this->assertStringContainsString('Bensin Motor', $html);
        $this->assertStringContainsString('Kopi', $html);

        // Potongan tabel, bukan halaman penuh: mesin filter menimpanya ke
        // #riwayatTable, jadi markup kerangka halaman di sini justru sia-sia.
        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('filterForm', $html);
    }

    public function test_trashed_partial_endpoint_applies_active_filters(): void
    {
        $user = User::factory()->create();
        $this->makeTransaction($user, ['title' => 'Bensin Motor'])->delete();
        $this->makeTransaction($user, ['title' => 'Kopi'])->delete();

        $response = $this->actingAs($user)
            ->getJson(route('transactions.trashed', ['partial' => 1, 'search' => 'Bensin']));

        $response->assertOk()->assertJsonPath('total', 1);

        $html = $response->json('tableHtml');

        $this->assertStringContainsString('Bensin Motor', $html);
        $this->assertStringNotContainsString('Kopi', $html);
    }

    public function test_trashed_partial_endpoint_shows_filtered_empty_state(): void
    {
        $user = User::factory()->create();
        $this->makeTransaction($user, ['title' => 'Bensin Motor'])->delete();

        $response = $this->actingAs($user)
            ->getJson(route('transactions.trashed', ['partial' => 1, 'search' => 'tidak-ada']));

        $response->assertOk()->assertJsonPath('total', 0);

        // Partial fetch harus membawa empty state yang benar juga, kalau tidak
        // user mengira filternya yang rusak setelah tabelnya diganti diam-diam.
        $this->assertStringContainsString('Tidak ada yang cocok', $response->json('tableHtml'));
        $this->assertStringNotContainsString('Sampah kosong', $response->json('tableHtml'));
    }

    public function test_sidebar_shows_how_many_transactions_wait_in_trash(): void
    {
        $user = User::factory()->create();
        $this->makeTransaction($user)->delete();
        $this->makeTransaction($user)->delete();

        $this->actingAs($user)->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('2 transaksi menunggu dipulihkan', escape: false);
    }

    public function test_sidebar_hides_trash_badge_when_trash_is_empty(): void
    {
        $user = User::factory()->create();

        // Angka 0 yang ditulis di badge cuma menambah kebisingan, dan bikin
        // menu Sampah terlihat seperti peringatan padahal tidak ada apa-apa.
        $this->actingAs($user)->get(route('transactions.index'))
            ->assertOk()
            ->assertDontSee('0 transaksi menunggu dipulihkan', escape: false);
    }

    // =====================================================================
    // AKSI MASSAL
    // =====================================================================

    public function test_bulk_restore_brings_several_transactions_back(): void
    {
        $user = User::factory()->create();
        $a = $this->makeTransaction($user, ['title' => 'PULIHKAN A']);
        $b = $this->makeTransaction($user, ['title' => 'PULIHKAN B']);
        $a->delete();
        $b->delete();

        $this->actingAs($user)
            ->post(route('transactions.bulk-restore'), ['ids' => [$a->id, $b->id]])
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('success');

        $this->assertNotSoftDeleted('transactions', ['id' => $a->id]);
        $this->assertNotSoftDeleted('transactions', ['id' => $b->id]);
    }

    public function test_bulk_restore_accepts_comma_separated_ids(): void
    {
        // Toolbar bulk menuliskan pilihan lewat satu hidden input dari state
        // Alpine, jadi bentuknya CSV — bukan `ids[]`.
        $user = User::factory()->create();
        $a = $this->makeTransaction($user);
        $b = $this->makeTransaction($user);
        $a->delete();
        $b->delete();

        $this->actingAs($user)
            ->post(route('transactions.bulk-restore'), ['ids' => $a->id.','.$b->id])
            ->assertRedirect(route('transactions.trashed'));

        $this->assertNotSoftDeleted('transactions', ['id' => $a->id]);
        $this->assertNotSoftDeleted('transactions', ['id' => $b->id]);
    }

    public function test_bulk_restore_ignores_other_users_and_active_transactions(): void
    {
        // Batas keamanan: id dari form tidak boleh menyentuh baris orang lain,
        // dan tidak boleh menyentuh transaksi yang masih aktif.
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $foreign = $this->makeTransaction($owner, ['title' => 'MILIK ORANG LAIN']);
        $foreign->delete();

        $active = $this->makeTransaction($owner, ['title' => 'MASIH AKTIF']);

        $mine = $this->makeTransaction($intruder, ['title' => 'MILIK SAYA']);
        $mine->delete();

        $this->actingAs($intruder)
            ->post(route('transactions.bulk-restore'), ['ids' => [$foreign->id, $active->id, $mine->id]])
            ->assertRedirect(route('transactions.trashed'));

        $this->assertSoftDeleted('transactions', ['id' => $foreign->id]);
        $this->assertNotSoftDeleted('transactions', ['id' => $active->id]);
        $this->assertNotSoftDeleted('transactions', ['id' => $mine->id]);
    }

    public function test_bulk_restore_with_no_selection_is_a_no_op(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);
        $transaction->delete();

        $this->actingAs($user)
            ->post(route('transactions.bulk-restore'), ['ids' => []])
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('error');

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_bulk_destroy_ignores_duplicate_ids(): void
    {
        // Checkbox desktop dan mobile memakai `ids[]` yang sama, jadi satu
        // transaksi bisa terkirim dua kali. Kalau tidak dibuang, jumlah yang
        // dilaporkan ke user akan lebih besar dari kenyataan.
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);
        $transaction->delete();

        $this->actingAs($user)
            ->post(route('transactions.bulk-destroy'), ['ids' => [$transaction->id, $transaction->id, $transaction->id]])
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('success', '1 transaksi dihapus permanen.');
    }

    public function test_bulk_destroy_removes_receipts(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $withReceipt = $this->makeTransaction($user, ['image' => 'receipts/a.jpg']);
        $withoutReceipt = $this->makeTransaction($user);
        Storage::disk('public')->put('receipts/a.jpg', 'data');

        $withReceipt->delete();
        $withoutReceipt->delete();

        $this->actingAs($user)->post(route('transactions.bulk-destroy'), [
            'ids' => [$withReceipt->id, $withoutReceipt->id],
        ]);

        // Bulk harus menghapus bukti sama seperti forceDestroy() tunggal --
        // kalau tidak, storage akan penuh file yatim.
        $this->assertFalse(Storage::disk('public')->exists('receipts/a.jpg'));
        $this->assertDatabaseMissing('transactions', ['id' => $withReceipt->id]);
        $this->assertDatabaseMissing('transactions', ['id' => $withoutReceipt->id]);
    }

    public function test_empty_trash_removes_everything_but_only_with_exact_confirmation(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $a = $this->makeTransaction($user, ['image' => 'receipts/x.jpg']);
        $b = $this->makeTransaction($user);
        Storage::disk('public')->put('receipts/x.jpg', 'data');
        $a->delete();
        $b->delete();

        // Konfirmasi yang salah TIDAK boleh menghapus apa pun: aksi ini
        // jangkauan penuhnya ditentukan server, bukan user.
        $this->actingAs($user)
            ->post(route('transactions.empty-trash'), ['confirm' => 'hapus'])
            ->assertRedirect(route('transactions.trashed'))
            ->assertSessionHas('error');

        $this->assertSoftDeleted('transactions', ['id' => $a->id]);
        $this->assertSoftDeleted('transactions', ['id' => $b->id]);

        $this->actingAs($user)
            ->post(route('transactions.empty-trash'), ['confirm' => TransactionController::EMPTY_TRASH_CONFIRMATION])
            ->assertRedirect(route('transactions.trashed'));

        $this->assertDatabaseMissing('transactions', ['id' => $a->id]);
        $this->assertDatabaseMissing('transactions', ['id' => $b->id]);
        $this->assertFalse(Storage::disk('public')->exists('receipts/x.jpg'));
    }

    public function test_empty_trash_spares_other_users_transactions(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $foreign = $this->makeTransaction($owner);
        $foreign->delete();

        $this->actingAs($intruder)
            ->post(route('transactions.empty-trash'), ['confirm' => TransactionController::EMPTY_TRASH_CONFIRMATION]);

        $this->assertSoftDeleted('transactions', ['id' => $foreign->id]);
    }

    public function test_demo_user_cannot_use_bulk_or_empty_actions(): void
    {
        $demo = User::factory()->create(['email' => config('demo.email')]);
        $transaction = $this->makeTransaction($demo);
        $transaction->delete();

        $this->actingAs($demo)
            ->post(route('transactions.bulk-restore'), ['ids' => [$transaction->id]])
            ->assertSessionHas('error');
        $this->actingAs($demo)
            ->post(route('transactions.bulk-destroy'), ['ids' => [$transaction->id]])
            ->assertSessionHas('error');
        $this->actingAs($demo)
            ->post(route('transactions.empty-trash'), ['confirm' => TransactionController::EMPTY_TRASH_CONFIRMATION])
            ->assertSessionHas('error');

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_guest_cannot_use_bulk_or_empty_actions(): void
    {
        $this->post(route('transactions.bulk-restore'), ['ids' => [1]])->assertRedirect(route('login'));
        $this->post(route('transactions.bulk-destroy'), ['ids' => [1]])->assertRedirect(route('login'));
        $this->post(route('transactions.empty-trash'))->assertRedirect(route('login'));
    }

    public function test_restore_offers_undo_but_bulk_restore_does_not(): void
    {
        $user = User::factory()->create();
        $single = $this->makeTransaction($user, ['title' => 'SATU SAJA']);
        $single->delete();

        $this->actingAs($user)
            ->post(route('transactions.restore', $single))
            ->assertSessionHas('undo_restore', ['id' => $single->id, 'title' => 'SATU SAJA']);

        // Memulihkan 40 transaksi sekaligus: "Urungkan" jadi tidak bermakna,
        // user tidak ingat item mana yang salah.
        $many = collect(range(1, 3))->map(fn ($i) => $this->makeTransaction($user, ['title' => 'Item '.$i]));
        $many->each->delete();

        $response = $this->actingAs($user)
            ->post(route('transactions.bulk-restore'), ['ids' => $many->pluck('id')->all()]);

        $response->assertSessionMissing('undo_restore');
    }

    public function test_undo_puts_the_transaction_back_in_trash(): void
    {
        $user = User::factory()->create();
        $transaction = $this->makeTransaction($user);
        $transaction->delete();

        $this->actingAs($user)->post(route('transactions.restore', $transaction));
        $this->assertNotSoftDeleted('transactions', ['id' => $transaction->id]);

        // "Urungkan" memakai aksi soft delete yang sudah ada -- bukan endpoint
        // baru, supaya jalur auditnya sama.
        $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_bulk_actions_are_logged_in_the_audit_trail(): void
    {
        $user = User::factory()->create();
        $restored = $this->makeTransaction($user);
        $destroyed = $this->makeTransaction($user);
        $restored->delete();
        $destroyed->delete();

        $this->actingAs($user)->post(route('transactions.bulk-restore'), ['ids' => [$restored->id]]);
        $this->actingAs($user)->post(route('transactions.bulk-destroy'), ['ids' => [$destroyed->id]]);

        // Aksi massal tidak boleh jadi lubang di riwayat: tiap baris tetap
        // dicatat lewat observer yang sama dengan aksi tunggal.
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id, 'action' => 'restored', 'model_id' => $restored->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id, 'action' => 'force_deleted', 'model_id' => $destroyed->id,
        ]);
    }
}
