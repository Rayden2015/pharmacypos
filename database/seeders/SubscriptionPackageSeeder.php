<?php

namespace Database\Seeders;

use App\Models\SubscriptionPackage;
use Illuminate\Database\Seeder;

class SubscriptionPackageSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Basic', 'billing_cycle' => 'monthly', 'price' => 50, 'billing_days' => 30, 'sort_order' => 10],
            ['name' => 'Advanced', 'billing_cycle' => 'monthly', 'price' => 200, 'billing_days' => 30, 'sort_order' => 20],
            ['name' => 'Premium', 'billing_cycle' => 'monthly', 'price' => 300, 'billing_days' => 30, 'sort_order' => 30],
            ['name' => 'Enterprise', 'billing_cycle' => 'monthly', 'price' => 400, 'billing_days' => 30, 'sort_order' => 40],
            ['name' => 'Basic', 'billing_cycle' => 'yearly', 'price' => 600, 'billing_days' => 365, 'sort_order' => 50],
            ['name' => 'Advanced', 'billing_cycle' => 'yearly', 'price' => 2400, 'billing_days' => 365, 'sort_order' => 60],
            ['name' => 'Premium', 'billing_cycle' => 'yearly', 'price' => 3600, 'billing_days' => 365, 'sort_order' => 70],
            ['name' => 'Enterprise', 'billing_cycle' => 'yearly', 'price' => 4800, 'billing_days' => 365, 'sort_order' => 80],
        ];

        foreach ($rows as $row) {
            SubscriptionPackage::query()->updateOrCreate(
                [
                    'name' => $row['name'],
                    'billing_cycle' => $row['billing_cycle'],
                ],
                array_merge($row, ['is_active' => true, 'subscriber_count' => 0])
            );
        }
    }
}
