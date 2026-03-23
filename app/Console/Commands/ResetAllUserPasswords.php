<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetAllUserPasswords extends Command
{
    protected $signature = 'users:reset-passwords
                            {--force : Run without confirmation (required in production)}';

    protected $description = 'Set every user\'s password and confirm_password to "password" (bcrypt).';

    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('In production, pass --force after backing up your database.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This will overwrite ALL user passwords. Continue?', false)) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $driver = config("database.connections.{$connection}.driver");

        $before = (int) DB::table('users')->count();
        if ($before === 0) {
            $this->warn('No rows in `users` — nothing to update.');
            $this->line("  Connection: <fg=cyan>{$connection}</> ({$driver}), database: <fg=cyan>{$database}</>");
            $this->line('  Create users with: <fg=yellow>php artisan migrate --seed</>');
            $this->line('  If you expected accounts here, check <fg=yellow>DB_*</> in <fg=yellow>.env</> matches the app you use in the browser.');

            return self::SUCCESS;
        }

        $hash = Hash::make('password');
        $now = now();

        $affected = DB::table('users')->update([
            'password' => $hash,
            'confirm_password' => $hash,
            'updated_at' => $now,
        ]);

        $this->info("Updated {$affected} user(s) on [{$connection}] {$database}.");
        $this->line('All can log in with password: <fg=green>password</>');

        return self::SUCCESS;
    }
}
