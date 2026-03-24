<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $defaults = [
            ['app_locale', 'en'],
            ['app_timezone', 'Africa/Lagos'],
            ['date_format', 'd M Y'],
            ['time_format', 'H:i'],
        ];
        foreach ($defaults as [$key, $value]) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Leave rows; they are harmless if re-run.
    }
};
