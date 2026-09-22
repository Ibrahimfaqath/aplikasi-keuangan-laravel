<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const ACTION_LABELS = [
        'created' => 'Tambah',
        'updated' => 'Ubah',
        'deleted' => 'Hapus',
        'restored' => 'Pulihkan',
        'force_deleted' => 'Hapus permanen',
        'auth.login' => 'Login',
        'auth.logout' => 'Logout',
        'auth.register' => 'Registrasi',
    ];

    public const SOURCE_LABELS = [
        'web' => 'Web',
        'ai' => 'AI',
        'demo' => 'Demo',
        'system' => 'Sistem',
    ];

    /** Tabel imutabel: catatan riwayat tidak pernah diperbarui. */
    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'label', 'description',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'source', 'created_at',
    ];

    protected $casts = [
        'model_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? $this->source;
    }

    /**
     * Nama singkat model yang dicatat (mis. "Transaksi", "Anggaran").
     */
    public function modelName(): string
    {
        if ($this->model_type === null) {
            return 'Akun';
        }

        $base = class_basename($this->model_type);

        return match ($base) {
            'Transaction' => 'Transaksi',
            'Budget' => 'Anggaran',
            'Category' => 'Kategori',
            'User' => 'Profil',
            default => $base,
        };
    }
}
