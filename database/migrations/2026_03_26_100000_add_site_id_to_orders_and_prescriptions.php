<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultSiteId = (int) DB::table('sites')->where('is_default', true)->orderBy('id')->value('id');
        if ($defaultSiteId < 1) {
            $defaultSiteId = (int) DB::table('sites')->orderBy('id')->value('id');
        }

        Schema::table('orders', function (Blueprint $table) use ($defaultSiteId) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->nullOnDelete();
        });
        if ($defaultSiteId > 0) {
            DB::table('orders')->whereNull('site_id')->update(['site_id' => $defaultSiteId]);
        }

        Schema::table('prescriptions', function (Blueprint $table) use ($defaultSiteId) {
            $table->foreignId('site_id')->nullable()->after('id')->constrained('sites')->nullOnDelete();
        });
        if ($defaultSiteId > 0) {
            DB::table('prescriptions')->whereNull('site_id')->update(['site_id' => $defaultSiteId]);
        }
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });
    }
};
