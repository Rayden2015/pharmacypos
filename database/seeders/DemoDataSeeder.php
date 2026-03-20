<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Demo orders use this mobile so re-seeding can replace old demo sales safely.
     */
    private const DEMO_MOBILE = '0244000000';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::query()->orderBy('id')->first();
        if (! $user) {
            $this->command->warn('No users found. Run AdminSeeder first.');

            return;
        }

        DB::transaction(function () use ($user) {
            $this->clearPreviousDemoSales();

            $products = $this->seedProducts();

            $orderScenarios = [
                ['day' => 0, 'lines' => [[0, 2, 0], [1, 1, 5]]],
                ['day' => 0, 'lines' => [[2, 3, 0], [3, 1, 0], [4, 2, 10]]],
                ['day' => 1, 'lines' => [[0, 1, 0], [5, 2, 0]]],
                ['day' => 2, 'lines' => [[6, 1, 0], [7, 4, 0]]],
                ['day' => 3, 'lines' => [[8, 2, 0]]],
                ['day' => 4, 'lines' => [[9, 1, 0], [2, 2, 5], [1, 1, 0]]],
                ['day' => 5, 'lines' => [[4, 1, 0], [5, 1, 0]]],
            ];

            $customerNames = [
                'Ama Mensah',
                'Kwesi Owusu',
                'Adwoa Boateng',
                'Kofi Asante',
                'Efua Darko',
                'Yaw Addo',
                'Abena Frimpong',
            ];

            foreach ($orderScenarios as $idx => $scenario) {
                $order = new Order;
                $order->name = $customerNames[$idx % count($customerNames)];
                $order->mobile = self::DEMO_MOBILE;
                $order->save();

                $placed = Carbon::now()->startOfDay()->subDays($scenario['day'])
                    ->addHours(9 + ($idx % 8));
                $order->created_at = $placed;
                $order->updated_at = $placed;
                $order->save();

                $grandTotal = 0;

                foreach ($scenario['lines'] as [$productIndex, $qty, $discPct]) {
                    /** @var Product $p */
                    $p = $products[$productIndex];
                    $unit = (int) $p->price;
                    $line = $qty * $unit * (100 - $discPct) / 100;
                    $line = (int) round($line);

                    $detail = new Order_detail;
                    $detail->order_id = $order->id;
                    $detail->product_id = $p->id;
                    $detail->quantity = $qty;
                    $detail->unitprice = $unit;
                    $detail->discount = $discPct;
                    $detail->amount = $line;
                    $detail->save();

                    $detail->created_at = $placed;
                    $detail->updated_at = $placed;
                    $detail->save();

                    $grandTotal += $line;
                }

                $paid = $grandTotal;
                $transaction = new Transaction;
                $transaction->order_id = $order->id;
                $transaction->user_id = $user->id;
                $transaction->transaction_amount = $grandTotal;
                $transaction->paid_amount = $paid;
                $transaction->balance = 0;
                $transaction->payment_method = $idx % 3 === 0 ? 'Cash' : ($idx % 3 === 1 ? 'BankTransfer' : 'CreditCard');
                $transaction->transaction_date = $placed->toDateString();
                $transaction->save();

                $transaction->created_at = $placed;
                $transaction->updated_at = $placed;
                $transaction->save();
            }
        });

        $this->command->info('Demo products and sales seeded (mobile '.self::DEMO_MOBILE.').');
    }

    /**
     * @return array<int, Product>
     */
    private function seedProducts(): array
    {
        $rows = [
            ['Amoxicillin 500mg', 'AMX-500', 'Antibiotic (Rx)', 'PharmaCare Ltd', 'Tablet', 18, 12, 120],
            ['Paracetamol 500mg', 'PAR-500', 'Pain relief / fever', 'Entrance Pharmaceuticals', 'Tablet', 5, 3, 200],
            ['ORS Sachets', 'ORS-01', 'Oral rehydration', 'HydrateCo', 'Powder', 8, 5, 80],
            ['Vitamin C 1000mg', 'VIT-C1K', 'Immune support', 'Wellness Brands', 'Tablet', 35, 22, 60],
            ['Cetirizine 10mg', 'CET-10', 'Allergy relief', 'AllerMed', 'Tablet', 12, 7, 90],
            ['Artemether-Lumefantrine', 'AL-20', 'Antimalarial pack', 'MalariaFree Inc', 'Tablet', 45, 30, 40],
            ['Ibuprofen 400mg', 'IBU-400', 'NSAID', 'PainEase', 'Tablet', 15, 9, 100],
            ['Salbutamol Inhaler', 'SAL-INH', 'Asthma relief', 'RespiPharm', 'Inhaler', 55, 38, 25],
            ['Omeprazole 20mg', 'OME-20', 'Acid reducer', 'GutHealth Co', 'Capsules', 22, 14, 75],
            ['Chloramphenicol Eye Drop', 'CHL-EYE', 'Bacterial conjunctivitis', 'OcularMed', 'Eye Drop', 28, 18, 35],
        ];

        $products = [];
        $exp = Carbon::now()->addYear()->format('Y-m-d');

        foreach ($rows as $i => $r) {
            [$name, $alias, $desc, $brand, $form, $price, $supplier, $qty] = $r;

            $product = Product::updateOrCreate(
                ['product_name' => $name],
                [
                    'alias' => $alias,
                    'description' => $desc,
                    'brand' => $brand,
                    'form' => $form,
                    'expiredate' => $exp,
                    'price' => $price,
                    'supplierprice' => $supplier,
                    'quantity' => $qty,
                    'stock_alert' => $qty < 50 ? 20 : 30,
                    'product_img' => 'product.png',
                ]
            );
            $products[$i] = $product->fresh();
        }

        return $products;
    }

    private function clearPreviousDemoSales(): void
    {
        $orderIds = Order::query()->where('mobile', self::DEMO_MOBILE)->pluck('id');

        if ($orderIds->isEmpty()) {
            return;
        }

        Order_detail::query()->whereIn('order_id', $orderIds)->delete();
        Transaction::query()->whereIn('order_id', $orderIds)->delete();
        Order::query()->whereIn('id', $orderIds)->delete();
    }
}
