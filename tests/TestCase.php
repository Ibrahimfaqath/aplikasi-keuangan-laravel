<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Selalu pakai SQLite in-memory, apa pun isi environment.
     *
     * Kenapa tidak cukup andalkan `<env name="DB_DATABASE" value=":memory:"/>`
     * di phpunit.xml: PHPUnit 12 TIDAK menimpa variabel yang sudah ada di
     * shell, jadi `DB_DATABASE=/path/database.sqlite php artisan test` membuat
     * `RefreshDatabase` mengosongkan file database yang sedang dipakai preview
     * lokal. Itu sudah kejadian sekali dan seluruh data demo ikut hilang.
     *
     * Dicek di `createApplication()` -- setelah config dibaca tapi SEBELUM ada
     * query apa pun, jadi tidak perlu disconnect/purge (yang justru bikin
     * test suite lambat sekali karena DB in-memory ikut ter-reset tiap test).
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $default = config('database.default');
        $database = config("database.connections.{$default}.database");

        // Hanya file .sqlite lokal yang berbahaya: MySQL produksi tidak pernah
        // di-purge RefreshDatabase dengan cara yang sama, dan sengaja tidak
        // disentuh di sini.
        if (is_string($database) && str_ends_with($database, '.sqlite')) {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => ':memory:',
            ]);
        }

        return $app;
    }
}
