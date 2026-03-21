<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->nullable()->comment('Short code (e.g. TAB, mL) for integrations');
            $table->string('name')->unique()->comment('Display / stored value on products');
            $table->string('category', 64)->nullable()->comment('e.g. solid_oral, liquid_oral, sterile');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
