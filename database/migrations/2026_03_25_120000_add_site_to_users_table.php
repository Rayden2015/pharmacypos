<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('is_admin');
        });

        $defaultSiteId = (int) DB::table('sites')->where('is_default', true)->orderBy('id')->value('id');
        if ($defaultSiteId < 1) {
            $defaultSiteId = (int) DB::table('sites')->orderBy('id')->value('id');
        }
        if ($defaultSiteId > 0) {
            DB::table('users')->whereNull('site_id')->update(['site_id' => $defaultSiteId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn(['site_id', 'is_super_admin']);
        });
    }
};
