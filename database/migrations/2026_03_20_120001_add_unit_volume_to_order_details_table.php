<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitVolumeToOrderDetailsTable extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('unit_of_measure', 64)->nullable()->after('discount');
            $table->string('volume', 128)->nullable()->after('unit_of_measure');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['unit_of_measure', 'volume']);
        });
    }
}
