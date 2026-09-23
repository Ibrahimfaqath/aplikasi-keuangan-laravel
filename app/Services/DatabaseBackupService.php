<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Backup database ke file .sql di storage/app/backups.
 *
 * Prioritas dump:
 *  1. `mysqldump` (jika driver MySQL/MariaDB dan binary tersedia) — hasil
 *     paling akurat (fK, index, charset) dan ringan memory.
 *  2. Fallback pure-PHP via PDO — agar tetap jalan di host yang memblokir
 *     `exec`/`mysqldump` maupun di koneksi non-MySQL (mis. SQLite saat test).
 *
 * Backup disimpan DI LUAR public/webroot sehingga tidak bisa diunduh publik.
 * Unduhan hanya lewat route ber-autentikasi (lihat BackupController).
 */
class DatabaseBackupService
{
    public const CONNECTION = 'mysql';

    /** Jumlah backup terakhir yang dipertahankan secara default. */
    public const DEFAULT_KEEP = 30;

    public function __construct(private FilesystemFactory $filesystem) {}

    /** Direktori tempat backup disimpan. */
    public function directory(): string
    {
        $disk = $this->filesystem->disk('local');

        return $disk->path('backups');
    }

    /**
     * Buat satu backup baru, lalu rapikan yang lama.
     *
     * @return array{filename: string, size: int, created_at: string}
     *
     * @throws \RuntimeException saat dump gagal total
     */
    public function take(int $keep = self::DEFAULT_KEEP): array
    {
        $dir = $this->directory();

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = 'dompetku-'.now()->format('Ymd_His').'-'.Str::lower(Str::random(4)).'.sql';
        $file = $dir.DIRECTORY_SEPARATOR.$filename;

        $sql = $this->sql();

        // Tulis atomik: file temp dulu, lalu rename — hindari file separuh
        // yang terlihat seandainya request terpotong di tengah-tengah.
        $tmp = $file.'.tmp';
        if (file_put_contents($tmp, $sql) === false) {
            throw new \RuntimeException('Gagal menulis file backup.');
        }
        if (! rename($tmp, $file)) {
            @unlink($tmp);
            throw new \RuntimeException('Gagal menyimpan file backup.');
        }

        $this->prune($keep);

        return [
            'filename' => $filename,
            'size' => (int) filesize($file),
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Daftar backup yang tersedia, terbaru di atas.
     *
     * @return Collection<int, array{filename: string, size: int, created_at: string}>
     */
    public function all(): Collection
    {
        $dir = $this->directory();

        if (! is_dir($dir)) {
            return collect();
        }

        return collect(glob($dir.'/*.sql') ?: [])
            ->map(fn (string $path): array => [
                'filename' => basename($path),
                'size' => (int) filesize($path),
                'created_at' => date('Y-m-d H:i:s', (int) filemtime($path)),
            ])
            ->sortByDesc('created_at')
            ->values();
    }

    /** Backup paling baru, atau null bila belum ada. */
    public function latest(): ?array
    {
        return $this->all()->first();
    }

    public function has(string $filename): bool
    {
        return preg_match('/^[A-Za-z0-9._-]+\.sql$/', $filename) === 1
            && is_file($this->pathFor($filename));
    }

    public function pathFor(string $filename): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.$filename;
    }

    /**
     * Hapus backup lama hingga tersisa `$keep` terbaru.
     */
    public function prune(int $keep = self::DEFAULT_KEEP): void
    {
        if ($keep < 0) {
            return;
        }

        $this->all()
            ->slice($keep)
            ->each(function (array $backup): void {
                $path = $this->pathFor($backup['filename']);
                if (is_file($path)) {
                    @unlink($path);
                }
            });
    }

    /**
     * Bangun teks SQL lengkap dari database aktif.
     */
    public function sql(): string
    {
        if ($this->isMysql()) {
            $dump = $this->sqlViaMysqldump();

            if ($dump !== null) {
                return $dump;
            }
        }

        return $this->sqlViaPhp();
    }

    private function connection(): array
    {
        return DB::connection()->getConfig();
    }

    private function isMysql(): bool
    {
        return in_array($this->connection()['driver'], ['mysql', 'mariadb'], true);
    }

    /**
     * @return string|null null saat mysqldump tidak tersedia / gagal
     */
    private function sqlViaMysqldump(): ?string
    {
        if (! function_exists('shell_exec') || ! function_exists('escapeshellarg')) {
            return null;
        }

        $c = $this->connection();
        $binary = $c['driver'] === 'mariadb' ? 'mariadb-dump' : 'mysqldump';

        $host = $c['host'] ?? '127.0.0.1';
        $port = $c['port'] ?? null;
        $socket = $c['unix_socket'] ?? null;

        $cmd = 'MYSQL_PWD='.escapeshellarg($c['password'] ?? '')
            .' '.$binary
            .' --no-tablespaces --single-transaction --quick'
            .' --host='.escapeshellarg((string) $host)
            .($socket !== null && $socket !== '' ? ' --socket='.escapeshellarg((string) $socket) : ' --port='.escapeshellarg((string) ($port ?: 3306)))
            .' --user='.escapeshellarg((string) ($c['username'] ?? ''))
            .' '.escapeshellarg((string) ($c['database'] ?? ''))
            .' 2>/dev/null';

        $output = @shell_exec($cmd);

        if (! is_string($output) || trim($output) === '') {
            return null;
        }

        return "SET NAMES 'utf8mb4';\n".$output;
    }

    private function sqlViaPhp(): string
    {
        $driver = DB::connection()->getDriverName();
        $lines = [];
        $lines[] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";';

        foreach ($this->tableNames($driver) as $table) {
            $lines[] = '';
            $lines[] = 'DROP TABLE IF EXISTS `'.$table.'`;';
            $lines[] = $this->createTableSql($table, $driver);
            $this->appendDataSql($table, $lines);
        }

        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines)."\n";
    }

    /** @return list<string> */
    private function tableNames(string $driver): array
    {
        $ignoredPrefix = $driver === 'sqlite' ? 'sqlite_' : '____';

        return collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (string $name): bool => ! Str::startsWith($name, $ignoredPrefix))
            ->values()
            ->all();
    }

    private function createTableSql(string $table, string $driver): string
    {
        // MySQL/MariaDB: gunakan DDL asli (idx, FK, charset, engine) agar restore sempurna.
        if ($driver === 'mysql') {
            $create = data_get(DB::select("SHOW CREATE TABLE `$table`"), 0);
            if ($create) {
                $ddl = collect((array) $create)->map(fn ($v) => (string) $v)->reverse()->first();

                return str_ends_with(trim($ddl), ';') ? $ddl : $ddl.';';
            }
        }

        // Driver lain (mis. SQLite saat test): bangun CREATE sederhana dari info kolom.
        $columns = Schema::getColumns($table);
        $parts = array_map(static function (array $col): string {
            $line = '  `'.$col['name'].'` '.strtoupper($col['type'] ?? 'varchar');
            if (($col['nullable'] ?? false) === false && ($col['auto_increment'] ?? false) !== true) {
                $line .= ' NOT NULL';
            }
            if (($col['auto_increment'] ?? false) === true) {
                $line .= ' AUTO_INCREMENT';
            }
            if (array_key_exists('default', $col) && $col['default'] !== null) {
                $default = is_string($col['default']) ? "'".str_replace("'", "''", $col['default'])."'" : $col['default'];
                $line .= " DEFAULT $default";
            }

            return $line;
        }, $columns);

        return "CREATE TABLE `$table` (\n".implode(",\n", $parts)."\n);";
    }

    /**
     * @param  list<string>  $lines
     */
    private function appendDataSql(string $table, array &$lines): void
    {
        $columns = Schema::getColumnListing($table);
        $colList = implode(', ', array_map(static fn (string $c) => '`'.$c.'`', $columns));

        $rows = 0;
        $this->eachRow($table, function ($row) use (&$lines, $colList, $table, &$rows): void {
            // Batch INSERT hingga 200 baris agar file tidak kebesaran untuk MySQL.
            if ($rows % 200 === 0 && $rows > 0) {
                $lines[] = ';';
            }
            if ($rows % 200 === 0) {
                $lines[] = '';
                $lines[] = 'INSERT INTO `'.$table.'` ('.$colList.') VALUES';
            } else {
                $lines[] = ',';
            }

            $values = array_map(static function ($value): string {
                if ($value === null) {
                    return 'NULL';
                }
                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }
                if (is_int($value) || is_float($value)) {
                    return (string) $value;
                }

                $pdo = DB::connection()->getPdo();

                return $pdo ? $pdo->quote((string) $value) : "'".str_replace(['\\', "'"], ['\\\\', "''"], (string) $value)."'";
            }, collect($row)->all());

            $lines[] = '  ('.implode(', ', $values).')';
            $rows++;
        });

        if ($rows >= 0) {
            $lines[] = ';';
        }
    }

    /**
     * Iterasi seluruh baris tabel dengan aman untuk chunk (pakai primary key),
     * dan jatuh ke SELECT penuh untuk tabel tanpa kolom "id".
     */
    private function eachRow(string $table, callable $callback): void
    {
        try {
            DB::table($table)
                ->orderByDesc('id')
                ->chunk(500, static function ($rows) use ($callback): void {
                    foreach ($rows as $row) {
                        $callback($row);
                    }
                });

            return;
        } catch (Throwable) {
            // lanjut ke pendekatan tanpa PK (komposit / view)
        }

        foreach (DB::table($table)->get() as $row) {
            $callback($row);
        }
    }
}
