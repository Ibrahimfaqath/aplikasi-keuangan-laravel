<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DemoMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        if (! DemoMode::isEnabled()) {
            return redirect()->route('login')->with('error', 'Mode demo sedang dinonaktifkan.');
        }

        $email = config('demo.email');

        $demo = User::where('email', $email)->first();

        if ($demo === null) {
            return redirect()->route('login')->with(
                'error',
                'Akun demo belum tersedia. Jalankan `php artisan db:seed --class=DemoDataSeeder` lalu coba lagi.'
            );
        }

        Auth::login($demo);
        $request->session()->regenerate();

        session()->forget('ai_messages');

        return redirect()->route('transactions.index')->with('success', 'Kamu masuk sebagai akun demo. Data bisa dilihat tapi tidak bisa diubah.');
    }
}
