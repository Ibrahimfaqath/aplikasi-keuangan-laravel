<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    /**
     * Menyimpan (membuat atau memperbarui) anggaran pengeluaran bulan berjalan.
     */
    public function store(Request $request)
    {
        // Terima format Rupiah ramah pengguna ("Rp 1.500.000", "1.500.000", "1500000")
        $request->merge(['amount' => $this->normalizeAmount($request->input('amount'))]);

        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $now = Carbon::now();

        Budget::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'month'   => $now->month,
                'year'    => $now->year,
            ],
            ['amount' => $request->amount]
        );

        return redirect()->back()->with('success', 'Anggaran bulan ini berhasil disimpan!');
    }

    /**
     * Ubah input nominal mentah menjadi angka float murni.
     * "Rp 1.500.000" -> 1500000, "25000,50" -> 25000.50, "5000000" -> 5000000.
     */
    private function normalizeAmount($value): float
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
