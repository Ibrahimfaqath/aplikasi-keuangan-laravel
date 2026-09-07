<?php

namespace App\Services;

class AmountFormatter
{
    /**
     * Ubah input nominal mentah menjadi angka float murni.
     * "Rp 1.500.000" -> 1500000, "25000,50" -> 25000.50, "5000000" -> 5000000.
     */
    public function normalize(mixed $value): float
    {
        $s = preg_replace('/[^0-9.,]/', '', (string) $value);

        if (str_contains($s, ',')) {
            // Format Indonesia: titik = ribuan, koma = desimal
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace('.', '', $s);
        }

        return round((float) $s, 2);
    }
}
