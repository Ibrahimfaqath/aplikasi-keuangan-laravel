<?php

namespace App\Services;

class AmountFormatter
{
    /**
     * Ubah input nominal mentah menjadi angka float murni.
     * "Rp 1.500.000" -> 1500000, "25000,50" -> 25000.50, "5000000" -> 5000000.
     *
     * Return null kalau tidak ada digit sama sekali (input kosong),
     * agar validasi `required` gagal instead of diam-diam jadi 0.
     * Tanda minus dipertahankan agar validasi `min` yang menolaknya.
     */
    public function normalize(mixed $value): ?float
    {
        $raw = (string) $value;

        if (trim($raw) === '') {
            return null;
        }

        $isNegative = str_contains($raw, '-');

        $s = preg_replace('/[^0-9.,]/', '', $raw);

        if ($s === null || $s === '') {
            return null;
        }

        if (str_contains($s, ',')) {
            // Format Indonesia: titik = ribuan, koma = desimal
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (substr_count($s, '.') >= 2) {
            // "2.500.000" -> ribuan semua
            $s = str_replace('.', '', $s);
        } elseif (substr_count($s, '.') === 1) {
            // Satu titik: bedakan ribuan vs desimal Inggris.
            // "2.500" / "25.000" -> 2500, tapi "25000.50" -> desimal.
            if (preg_match('/^\d{1,3}\.\d{3}$/', $s)) {
                $s = str_replace('.', '', $s);
            }
        }

        if ($s === '' || $s === '.' || $s === '-') {
            return null;
        }

        $amount = round((float) $s, 2);

        return $isNegative ? -abs($amount) : $amount;
    }

    /**
     * Format penuh ala Indonesia: 1500000 -> "Rp 1.500.000".
     */
    public static function format(int|float $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    /**
     * Format ringkas untuk nominal besar (sama dengan rupiahCompact di
     * dashboard Alpine): di bawah 1 juta tampil penuh, dari 1 juta ke atas
     * dipadatkan per besaran (juta -> miliar -> triliun). Tulisan dibuat
     * lengkap agar tidak ambigu dengan singkatan yang bisa salah baca.
     * 1500000 -> "Rp 1,5 juta", 1.500.000.000 -> "Rp 1,5 miliar".
     */
    public static function compact(int|float $value): string
    {
        $abs = abs((float) $value);

        if ($abs < 1_000_000) {
            return self::format($abs);
        }

        $units = [
            [1e12, 'triliun'],
            [1e9, 'miliar'],
            [1e6, 'juta'],
        ];

        foreach ($units as [$base, $word]) {
            if ($abs >= $base) {
                $frac = $abs < $base * 100 ? 2 : 0;
                $val = number_format($abs / $base, $frac, ',', '.');
                if ($frac === 2) {
                    $val = rtrim(rtrim($val, '0'), ',');
                }

                return 'Rp '.$val.' '.$word;
            }
        }

        return self::format($abs);
    }
}
