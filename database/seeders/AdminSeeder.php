<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'admin',
                'password' => bcrypt('secret'),
                'confirm_password' => bcrypt('secret'),
                'is_admin' => '1',
                'is_super_admin' => true,
                'mobile' => '0123456789',
                'status' => '1',
            ]
        );
    }
}
