<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
            $table->index('company_id');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        DB::statement(
            'UPDATE audit_logs SET company_id = (
                SELECT u.company_id FROM users u WHERE u.id = audit_logs.user_id
            ) WHERE user_id IS NOT NULL'
        );

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')->nullable()->after('order_id');
            $table->unsignedBigInteger('company_id')->nullable()->after('site_id');
            $table->index(['site_id']);
            $table->index(['company_id']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('site_id')->references('id')->on('sites')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        DB::statement(
            'UPDATE transactions SET site_id = (
                SELECT o.site_id FROM orders o WHERE o.id = transactions.order_id
            ), company_id = (
                SELECT s.company_id FROM orders o
                INNER JOIN sites s ON s.id = o.site_id
                WHERE o.id = transactions.order_id
            )'
        );

        Schema::table('backup_generation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
            $table->index('company_id');
        });
        Schema::table('backup_generation_requests', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        DB::statement(
            'UPDATE backup_generation_requests SET company_id = (
                SELECT u.company_id FROM users u WHERE u.id = backup_generation_requests.user_id
            ) WHERE user_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('backup_generation_requests', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['company_id']);
            $table->dropColumn(['site_id', 'company_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
