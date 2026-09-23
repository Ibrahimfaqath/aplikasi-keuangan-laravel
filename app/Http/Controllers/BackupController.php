<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /**
     * Halaman cadangan hanya untuk pemilik aplikasi (lihat config/backup.php).
     * Pemilik dikonfigurasi lewat BACKUP_OWNER_EMAIL di .env produksi.
     * Selama belum diisi, seluruh halaman nonaktif (404) — backup tetap
     * berjalan otomatis lewat cron.
     */
    public function index(DatabaseBackupService $service)
    {
        $this->ensureOwner();

        return view('backups.index', [
            'backups' => $service->all(),
            'backupDir' => $service->directory(),
            'retentionKeep' => DatabaseBackupService::DEFAULT_KEEP,
        ]);
    }

    public function store(Request $request, DatabaseBackupService $service): RedirectResponse
    {
        $this->ensureOwner();

        $backup = $service->take();

        return redirect()
            ->route('backups.index')
            ->with('success', 'Backup berhasil dibuat: '.$backup['filename']);
    }

    /**
     * Unduh file backup. Nama dibatasi pola aman dan harus benar-benar ada.
     */
    public function download(DatabaseBackupService $service, string $filename): BinaryFileResponse|RedirectResponse
    {
        $this->ensureOwner();

        if (! $service->has($filename)) {
            abort(404);
        }

        return response()->download($service->pathFor($filename), $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Kota pintu: hanya akun dengan email pemilik yang boleh masuk.
     * Lainnya 404 (seakan halaman tidak pernah ada) — bukan pesan error.
     */
    private function ensureOwner(): void
    {
        $ownerEmail = config('backup.owner_email');

        if ($ownerEmail === '' || Auth::user()?->email !== $ownerEmail) {
            abort(404);
        }
    }
}
