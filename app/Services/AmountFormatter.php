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
}
