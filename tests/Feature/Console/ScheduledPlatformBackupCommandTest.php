<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ScheduledPlatformBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_exits_zero_when_scheduled_backups_disabled(): void
    {
        Config::set('backup.scheduled_enabled', false);

        $exit = Artisan::call('backup:scheduled-platform');

        $this->assertSame(0, $exit);
    }

    public function test_full_scheduled_backup_skipped_when_sqlite_is_in_memory(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            $this->markTestSkipped('This assertion applies to the default PHPUnit sqlite :memory: configuration.');
        }

        Config::set('backup.scheduled_enabled', true);

        $exit = Artisan::call('backup:scheduled-platform');

        $this->assertSame(1, $exit);
    }
}
