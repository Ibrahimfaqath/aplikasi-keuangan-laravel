<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\AmountFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function __construct(private AmountFormatter $amountFormatter) {}

    /**
     * Menyimpan (membuat atau memperbarui) anggaran pengeluaran bulan berjalan.
     */
    public function store(Request $request)
    {
        // Terima format Rupiah ramah pengguna ("Rp 1.500.000", "1.500.000", "1500000")
        $request->merge(['amount' => $this->amountFormatter->normalize($request->input('amount'))]);

        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $now = Carbon::now();

        Budget::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'month' => $now->month,
                'year' => $now->year,
            ],
            ['amount' => $request->amount]
        );

        return redirect()->back()->with('success', 'Anggaran bulan ini berhasil disimpan!');
    }
}
