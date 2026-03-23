<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
        });

        $this->backfillProductCompanies();

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }

    private function backfillProductCompanies(): void
    {
        $defaultCompanyId = (int) DB::table('companies')->orderBy('id')->value('id');
        if ($defaultCompanyId < 1) {
            return;
        }

        $productIds = DB::table('product_site_stock')->distinct()->pluck('product_id');
        foreach ($productIds as $pid) {
            $cid = (int) DB::table('product_site_stock as pss')
                ->join('sites as s', 's.id', '=', 'pss.site_id')
                ->where('pss.product_id', $pid)
                ->orderBy('pss.id')
                ->value('s.company_id');
            if ($cid > 0) {
                DB::table('products')->where('id', $pid)->update(['company_id' => $cid]);
            }
        }

        DB::table('products')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
    }
};
