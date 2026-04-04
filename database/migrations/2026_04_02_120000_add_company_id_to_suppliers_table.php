<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCompanyIdToSuppliersTable extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->index('company_id');
        });

        $defaultCompanyId = DB::table('companies')->orderBy('id')->value('id');
        if ($defaultCompanyId) {
            DB::table('suppliers')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
        }
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
