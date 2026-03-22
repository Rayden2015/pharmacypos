<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('product_name');
            $table->string('sku', 64)->nullable()->unique()->after('slug');
            $table->string('item_code', 64)->nullable()->after('sku');
            $table->string('selling_type', 32)->default('retail')->after('item_code');
            $table->string('category')->nullable()->after('selling_type');
            $table->string('sub_category')->nullable()->after('category');
            $table->string('barcode_symbology', 32)->nullable()->after('sub_category');
            $table->string('tax_type', 32)->nullable()->after('barcode_symbology');
            $table->string('discount_type', 32)->default('none')->after('tax_type');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            $table->string('product_type', 32)->default('single')->after('discount_value');
            $table->string('warranty_term')->nullable()->after('product_type');
            $table->date('manufactured_date')->nullable()->after('warranty_term');
            $table->string('warehouse_note')->nullable()->after('manufactured_date');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'sku', 'item_code', 'selling_type', 'category', 'sub_category',
                'barcode_symbology', 'tax_type', 'discount_type', 'discount_value',
                'product_type', 'warranty_term', 'manufactured_date', 'warehouse_note',
            ]);
        });
    }
};
