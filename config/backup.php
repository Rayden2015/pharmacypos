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
    | Single-tenant restore drill: restore the SQL dump to a scratch instance, then delete or
    | export rows for other company_id / site_id values before promoting the DB; scheduled
    | backups are full-database (multi-tenant), not per-tenant slices.
    |
    */
    'scheduled_enabled' => filter_var(env('BACKUP_SCHEDULE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'scheduled_retention_days' => max(1, (int) env('BACKUP_SCHEDULE_RETENTION_DAYS', 30)),

    /*
    |--------------------------------------------------------------------------
    | mysqldump binary (MySQL full dumps)
    |--------------------------------------------------------------------------
    |
    | When empty, common paths are tried (Homebrew on Apple Silicon/Intel, etc.).
    | Set explicitly if mysqldump is not on PATH for the PHP process (e.g. web server).
    |
    */
    'mysql_dump' => env('BACKUP_MYSQLDUMP', ''),

    /*
    |--------------------------------------------------------------------------
    | PHP CLI binary (async backup subprocess)
    |--------------------------------------------------------------------------
    |
    | Used to spawn `php artisan backup:process-generation` in the background.
    | The web SAPI may point PHP_BINARY at php-fpm; set this to the php CLI (e.g. /opt/homebrew/bin/php).
    |
    */
    'php_cli' => env('PHP_CLI_PATH', 'php'),

];
