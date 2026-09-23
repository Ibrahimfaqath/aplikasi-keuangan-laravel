<?php

namespace App\Services;

use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

class CsvImportService
{
    public const MAX_ROWS = 500;

    /**
     * Alias nama kolom (sudah dinormalisasi: huruf kecil, spasi rapat).
     * Kunci = field internal, nilai = turunan dari header kolom.
     *
     * @var array<string, list<string>>
     */
    private const COLUMN_ALIASES = [
        'date' => [
            'tanggal', 'tanggal transaksi', 'tanggal_transaksi', 'tanggaltransaksi',
            'date', 'transaction date', 'transaction_date', 'tanggal (yyyy-mm-dd)',
            'tanggal transaksi (yyyy-mm-dd)',
        ],
        'title' => [
            'keterangan', 'judul', 'keterangan / judul', 'keterangan/judul',
            'description', 'title', 'nama', 'deskripsi',
        ],
        'category' => [
            'kategori', 'category',
        ],
        'type' => [
            'jenis', 'jenis transaksi', 'jenis_transaksi', 'jenistransaksi',
            'tipe', 'type', 'jenis transaksi (pemasukan/pengeluaran)',
        ],
        'amount' => [
            'nominal', 'nominal (rp)', 'nominal_rp', 'nominal(rp)',
            'amount', 'jumlah', 'jumlah (rp)', 'nominal (idr)',
        ],
    ];

    public function __construct(private AmountFormatter $amountFormatter) {}

    /**
     * Baca isi file CSV: deteksi delimiter, pecah baris, petakan header.
     *
     * @return array{rows: array<int, array<int, string>>, map: array<string, int>}
     *
     * @throws \InvalidArgumentException Bila file kosong / bukan CSV yang bisa dibaca.
     */
    public function parse(UploadedFile $file): array
    {
        $raw = $file->get();
        if (! is_string($raw) || $raw === '') {
            throw new \InvalidArgumentException('File kosong. Tidak ada data untuk diimpor.');
        }

        // Hilangkan BOM UTF-8 (hasil export Excel kadang membawa BOM).
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        $delimiter = $this->detectDelimiter($raw);

        $rows = [];
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $raw);
        rewind($stream);
        while (($cells = fgetcsv($stream, 0, $delimiter)) !== false) {
            $cells = array_map(static fn ($cell) => is_string($cell) ? trim($cell) : '', $cells);
            if (count($cells) === 1 && trim($cells[0] ?? '') === '') {
                continue;
            }
            $rows[] = $cells;
        }
        fclose($stream);

        if ($rows === []) {
            throw new \InvalidArgumentException('File kosong. Tidak ada data untuk diimpor.');
        }

        $header = $this->normalizeCells($rows[0]);
        $map = [];
        foreach ($header as $index => $head) {
            $key = $this->normalizeKey($head);
            foreach (self::COLUMN_ALIASES as $field => $aliases) {
                if (in_array($key, $aliases, true)) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return compact('rows', 'map');
    }

    /**
     * Normalisasi + validasi setiap baris data.
     *
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, int>  $map
     * @return array{valid: list<array<string, mixed>>, invalid: list<array{row: int, errors: list<string>}>}
     */
    public function importRows(array $rows, array $map, int $userId): array
    {
        $missing = array_diff(['date', 'title', 'category', 'amount'], array_keys($map));
        if ($missing !== []) {
            $labels = [
                'date' => 'Tanggal Transaksi',
                'title' => 'Keterangan / Judul',
                'category' => 'Kategori',
                'amount' => 'Nominal',
            ];
            $names = implode(', ', array_map(fn ($f) => $labels[$f], $missing));

            throw new \InvalidArgumentException(
                'Kolom berikut tidak ditemukan di baris pertama: '.$names.'. '
                .'Baris pertama file harus berisi nama kolom. Gunakan tombol "Unduh Contoh Template" sebagai acuan.'
            );
        }

        $dataRows = array_slice($rows, 1);
        if (count($dataRows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException(
                'File berisi '.count($dataRows).' baris data. Maksimal '.self::MAX_ROWS.' baris per import.'
            );
        }

        $available = [
            'income' => Category::namesFor($userId, 'income'),
            'expense' => Category::namesFor($userId, 'expense'),
        ];

        $valid = [];
        $invalid = [];

        foreach ($dataRows as $rowNo => $cells) {
            $get = fn (string $field): string => isset($map[$field])
                ? trim((string) ($cells[$map[$field]] ?? ''))
                : '';

            $title = $get('title');
            $categoryRaw = $get('category');
            $amountRaw = $get('amount');
            $dateRaw = $get('date');
            $typeRaw = $get('type');

            $errors = [];

            if ($title === '') {
                $errors[] = 'Keterangan/Judul kosong.';
            }

            $date = $this->parseDate($dateRaw);
            if ($date === null) {
                $errors[] = 'Tanggal tidak valid: '.($dateRaw === '' ? '(kosong)' : $dateRaw);
            }

            $amount = $this->amountFormatter->normalize($amountRaw);
            if ($amount === null || abs((float) $amount) <= 0) {
                $errors[] = 'Nominal tidak valid: '.($amountRaw === '' ? '(kosong)' : $amountRaw);
            } else {
                // AmountFormatter mengawetkan tanda minus; di CSV tanda itu hanya
                // penentu jenis (lihat inferType), nominal yang disimpan selalu positif.
                $amount = round(abs((float) $amount), 2);
            }

            $type = $this->inferType($typeRaw, $amountRaw);
            if ($type === null) {
                $errors[] = $typeRaw === ''
                    ? 'Jenis transaksi tidak dikenali (kolom kosong). Isi "Pemasukan" atau "Pengeluaran".'
                    : 'Jenis transaksi tidak dikenali: "'.$typeRaw.'".';
            }

            $category = null;
            if ($type !== null && $categoryRaw !== '') {
                $category = $this->matchCategory($categoryRaw, $available[$type]);
                if ($category === null) {
                    $errors[] = 'Kategori "'.$categoryRaw.'" tidak tersedia untuk '.($type === 'income' ? 'Pemasukan' : 'Pengeluaran').'.';
                }
            } elseif ($categoryRaw === '') {
                $errors[] = 'Kategori kosong.';
            }

            if ($errors !== []) {
                $invalid[] = [
                    'row' => $rowNo + 2,
                    'errors' => $errors,
                ];

                continue;
            }

            $valid[] = [
                'transaction_date' => $date,
                'title' => $title,
                'category' => $category,
                'type' => $type,
                'amount' => $amount,
            ];
        }

        return compact('valid', 'invalid');
    }

    private function detectDelimiter(string $raw): string
    {
        $firstLine = strtok(str_replace(["\r\n", "\r"], "\n", $raw), "\n") ?: '';

        $best = ';';
        $bestCount = 0;
        foreach ([',', ';', "\t", '|'] as $candidate) {
            $count = count(str_getcsv($firstLine, $candidate));
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $candidate;
            }
        }

        return $bestCount > 1 ? $best : ';';
    }

    /**
     * @return list<string>
     */
    private function normalizeCells(array $cells): array
    {
        return array_values(array_map('strval', $cells));
    }

    private function normalizeKey(string $value): string
    {
        $value = trim($value);
        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = [
            'Y-m-d', 'Y-m-d H:i:s', 'Y-m-d H:i',
            'd/m/Y', 'd/m/Y H:i', 'd-m-Y', 'd.m.Y',
            'Y/m/d', 'j F Y', 'j M Y', 'F j, Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date instanceof Carbon && $date->format($format) === $value && $this->inRange($date)) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        try {
            $date = Carbon::parse($value);
            if ($this->inRange($date)) {
                return $date->format('Y-m-d');
            }
        } catch (\Throwable) {
            // tidak ter-parse
        }

        return null;
    }

    private function inRange(Carbon $date): bool
    {
        return $date->year >= 1970 && $date->year <= 2100;
    }

    private function inferType(string $typeRaw, string $amountRaw): ?string
    {
        $type = mb_strtolower(trim($typeRaw));

        if ($type !== '') {
            // "pengeluaran" berasal dari "keluar" tanpa huruf k — cek dengan daftar kata,
            // bukan hanya substr "keluar".
            foreach (['pemasukan', 'masuk', 'income', 'kredit', 'deposit'] as $word) {
                if (str_contains($type, $word)) {
                    return 'income';
                }
            }
            foreach (['pengeluaran', 'keluar', 'expense', 'debit', 'withdrawal', 'penarikan'] as $word) {
                if (str_contains($type, $word)) {
                    return 'expense';
                }
            }
            if (str_starts_with($type, '+')) {
                return 'income';
            }
            if (str_starts_with($type, '-')) {
                return 'expense';
            }

            return null;
        }

        // Tanpa kolom jenis: baca tanda nominal, "-5000" = pengeluaran.
        if (preg_match('/^\s*-/', $amountRaw)) {
            return 'expense';
        }
        if (preg_match('/^\s*\+/', $amountRaw)) {
            return 'income';
        }

        return null;
    }

    /**
     * Cari kategori user yang cocok case-insensitive; pulangkan nama kanonik.
     *
     * @param  list<string>  $available
     */
    private function matchCategory(string $raw, array $available): ?string
    {
        $normalized = mb_strtolower(trim($raw));
        if ($normalized === '') {
            return null;
        }

        foreach ($available as $name) {
            if (mb_strtolower($name) === $normalized) {
                return $name;
            }
        }

        return null;
    }
}
