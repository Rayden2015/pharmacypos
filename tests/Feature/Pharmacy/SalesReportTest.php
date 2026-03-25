<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(): User
    {
        $this->seedPermissionsCatalog();

        $user = User::create([
            'name' => 'Report User',
            'email' => uniqid('rep', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        return $this->grantPermissions($user, ['reports.view', 'reports.export']);
    }

    public function test_sales_report_page_loads_for_authenticated_user(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('reports.sales'))
            ->assertOk()
            ->assertSee('Sales report', false)
            ->assertSee('Invoice no.', false)
            ->assertSee('Net revenue', false)
            ->assertSee('Total sales amount', false);
    }

    public function test_sales_report_lists_order_with_totals(): void
    {
        $user = $this->makeUser();

        $product = Product::create([
            'product_name' => 'Report SKU '.uniqid(),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'M'], ['name' => 'M'])->id,
            'price' => 100,
            'supplierprice' => 50,
            'quantity' => 10,
            'stock_alert' => 1,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $order = new Order;
        $order->name = 'Jane Cooper';
        $order->mobile = '0244000111';
        $order->site_id = $user->site_id ?? \App\Models\Site::defaultId();
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unitprice' => 100,
            'discount' => 10,
            'amount' => 90,
            'unit_of_measure' => null,
            'volume' => null,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'transaction_amount' => 90,
            'paid_amount' => 100,
            'balance' => 10,
            'payment_method' => 'Cash',
            'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.sales', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Jane Cooper', false)
            ->assertSee('#ORD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT), false);
    }

    public function test_sales_csv_export_streams_csv_for_current_filters(): void
    {
        $user = $this->makeUser();

        $product = Product::create([
            'product_name' => 'CSV SKU '.uniqid(),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'M2'], ['name' => 'M2'])->id,
            'price' => 50,
            'supplierprice' => 25,
            'quantity' => 5,
            'stock_alert' => 1,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $order = new Order;
        $order->name = 'CSV Customer';
        $order->mobile = '0244111222';
        $order->site_id = $user->site_id ?? \App\Models\Site::defaultId();
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unitprice' => 50,
            'discount' => 0,
            'amount' => 50,
            'unit_of_measure' => null,
            'volume' => null,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'transaction_amount' => 50,
            'paid_amount' => 50,
            'balance' => 0,
            'payment_method' => 'MoMo',
            'transaction_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.sales.export', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Invoice no.', $csv);
        $this->assertStringContainsString('Sales amount', $csv);
        $this->assertStringContainsString('Net revenue', $csv);
        $this->assertStringContainsString('CSV Customer', $csv);
        $this->assertStringContainsString('MoMo', $csv);
    }

    public function test_sales_print_page_loads_with_heading(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('reports.sales.print', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Sales report', false)
            ->assertSee('Invoice no.', false)
            ->assertSee('Net revenue', false);
    }

    public function test_sales_report_search_by_customer_and_invoice(): void
    {
        $user = $this->makeUser();

        $order = new Order;
        $order->name = 'UniqueSearchCustomer';
        $order->mobile = '0244999888';
        $order->site_id = $user->site_id ?? \App\Models\Site::defaultId();
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => Product::create([
                'product_name' => 'S '.uniqid(),
                'description' => 'd',
                'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'Ms'], ['name' => 'Ms'])->id,
                'price' => 10,
                'supplierprice' => 5,
                'quantity' => 5,
                'stock_alert' => 1,
                'form' => 'Tablet',
                'expiredate' => '2030-01-01',
                'product_img' => 'product.png',
            ])->id,
            'quantity' => 1,
            'unitprice' => 10,
            'discount' => 0,
            'amount' => 10,
            'unit_of_measure' => null,
            'volume' => null,
        ]);

        $d = now()->toDateString();

        $this->actingAs($user)
            ->get(route('reports.sales', ['start_date' => $d, 'end_date' => $d, 'q' => 'UniqueSearch']))
            ->assertOk()
            ->assertSee('UniqueSearchCustomer', false);

        $this->actingAs($user)
            ->get(route('reports.sales', ['start_date' => $d, 'end_date' => $d, 'q' => '0244999888']))
            ->assertOk()
            ->assertSee('UniqueSearchCustomer', false);

        $this->actingAs($user)
            ->get(route('reports.sales', ['start_date' => $d, 'end_date' => $d, 'q' => '#ORD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)]))
            ->assertOk()
            ->assertSee('UniqueSearchCustomer', false);
    }
}
