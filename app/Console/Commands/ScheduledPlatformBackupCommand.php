<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScheduledPlatformBackupCommand extends Command
{
    protected $signature = 'backup:scheduled-platform';

    protected $description = 'Create nightly platform backups under storage/app/backups/scheduled/platform (not listed on Backup settings)';

    public function handle(BackupSettingsService $backups): int
    {
        if (! config('backup.scheduled_enabled', true)) {
            $this->info('Scheduled platform backups are disabled (BACKUP_SCHEDULE_ENABLED).');

            return 0;
        }

        try {
            $paths = $backups->runScheduledPlatformBackups();
            $pruned = $backups->pruneScheduledPlatformBackups();

            Log::channel('audit')->info('backup.scheduled.platform.completed', [
                'system_path' => $paths['system'],
                'database_path' => $paths['database'],
                'pruned_files' => $pruned,
            ]);

            $this->line('System:    '.$paths['system']);
            $this->line('Database: '.$paths['database']);
            $this->line('Pruned old scheduled files: '.$pruned);

            return 0;
        } catch (\Throwable $e) {
            Log::channel('audit')->error('backup.scheduled.platform.failed', [
                'message' => $e->getMessage(),
            ]);
            $this->error($e->getMessage());

            return 1;
        }
    }
}
