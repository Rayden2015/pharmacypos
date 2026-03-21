<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->unique('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('manufacturer_id')->nullable()->after('description');
            $table->unsignedBigInteger('preferred_supplier_id')->nullable()->after('manufacturer_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('manufacturer_id')->references('id')->on('manufacturers')->nullOnDelete();
            $table->foreign('preferred_supplier_id')->references('id')->on('suppliers')->nullOnDelete();
        });

        $brands = DB::table('products')->select('brand')->distinct()->get();
        foreach ($brands as $row) {
            $raw = $row->brand;
            $name = trim((string) $raw);
            if ($name === '') {
                $name = 'Unspecified';
            }
            $id = DB::table('manufacturers')->where('name', $name)->value('id');
            if (! $id) {
                $id = DB::table('manufacturers')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $q = DB::table('products');
            if ($raw === null) {
                $q->whereNull('brand');
            } elseif ($raw === '') {
                $q->where(function ($w) {
                    $w->whereNull('brand')->orWhere('brand', '');
                });
            } else {
                $q->where('brand', $raw);
            }
            $q->update(['manufacturer_id' => $id]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('description');
        });

        DB::table('products')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $p) {
                $name = DB::table('manufacturers')->where('id', $p->manufacturer_id)->value('name');
                DB::table('products')->where('id', $p->id)->update(['brand' => $name ?? '']);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['manufacturer_id']);
            $table->dropForeign(['preferred_supplier_id']);
            $table->dropColumn(['manufacturer_id', 'preferred_supplier_id']);
        });

        Schema::dropIfExists('manufacturers');
    }
};
