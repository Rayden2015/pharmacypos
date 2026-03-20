<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestructureSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Legacy settings table had only id + timestamps; app expects key/value rows.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('settings')) {
            $this->createSettingsTable();
            $this->seedCurrencyDefaults();

            return;
        }

        if (! Schema::hasColumn('settings', 'key')) {
            Schema::drop('settings');
            $this->createSettingsTable();
            $this->seedCurrencyDefaults();

            return;
        }

        $this->seedCurrencyDefaults();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

    private function createSettingsTable(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    private function seedCurrencyDefaults(): void
    {
        $now = now();
        $defaults = [
            ['currency_symbol', '#'],
            ['currency_code', ''],
        ];
        foreach ($defaults as [$key, $value]) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
