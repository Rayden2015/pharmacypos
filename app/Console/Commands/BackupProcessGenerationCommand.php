<?php

namespace App\Console\Commands;

use App\Models\BackupGenerationRequest;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Console\Command;

class BackupProcessGenerationCommand extends Command
{
    protected $signature = 'backup:process-generation {id : backup_generation_requests.id}';

    protected $description = 'Run a queued backup generation request (started from Backup settings)';

    public function handle(BackupSettingsService $backups): int
    {
        $id = (int) $this->argument('id');
        $req = BackupGenerationRequest::query()->find($id);
        if (! $req) {
            $this->error('Backup request not found.');

            return 1;
        }

        if ($req->isFinished()) {
            $this->info('Backup request already finished.');

            return 0;
        }

        $backups->runQueuedBackupGeneration($req);
        $req->refresh();

        if ($req->status === BackupGenerationRequest::STATUS_FAILED) {
            $this->error($req->error_message ?? 'Backup failed.');

            return 1;
        }

        $this->info('Backup saved: '.($req->output_path ?? ''));

        return 0;
    }
}
