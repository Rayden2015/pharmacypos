<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('state_region')->nullable();
            $table->string('postal_code')->nullable();
        });

        foreach (DB::table('users')->get(['id', 'name', 'address']) as $row) {
            $name = trim((string) $row->name);
            $parts = preg_split('/\s+/', $name, 2);
            $first = $parts[0] ?? '';
            $last = $parts[1] ?? '';

            DB::table('users')->where('id', $row->id)->update([
                'first_name' => $first !== '' ? $first : null,
                'last_name' => $last !== '' ? $last : null,
                'address_line1' => $row->address ? (string) $row->address : null,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'address_line1',
                'address_line2',
                'country',
                'city',
                'state_region',
                'postal_code',
            ]);
        });
    }
};
