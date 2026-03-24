<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BackupSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tenant.admin.or.super']);
    }

    public function index(BackupSettingsService $backups): View
    {
        $user = auth()->user();
        $platform = $backups->isPlatformScope($user);

        return view('settings.backup', [
            'platformScope' => $platform,
            'systemFiles' => $backups->listSystemFiles($user),
            'databaseFiles' => $backups->listDatabaseFiles($user),
        ]);
    }

    public function generateSystem(Request $request, BackupSettingsService $backups): RedirectResponse
    {
        try {
            $path = $backups->generateSystemBackup($request->user());
            Log::channel('audit')->info('settings.backup.system.generated', [
                'user_id' => $request->user()->id,
                'path' => $path,
                'super_admin' => $request->user()->isSuperAdmin(),
            ]);

            return redirect()->route('settings.backup')->with('success', 'System backup generated.');
        } catch (\Throwable $e) {
            Log::channel('audit')->error('settings.backup.system.failed', [
                'user_id' => $request->user()->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('settings.backup')->with('error', 'Could not generate system backup: '.$e->getMessage());
        }
    }

    public function generateDatabase(Request $request, BackupSettingsService $backups): RedirectResponse
    {
        try {
            $path = $backups->generateDatabaseBackup($request->user());
            Log::channel('audit')->info('settings.backup.database.generated', [
                'user_id' => $request->user()->id,
                'path' => $path,
                'super_admin' => $request->user()->isSuperAdmin(),
            ]);

            return redirect()->route('settings.backup')->with('success', 'Database backup generated.');
        } catch (\Throwable $e) {
            Log::channel('audit')->error('settings.backup.database.failed', [
                'user_id' => $request->user()->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('settings.backup')->with('error', 'Could not generate database backup: '.$e->getMessage());
        }
    }

    public function download(Request $request, BackupSettingsService $backups, string $category, string $filename)
    {
        $user = $request->user();
        $name = $backups->safeBasename($filename);
        if (! in_array($category, ['system', 'database'], true)) {
            abort(404);
        }

        $prefix = $backups->isPlatformScope($user) ? 'platform' : 'tenants/'.(int) $user->company_id;
        $relativePath = $prefix.'/'.$category.'/'.$name;
        $backups->assertDownloadAllowed($user, $relativePath);

        return Storage::disk('backups')->download($relativePath, $name);
    }

    public function destroy(Request $request, BackupSettingsService $backups, string $category, string $filename): RedirectResponse
    {
        $user = $request->user();
        $name = $backups->safeBasename($filename);
        if (! in_array($category, ['system', 'database'], true)) {
            abort(404);
        }

        $prefix = $backups->isPlatformScope($user) ? 'platform' : 'tenants/'.(int) $user->company_id;
        $relativePath = $prefix.'/'.$category.'/'.$name;
        $backups->assertDownloadAllowed($user, $relativePath);

        Storage::disk('backups')->delete($relativePath);

        Log::channel('audit')->info('settings.backup.deleted', [
            'user_id' => $user->id,
            'path' => $relativePath,
        ]);

        return redirect()->route('settings.backup')->with('success', 'Backup file removed.');
    }
}
