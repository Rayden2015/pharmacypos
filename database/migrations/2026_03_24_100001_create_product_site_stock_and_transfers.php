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

        Schema::create('product_site_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained('sites')->onDelete('restrict');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'site_id']);
        });

        DB::table('products')->orderBy('id')->chunkById(100, function ($rows) use ($defaultSiteId) {
            foreach ($rows as $row) {
                DB::table('product_site_stock')->insert([
                    'product_id' => $row->id,
                    'site_id' => $defaultSiteId,
                    'quantity' => max(0, (int) $row->quantity),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_site_id')->constrained('sites');
            $table->foreignId('to_site_id')->constrained('sites');
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });

        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('user_id')->constrained('sites')->nullOnDelete();
        });

        DB::table('stock_receipts')->update(['site_id' => $defaultSiteId]);

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('product_id')->constrained('sites')->nullOnDelete();
            $table->foreignId('stock_transfer_id')->nullable()->after('stock_receipt_id')->constrained('stock_transfers')->nullOnDelete();
        });

        DB::table('inventory_movements')->whereNull('site_id')->update(['site_id' => $defaultSiteId]);
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['stock_transfer_id']);
            $table->dropColumn(['site_id', 'stock_transfer_id']);
        });

        Schema::table('stock_receipts', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });

        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('product_site_stock');
    }
};
