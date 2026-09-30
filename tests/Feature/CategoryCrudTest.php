<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DemoMode;
use App\Support\CategoryStyle;
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

    public function test_index_menampilkan_kategori_bawaan_bahkan_tanpa_seeder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('Gaji');
        $response->assertSee('Makanan & Minuman');
        $response->assertSee('Belum dipakai');
        $response->assertViewHas('stats', fn (array $stats) => $stats['total'] === count(Transaction::INCOME_CATEGORIES) + count(Transaction::EXPENSE_CATEGORIES));
    }

    public function test_index_menampilkan_kategori_bawaan_dan_custom_beserta_pemakaiannya(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);

        Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        foreach (['Jualan Distro', 'Jualan Kaos'] as $title) {
            Transaction::create([
                'user_id' => $user->id,
                'title' => $title,
                'category' => 'Jualan Online',
                'amount' => 50000,
                'type' => 'income',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
            ]);
        }

        $response = $this->actingAs($user)->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('Jualan Online');
        $response->assertSee('2 transaksi');
        $bawaan = count(Transaction::INCOME_CATEGORIES) + count(Transaction::EXPENSE_CATEGORIES);

        $response->assertViewHas('stats', fn (array $stats) => $stats['total'] === $bawaan + 1
            && $stats['custom'] === 1
            && $stats['used'] === 1
            && $stats['unused'] === $bawaan);
    }

    public function test_index_tidak_membocorkan_kategori_custom_user_lain(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Category::create([
            'user_id' => $owner->id,
            'name' => 'Rahasia Bos',
            'type' => 'expense',
        ]);

        $this->actingAs($other)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertDontSee('Rahasia Bos');
    }

    public function test_available_category_falls_back_to_built_in_list(): void
    {
        $income = Category::availableFor(null, 'income');

        $this->assertSame(Transaction::categoriesFor('income'), array_column($income, 'name'));
        $this->assertTrue($income[0]['is_global']);
        $this->assertNull($income[0]['id']);
    }

    public function test_available_category_uses_seeded_global_rows_when_present(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);

        $income = Category::availableFor($user->id, 'income');

        $this->assertSame(Transaction::INCOME_CATEGORIES, array_column($income, 'name'));
        $this->assertNotNull($income[0]['id']);
        $this->assertTrue($income[0]['is_global']);
    }

    // ---- ubah kategori (rename / ganti jenis / ganti tampilan) ----

    public function test_rename_menarik_transaksi_lama_ikut_berganti(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Makananan',
            'type' => 'expense',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Kopi',
            'category' => 'Makananan',
            'amount' => 25000,
            'type' => 'expense',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->patch(route('categories.update', $category->id), [
                'name' => 'Makanan',
                'type' => 'expense',
            ])
            ->assertRedirect(route('categories.index'));

        // Ini inti dari fitur: tanpa cascade, transaksi lama akan nempel ke
        // "Makananan" selamanya dan kategorinya jadi yatim.
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'title' => 'Kopi',
            'category' => 'Makanan',
        ]);

        $this->assertDatabaseMissing('transactions', ['category' => 'Makananan']);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Makanan',
        ]);
    }

    public function test_rename_menarik_anggaran_kategori_ikut_berganti(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'expense',
        ]);

        Budget::create([
            'user_id' => $user->id,
            'amount' => 500000,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'category' => 'Jualan Online',
        ]);

        // Anggaran keseluruhan (category = '') TIDAK boleh ikut berubah.
        Budget::create([
            'user_id' => $user->id,
            'amount' => 2000000,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'category' => '',
        ]);

        $this->actingAs($user)
            ->patch(route('categories.update', $category->id), [
                'name' => 'Jualan Resmi',
                'type' => 'expense',
            ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category' => 'Jualan Resmi',
        ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category' => '',
        ]);
    }

    public function test_rename_tidak_menyentuh_transaksi_user_lain(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $category = Category::create([
            'user_id' => $owner->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        Transaction::create([
            'user_id' => $other->id,
            'title' => 'Jualan',
            'category' => 'Jualan Online',
            'amount' => 50000,
            'type' => 'income',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->actingAs($owner)->patch(route('categories.update', $category->id), [
            'name' => 'Jualan Resmi',
            'type' => 'income',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $other->id,
            'category' => 'Jualan Online',
        ]);
    }

    public function test_ganti_jenis_memindahkan_transaksi(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Refund',
            'type' => 'expense',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'title' => 'Refund Tokopedia',
            'category' => 'Refund',
            'amount' => 75000,
            'type' => 'expense',
            'transaction_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $this->actingAs($user)->patch(route('categories.update', $category->id), [
            'name' => 'Refund',
            'type' => 'income',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'category' => 'Refund',
            'type' => 'income',
        ]);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'type' => 'income']);
    }

    public function test_ganti_jenis_ditolak_saat_ada_anggaran(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Belanja',
            'type' => 'expense',
        ]);

        Budget::create([
            'user_id' => $user->id,
            'amount' => 500000,
            'month' => Carbon::now()->month,
            'year' => Carbon::now()->year,
            'category' => 'Belanja',
        ]);

        $this->actingAs($user)
            ->patch(route('categories.update', $category->id), [
                'name' => 'Belanja',
                'type' => 'income',
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'type' => 'expense']);
    }

    public function test_simpan_tanpa_ubah_nama_tidak_ditolak_sebagai_duplikat(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        // Cuma ganti warna — nama tidak disentuh sama sekali.
        $this->actingAs($user)
            ->patch(route('categories.update', $category->id), [
                'name' => 'Jualan Online',
                'type' => 'income',
                'color' => 'violet',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Jualan Online',
            'color' => 'violet',
        ]);
    }

    public function test_rename_ke_nama_yang_sudah_terpakai_ditolak(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);
        Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Resmi',
            'type' => 'income',
        ]);

        $this->actingAs($user)
            ->patch(route('categories.update', $category->id), [
                'name' => 'jualan resmi',
                'type' => 'income',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Jualan Online']);
    }

    public function test_tidak_bisa_ubah_kategori_user_lain(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $category = Category::create([
            'user_id' => $owner->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        $this->actingAs($attacker)
            ->patch(route('categories.update', $category->id), [
                'name' => 'Dibajak',
                'type' => 'income',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Jualan Online']);
    }

    public function test_tidak_bisa_ubah_kategori_bawaan(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);
        $global = Category::whereNull('user_id')->where('name', 'Gaji')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('categories.update', $global->id), [
                'name' => 'Gaji Bulanan',
                'type' => 'income',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('categories', ['id' => $global->id, 'name' => 'Gaji']);
    }

    public function test_demo_user_tidak_bisa_ubah_kategori(): void
    {
        $user = User::factory()->create(['email' => config('demo.email')]);
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        $this->actingAs($user)
            ->patch(route('categories.update', $category->id), [
                'name' => 'Hack',
                'type' => 'income',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', DemoMode::ERROR);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Jualan Online']);
    }

    public function test_warna_dan_ikon_disimpan_saat_buat_kategori(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/categories', [
            'name' => 'Jualan Online',
            'type' => 'income',
            'color' => 'violet',
            'icon' => 'piggy',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'color' => 'violet',
            'icon' => 'piggy',
        ]);
    }

    public function test_warna_di_luar_palet_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/categories', [
            'name' => 'Jualan Online',
            'type' => 'income',
            'color' => 'ungu-tua',
        ])->assertSessionHasErrors('color');
    }

    public function test_kategori_tanpa_warna_tetap_dapat_warna_dan_ikon(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);

        $income = Category::availableFor($user->id, 'income');
        $gaji = collect($income)->firstWhere('name', 'Gaji');

        // Bawaan harus dapat warna yang sudah ditentukan, bukan fallback.
        $this->assertSame('green', $gaji['color']);
        $this->assertSame('wallet', $gaji['icon']);

        $expense = Category::availableFor($user->id, 'expense');
        $lainnya = collect($expense)->firstWhere('name', 'Lainnya');

        // "Lainnya" tidak punya warna khusus, tapi TIDAK BOLEH jadi netral
        // yang indistinguishable dari "kategori tanpa identitas".
        $this->assertNotSame('neutral', $lainnya['color']);
        $this->assertNotEmpty($lainnya['icon']);
    }

    public function test_kategori_custom_tanpa_pilihan_warna_tetap_berwarna(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/categories', [
            'name' => 'Jualan Online',
            'type' => 'income',
        ]);

        $row = Category::where('user_id', $user->id)->where('name', 'Jualan Online')->firstOrFail();

        $this->assertNotSame('neutral', $row->color);
        $this->assertNotEmpty($row->icon);
    }

    public function test_seeder_kategori_bawaan_tidak_menggandakan_baris(): void
    {
        // Regresi: seeder lama memakai insertOrIgnore, tapi index unik
        // (user_id, name, type) tidak berlaku untuk user_id NULL sehingga
        // setiap deploy menambah 15 baris duplikat.
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $expected = count(Transaction::INCOME_CATEGORIES) + count(Transaction::EXPENSE_CATEGORIES);

        $this->assertDatabaseCount('categories', $expected);
    }

    public function test_index_menampilkan_warna_dan_ikon_kategori_custom(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);

        Category::create([
            'user_id' => $user->id,
            'name' => 'Jualan Online',
            'type' => 'income',
            'color' => 'violet',
            'icon' => 'piggy',
        ]);

        $this->actingAs($user)->get(route('categories.index'))
            ->assertOk()
            ->assertSee('bg-violet-50')
            // false = jangan escape; yang dicari path SVG mentah, bukan
            // versi HTML-escaped-nya.
            ->assertSee(trim(CategoryStyle::iconPath('piggy')), false);
    }

    // ---- Regression guard ----
    // Tiga bug di halaman ini lolos dari test biasa karena gejalanya hanya
    // muncul di browser (parsing Alpine, atribut SVG, tombol disabled).
    // Guard di bawah menangkap ketiganya di level render.

    public function test_tidak_ada_php_yang_bocor_ke_binding_alpine(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        // Blade hanya mengevaluasi PHP di dalam {{ }} / {!! !!} / @php.
        // `$errors->has('x')` yang ditulis polos di nilai atribut akan masuk
        // mentah ke parser Alpine dan melempar SyntaxError ("Unexpected
        // token '>'") setiap kali halaman dimuat.
        //
        // Hanya nilai atribut yang dicek: `$errors` boleh muncul di dalam
        // komentar JS, karena komentar tidak pernah dieksekusi.
        preg_match_all('/=\s*"([^"]*)"/', $html, $matches);

        foreach ($matches[1] as $value) {
            foreach (['$errors', '$stats', '$usage', '$isDemo', '$groups'] as $variable) {
                $this->assertStringNotContainsString(
                    $variable,
                    $value,
                    "PHP  {$variable} bocor ke nilai atribut HTML: ".trim(substr($value, 0, 90))
                );
            }
        }
    }

    public function test_payload_ikon_ke_alpine_hanya_path_data(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);

        $html = $this->actingAs($user)->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        // Ikon disimpan sebagai markup `<path .../>` utuh, tapi Alpine
        // mengikat nilainya ke atribut `d`. Kalau yang dikirim markup, hasil
        //nya `d="<path ..."` — tidak valid, dan preview di modal maupun
        //seluruh ikon di picker tidak tergambar.
        //
        // Di dalam HTML, `<` pada payload JS ter-escape jadi \u003C, jadi
        // cukup cari sisa-sisa markup SVG di halaman.
        $this->assertStringNotContainsString(
            'u003Cpath',
            $html,
            'markup SVG masuk ke payload Alpine; Alpine butuh atribut d polos, bukan <path>'
        );

        // Ikon juga harus benar-benar terkirim, bukan kosong diam-diam.
        $this->assertStringContainsString('iconD()', $html);
        $this->assertStringContainsString('iconSet[key]', $html);
    }

    public function test_atribut_d_pada_svg_bersifat_path_data(): void
    {
        $user = User::factory()->create();
        $this->seed(CategorySeeder::class);

        $html = $this->actingAs($user)->get(route('categories.index'))
            ->assertOk()
            ->getContent();

        // Ikon disimpan sebagai markup <path> utuh, tapi Alpine mengikat
        // nilainya ke atribut `d`. Kalau yang diikat markup, hasilnya
        // d="<path ..." — tidak valid, dan ikonnya tidak tergambar.
        // Setiap atribut d harus diawali moveto (M/m).
        preg_match_all('/\sd="([^"]*)"/', $html, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $d) {
            $d = trim($d);
            $this->assertMatchesRegularExpression(
                '/^[Mm]/',
                $d,
                'atribut d harus diawali moveto (M/m), bukan markup: '.substr($d, 0, 60)
            );
        }
    }

    public function test_semua_kategori_bawaan_punya_warna_dan_ikon_valid(): void
    {
        $this->seed(CategorySeeder::class);

        $globals = Category::whereNull('user_id')->get();

        $this->assertCount(15, $globals);

        foreach ($globals as $category) {
            $this->assertContains($category->color, array_keys(CategoryStyle::COLORS));
            $this->assertContains($category->icon, array_keys(CategoryStyle::ICONS));
        }
    }

    public function test_icon_d_set_terisi_untuk_seluruh_kunci(): void
    {
        $set = CategoryStyle::iconDSet();

        $this->assertSame(array_keys(CategoryStyle::ICONS), array_keys($set));

        foreach ($set as $key => $d) {
            $this->assertMatchesRegularExpression(
                '/^[Mm]/',
                $d,
                "ikon {$key} tidak menghasilkan path data yang valid"
            );
        }
    }
}
