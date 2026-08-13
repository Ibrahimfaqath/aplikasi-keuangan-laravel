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
}
