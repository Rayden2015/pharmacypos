<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'yser@gmail.com'],
            [
                'name' => 'user',
                'password' => bcrypt('secret'),
                'confirm_password' => bcrypt('secret'),
                'is_admin' => '0',
                'mobile' => '0123456789',
                'status' => '1',
            ]
        );
    }
}
