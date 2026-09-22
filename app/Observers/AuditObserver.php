<?php

namespace App\Observers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class AuditObserver
{
    /**
     * Kolom yang tidak pernah disimpan ke audit log / tidak muncul di diff.
     * - password & remember_token: rahasia, jangan pernah disalin ke tabel log.
     * - updated_at & deleted_at: noise (tiang waktu yang berubah sendiri).
     */
    protected array $excludedColumns = ['password', 'remember_token', 'updated_at', 'deleted_at'];

    /** Label pendek, mis. judul transaksi. */
    abstract protected function label(Model $model): string;

    /** Detail khas model, mis. judul + nominal + kategori. */
    abstract protected function detail(Model $model): string;

    public function created(Model $model): void
    {
        $this->log('created', 'Tambah', $model, [], $this->attributes($model));
    }

    public function updated(Model $model): void
    {
        if ($model->isDirty('deleted_at')) {
            // Soft delete / restore dibalut update internal — ditangani event deleted/restored.
            return;
        }

        [$old, $new] = $this->changes($model);
        if ($new === []) {
            return;
        }

        $this->log('updated', 'Ubah', $model, $old, $new, array_keys($new));
    }

    public function deleted(Model $model): void
    {
        // Akun yang dihapus permanen: row user sudah hilang dari DB saat event
        // ini berjalan, jadi baris log ber-user_id yang mengacu akun itu akan
        // melanggar FK. Peristiwa akun lain tetap terdokumentasi via auth login/
        // logout/register.
        if ($model instanceof User) {
            return;
        }

        $this->log('deleted', 'Hapus', $model, $this->attributes($model), []);
    }

    public function restored(Model $model): void
    {
        $this->log('restored', 'Pulihkan', $model, [], $this->attributes($model));
    }

    public function forceDeleted(Model $model): void
    {
        $this->log('force_deleted', 'Hapus permanen', $model, $this->attributes($model), []);
    }

    protected function log(
        string $action,
        string $verb,
        Model $model,
        array $old,
        array $new,
        ?array $changed = null,
        ?int $userId = null,
    ): void {
        $suffix = $changed ? ' (perubahan: '.implode(', ', $changed).')' : '';

        AuditLogger::record(
            $userId ?? $this->userId($model),
            $action,
            $model,
            $verb.' '.$this->label($model).': '.$this->detail($model).$suffix,
            $old,
            $new,
            $this->label($model),
        );
    }

    protected function userId(Model $model): ?int
    {
        $userId = $model->getAttribute('user_id');

        return $userId !== null ? (int) $userId : null;
    }

    protected function softDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    protected function attributes(Model $model): array
    {
        return $this->filtered($model->getAttributes());
    }

    protected function changes(Model $model): array
    {
        $old = [];
        $new = [];
        foreach ($model->getChanges() as $column => $value) {
            if (in_array($column, $this->excludedColumns, true)) {
                continue;
            }
            $old[$column] = $model->getOriginal($column);
            $new[$column] = $model->getAttribute($column);
        }

        return [$old, $new];
    }

    protected function filtered(array $attributes): array
    {
        return array_diff_key($attributes, array_flip($this->excludedColumns));
    }
}
