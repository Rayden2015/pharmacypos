<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('quantity_before')->nullable();
            $table->integer('quantity_delta');
            $table->unsignedInteger('quantity_after');
            $table->string('change_type', 32);
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('products')->orderBy('id')->chunkById(100, function ($rows) use ($now) {
            foreach ($rows as $row) {
                if (DB::table('inventory_movements')->where('product_id', $row->id)->doesntExist()) {
                    DB::table('inventory_movements')->insert([
                        'product_id' => $row->id,
                        'user_id' => null,
                        'quantity_before' => null,
                        'quantity_delta' => (int) $row->quantity,
                        'quantity_after' => (int) $row->quantity,
                        'change_type' => 'initial',
                        'note' => 'Opening balance (backfilled from stock on hand)',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
