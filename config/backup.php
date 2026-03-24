<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nightly scheduled platform backups (Artisan + cron schedule:run)
    |--------------------------------------------------------------------------
    |
    | Files are written under storage/app/backups/scheduled/platform/ and are
    | intentionally not listed on the Backup settings page (on-demand backups
    | use storage/app/backups/platform/ or tenants/{id}/).
    |
    */
    'scheduled_enabled' => filter_var(env('BACKUP_SCHEDULE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'scheduled_retention_days' => max(1, (int) env('BACKUP_SCHEDULE_RETENTION_DAYS', 30)),

];
