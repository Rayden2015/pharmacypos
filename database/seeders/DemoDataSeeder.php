<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Doctor;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\Site;
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
        $user = User::query()->where('email', 'cashier@demo.test')->first()
            ?? User::query()->where('is_super_admin', false)->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();
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

            $companyIdForSites = (int) ($user->company_id ?? Company::query()->orderBy('id')->value('id'));
            $defaultSiteId = $this->defaultSiteIdForCompany($companyIdForSites);

            foreach ($orderScenarios as $idx => $scenario) {
                $order = new Order;
                $order->name = $customerNames[$idx % count($customerNames)];
                $order->mobile = self::DEMO_MOBILE;
                $order->site_id = $defaultSiteId;
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
                    $detail->unit_of_measure = $p->unit_of_measure;
                    $detail->volume = $p->volume;
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

            $this->seedDemoPrescriptions($user);
        });

        $this->command->info('Demo products and sales seeded (mobile '.self::DEMO_MOBILE.').');
    }

    private function seedDemoPrescriptions(User $user): void
    {
        Prescription::query()->where('rx_number', 'like', 'DEMO-%')->delete();
        Doctor::query()->where('license_number', 'like', 'DEMO-%')->delete();

        $siteId = (int) ($user->site_id ?? Site::defaultId());

        $docDemo1 = Doctor::create([
            'site_id' => $siteId,
            'name' => 'Dr. Demo One',
            'specialty' => 'General practice',
            'phone' => '0200000001',
            'license_number' => 'DEMO-DOC-1',
        ]);
        $docDemo2 = Doctor::create([
            'site_id' => $siteId,
            'name' => 'Dr. Demo Two',
            'specialty' => 'Paediatrics',
            'phone' => '0200000002',
            'license_number' => 'DEMO-DOC-2',
        ]);

        $rows = [
            ['patient_name' => 'Akosua T.', 'patient_phone' => '0244111001', 'rx_number' => 'DEMO-001', 'status' => 'completed', 'notes' => 'Antibiotic course'],
            ['patient_name' => 'Yaw K.', 'patient_phone' => '0244111002', 'rx_number' => 'DEMO-002', 'status' => 'pending', 'notes' => 'Awaiting stock'],
            ['patient_name' => 'Efua N.', 'patient_phone' => '0244111003', 'rx_number' => 'DEMO-003', 'status' => 'pending', 'notes' => null],
            ['patient_name' => 'Kojo P.', 'patient_phone' => '0244111004', 'rx_number' => 'DEMO-004', 'status' => 'cancelled', 'notes' => 'Patient switched to OTC'],
            ['patient_name' => 'Ama S.', 'patient_phone' => '0244111005', 'rx_number' => 'DEMO-005', 'status' => 'completed', 'notes' => null],
        ];

        foreach ($rows as $i => $row) {
            $rx = new Prescription;
            $rx->site_id = $siteId;
            $rx->doctor_id = $i % 2 === 0 ? $docDemo1->id : $docDemo2->id;
            $rx->patient_name = $row['patient_name'];
            $rx->patient_phone = $row['patient_phone'];
            $rx->rx_number = $row['rx_number'];
            $rx->status = $row['status'];
            $rx->notes = $row['notes'];
            $rx->user_id = $user->id;
            $rx->dispensed_at = $row['status'] === 'completed' ? Carbon::now()->subDays(random_int(0, 3)) : null;
            $rx->save();
        }
    }

    /**
     * @return array<int, Product>
     */
    private function seedProducts(): array
    {
        $rows = [
            ['Amoxicillin 500mg', 'AMX-500', 'Antibiotic (Rx)', 'PharmaCare Ltd', 'Tablet', 18, 12, 120, 'Tablet', '500 mg'],
            ['Paracetamol 500mg', 'PAR-500', 'Pain relief / fever', 'Entrance Pharmaceuticals', 'Tablet', 5, 3, 200, 'Tablet', '500 mg'],
            ['ORS Sachets', 'ORS-01', 'Oral rehydration', 'HydrateCo', 'Powder', 8, 5, 80, 'Sachet', '20.5 g'],
            ['Vitamin C 1000mg', 'VIT-C1K', 'Immune support', 'Wellness Brands', 'Tablet', 35, 22, 60, 'Tablet', '1000 mg'],
            ['Cetirizine 10mg', 'CET-10', 'Allergy relief', 'AllerMed', 'Tablet', 12, 7, 90, 'Tablet', '10 mg'],
            ['Artemether-Lumefantrine', 'AL-20', 'Antimalarial pack', 'MalariaFree Inc', 'Tablet', 45, 30, 40, 'Pack', '24 tabs'],
            ['Ibuprofen 400mg', 'IBU-400', 'NSAID', 'PainEase', 'Tablet', 15, 9, 100, 'Tablet', '400 mg'],
            ['Salbutamol Inhaler', 'SAL-INH', 'Asthma relief', 'RespiPharm', 'Inhaler', 55, 38, 25, 'Inhaler', '200 doses'],
            ['Omeprazole 20mg', 'OME-20', 'Acid reducer', 'GutHealth Co', 'Capsules', 22, 14, 75, 'Capsule', '20 mg'],
            ['Chloramphenicol Eye Drop', 'CHL-EYE', 'Bacterial conjunctivitis', 'OcularMed', 'Eye Drop', 28, 18, 35, 'Bottle', '10 ml'],
        ];

        $products = [];
        $exp = Carbon::now()->addYear()->format('Y-m-d');

        $companyId = (int) Company::query()->orderBy('id')->value('id');
        $initialSiteId = $this->defaultSiteIdForCompany($companyId);

            foreach ($rows as $i => $r) {
            [$name, $alias, $desc, $brand, $form, $price, $supplier, $qty, $uom, $vol] = $r;

            $manufacturer = Manufacturer::firstOrCreate(
                ['name' => $brand],
                ['name' => $brand]
            );

            $product = Product::firstOrNew(['product_name' => $name]);
            $product->fill([
                'company_id' => $companyId,
                'alias' => $alias,
                'description' => $desc,
                'manufacturer_id' => $manufacturer->id,
                'form' => $form,
                'unit_of_measure' => $uom,
                'volume' => $vol,
                'expiredate' => $exp,
                'price' => $price,
                'supplierprice' => $supplier,
                'quantity' => $qty,
                'stock_alert' => $qty < 50 ? 20 : 30,
                'product_img' => 'product.png',
            ]);
            $product->initial_site_id = $initialSiteId;
            $product->save();
            $products[$i] = $product->fresh();
        }

        return $products;
    }

    private function defaultSiteIdForCompany(int $companyId): int
    {
        if ($companyId < 1) {
            return Site::defaultId();
        }

        $id = (int) Site::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->orderBy('id')
            ->value('id');

        return $id > 0 ? $id : Site::defaultId();
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
        Prescription::query()->where('rx_number', 'like', 'DEMO-%')->delete();
        Doctor::query()->where('license_number', 'like', 'DEMO-%')->delete();
    }
}
