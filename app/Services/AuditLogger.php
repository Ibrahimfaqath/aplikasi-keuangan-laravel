<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * Sumber yang dipasang sementara sebelum pencatatan berikutnya (dikonsumsi
     * satu kali). Dipakai oleh AiController supaya transaksi buatan AI tercatat
     * sebagai sumber "ai".
     */
    private static ?string $sourceOverride = null;

    public static function begin(string $source): void
    {
        self::$sourceOverride = $source;
    }

    public static function record(
        ?int $userId,
        string $action,
        ?Model $model,
        string $description,
        array $old = [],
        array $new = [],
        ?string $label = null,
    ): ?AuditLog {
        $userId ??= Auth::id();

        if ($userId === null) {
            return null;
        }

        $source = self::$sourceOverride ?? self::defaultSource($userId);
        self::$sourceOverride = null;

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $model?->getMorphClass(),
            'model_id' => $model?->getKey(),
            'label' => $label,
            'description' => $description,
            'old_values' => $old !== [] ? $old : null,
            'new_values' => $new !== [] ? $new : null,
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 500),
            'source' => $source,
        ]);
    }

    private static function defaultSource(int $userId): string
    {
        $user = $userId === Auth::id() ? Auth::user() : User::find($userId);

        return $user !== null && DemoMode::isDemoUser($user) ? 'demo' : 'web';
    }
}
