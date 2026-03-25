<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('manager_name', 255)->nullable()->after('address');
            $table->string('phone', 64)->nullable()->after('manager_name');
            $table->string('email', 255)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['manager_name', 'phone', 'email']);
        });
    }
};
