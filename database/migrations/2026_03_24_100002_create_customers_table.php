<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->nullable()->unique();
            $table->string('name');
            $table->string('mobile', 32);
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['site_id', 'is_active']);
            $table->index('mobile');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
