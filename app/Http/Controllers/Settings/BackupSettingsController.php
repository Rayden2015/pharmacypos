<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\BackupGenerationRequest;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

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

        $generationRequests = BackupGenerationRequest::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(25)
            ->get();

        return view('settings.backup', [
            'platformScope' => $platform,
            'systemFiles' => $backups->listSystemFiles($user),
            'databaseFiles' => $backups->listDatabaseFiles($user),
            'generationRequests' => $generationRequests,
        ]);
    }

    public function generationStatus(Request $request): JsonResponse
    {
        $requests = BackupGenerationRequest::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(25)
            ->get()
            ->map(function (BackupGenerationRequest $r) {
                return [
                    'id' => $r->id,
                    'kind' => $r->kind,
                    'kind_label' => $r->label(),
                    'status' => $r->status,
                    'output_path' => $r->output_path,
                    'error_message' => $r->error_message,
                    'started_at' => $r->started_at?->toIso8601String(),
                    'completed_at' => $r->completed_at?->toIso8601String(),
                    'created_at' => $r->created_at->toIso8601String(),
                ];
            });

        return response()->json(['requests' => $requests]);
    }

    public function generateSystem(Request $request): RedirectResponse
    {
        return $this->queueBackupGeneration($request, BackupGenerationRequest::KIND_SYSTEM);
    }

    public function generateDatabase(Request $request): RedirectResponse
    {
        return $this->queueBackupGeneration($request, BackupGenerationRequest::KIND_DATABASE);
    }

    private function queueBackupGeneration(Request $request, string $kind): RedirectResponse
    {
        $actor = $request->user();

        $req = BackupGenerationRequest::create([
            'user_id' => $actor->id,
            'company_id' => $actor->company_id ? (int) $actor->company_id : null,
            'kind' => $kind,
            'status' => BackupGenerationRequest::STATUS_QUEUED,
        ]);

        if ($this->runningUnitTests()) {
            Artisan::call('backup:process-generation', ['id' => (string) $req->id]);
            $req->refresh();

            if ($req->status === BackupGenerationRequest::STATUS_FAILED) {
                return redirect()->route('settings.backup')
                    ->with('error', __('Backup failed: :msg', ['msg' => $req->error_message ?? 'unknown']));
            }

            return redirect()->route('settings.backup')
                ->with('success', __('Backup completed. File appears in the list below.'));
        }

        if (! $this->spawnBackupProcess($req->id)) {
            return redirect()->route('settings.backup')
                ->with('error', __('Could not start the backup process. Set PHP_CLI_PATH in .env to your PHP CLI (e.g. /opt/homebrew/bin/php), then try again.'));
        }

        return redirect()->route('settings.backup')
            ->with('success', __('Backup started in the background. Status updates in the table below.'));
    }

    private function runningUnitTests(): bool
    {
        return app()->runningUnitTests();
    }

    private function spawnBackupProcess(int $id): bool
    {
        $php = (string) config('backup.php_cli', 'php');
        $artisan = base_path('artisan');

        try {
            $process = new Process([$php, $artisan, 'backup:process-generation', (string) $id]);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(null);
            $process->start();
        } catch (\Throwable $e) {
            Log::channel('audit')->error('settings.backup.spawn_failed', [
                'generation_id' => $id,
                'message' => $e->getMessage(),
            ]);
            BackupGenerationRequest::query()->whereKey($id)->update([
                'status' => BackupGenerationRequest::STATUS_FAILED,
                'error_message' => 'Could not start backup process: '.$e->getMessage(),
                'completed_at' => now(),
            ]);

            return false;
        }

        return true;
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
