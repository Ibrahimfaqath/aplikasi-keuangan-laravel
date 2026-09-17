<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class DemoMode
{
    final public const ERROR = 'Akun demo hanya untuk mencoba aplikasi. Silakan daftar akun sendiri untuk menambah, mengubah, atau menghapus data.';

    public static function isEnabled(): bool
    {
        return (bool) config('demo.enabled', false);
    }

    public static function isDemoUser(?User $user): bool
    {
        return $user !== null && $user->email === config('demo.email');
    }

    public static function warn(): RedirectResponse
    {
        return redirect()->back()->with('error', self::ERROR);
    }

    public static function warnJson(): JsonResponse
    {
        return response()->json(['message' => self::ERROR], 403);
    }
}
