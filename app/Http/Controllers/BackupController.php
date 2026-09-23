<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use App\Services\DemoMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(DatabaseBackupService $service)
    {
        return view('backups.index', [
            'backups' => $service->all(),
            'backupDir' => $service->directory(),
            'retentionKeep' => DatabaseBackupService::DEFAULT_KEEP,
            'isDemo' => DemoMode::isEnabled() && DemoMode::isDemoUser(Auth::user()),
        ]);
    }

    public function store(Request $request, DatabaseBackupService $service)
    {
        if (DemoMode::isEnabled() && DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

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
        if (DemoMode::isEnabled() && DemoMode::isDemoUser(Auth::user())) {
            return DemoMode::warn();
        }

        if (! $service->has($filename)) {
            abort(404);
        }

        return response()->download($service->pathFor($filename), $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }
}
