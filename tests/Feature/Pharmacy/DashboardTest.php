<?php

namespace Tests\Feature\Pharmacy;

use App\Http\Controllers\DashboardController;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(string $name = 'Dash User'): User
    {
        return User::create([
            'name' => $name,
            'email' => uniqid('dash', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    private function baseProductAttributes(): array
    {
        return [
            'description' => 'd',
            'brand' => 'BrandCo',
            'price' => 25,
            'supplierprice' => 10,
            'form' => 'Tablet',
            'expiredate' => '2030-06-01',
            'product_img' => 'product.png',
        ];
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_dashboard_and_home_render_successfully(): void
    {
        $user = $this->makeUser('Alex Admin');

        foreach ([route('dashboard'), url('/home')] as $url) {
            $this->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('Welcome, Alex Admin', false)
                ->assertSee('Quick actions', false)
                ->assertSee('Sales vs purchase (last 7 days)', false)
                ->assertSee('Low stock products', false)
                ->assertSee('Recent activity', false)
                ->assertSee('Total sales (MTD)', false)
                ->assertSee('Total sales return', false)
                ->assertSee('Total purchase (MTD)', false)
                ->assertSee('Total purchase return', false)
                ->assertSee('Today\'s sales', false)
                ->assertSee('POS orders today', false)
                ->assertSee('Payments today', false)
                ->assertSee('Low stock SKUs', false)
                ->assertSee('dashSalesPurchaseChart', false);
        }
    }

    public function test_dashboard_shows_low_stock_banner_and_table_rows(): void
    {
        $user = $this->makeUser();

        Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'Zebra Low Stock Item',
            'quantity' => 2,
            'stock_alert' => 10,
        ]));

        Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'Another Low',
            'quantity' => 5,
            'stock_alert' => 20,
        ]));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Low stock:', false)
            ->assertSee('Zebra Low Stock Item', false)
            ->assertSee('Add stock', false)
            ->assertSee('View all', false);
    }

    public function test_dashboard_reflects_today_sales_orders_and_payments(): void
    {
        $user = $this->makeUser();

        $product = Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'Sale Product',
            'quantity' => 100,
            'stock_alert' => 5,
        ]));

        $order = new Order;
        $order->name = 'Walk-in Customer';
        $order->mobile = '0244000000';
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unitprice' => 25,
            'amount' => 50,
            'discount' => 0,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'transaction_amount' => 50,
            'paid_amount' => 50,
            'balance' => 0,
            'payment_method' => 'Cash',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(number_format(50, 2), $html, 'Today\'s sales should show order line total');
        $this->assertStringContainsString('Walk-in Customer', $html, 'Recent sales tab should list customer');

        $metrics = DashboardController::dashboardViewData();
        $this->assertSame(50.0, $metrics['today_sales']);
        $this->assertSame(1, $metrics['orders_today']);
        $this->assertSame(50.0, $metrics['payments_today']);
    }

    public function test_dashboard_purchase_mtd_and_recent_purchases_reflect_receipts(): void
    {
        $user = $this->makeUser();

        $product = Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'Inbound SKU',
            'supplierprice' => 8,
            'quantity' => 100,
            'stock_alert' => 5,
        ]));

        StockReceipt::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'quantity' => 5,
            'batch_number' => 'B1',
            'expiry_date' => null,
            'supplier_id' => null,
            'document_reference' => 'PO-1',
            'received_at' => Carbon::today()->toDateString(),
            'notes' => null,
        ]);

        $expectedPurchaseMtd = 5 * 8.0;

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(number_format($expectedPurchaseMtd, 2), $html, 'Purchase MTD = qty × supplier cost');
        $this->assertStringContainsString('Inbound SKU', $html, 'Recent purchases lists receipt product');
    }

    public function test_dashboard_chart_script_embeds_seven_day_series(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('labels:', $content);
        $this->assertStringContainsString('datasets:', $content);

        $data = DashboardController::dashboardViewData();
        $this->assertCount(7, $data['chart_labels']);
        $this->assertCount(7, $data['chart_sales']);
        $this->assertCount(7, $data['chart_purchases']);
    }

    public function test_dashboard_view_data_matches_expected_aggregates(): void
    {
        $user = $this->makeUser();

        $p = Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'Agg Product',
            'price' => 10,
            'supplierprice' => 4,
            'quantity' => 3,
            'stock_alert' => 10,
        ]));

        $order = new Order;
        $order->name = 'Cust';
        $order->mobile = '1';
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $p->id,
            'quantity' => 1,
            'unitprice' => 10,
            'amount' => 10,
            'discount' => 0,
        ]);

        StockReceipt::create([
            'product_id' => $p->id,
            'user_id' => $user->id,
            'quantity' => 2,
            'received_at' => Carbon::now()->startOfMonth()->addDays(3)->toDateString(),
            'batch_number' => null,
            'expiry_date' => null,
            'supplier_id' => null,
            'document_reference' => null,
            'notes' => null,
        ]);

        $d = DashboardController::dashboardViewData();

        $this->assertSame(10.0, $d['today_sales']);
        $this->assertSame(1, $d['orders_today']);
        $this->assertSame(1, $d['low_stock_count']);
        $this->assertSame(10.0, $d['month_sales']);
        $this->assertSame(8.0, $d['purchase_mtd']);
        $this->assertSame(0.0, $d['total_sales_return']);
        $this->assertSame(0.0, $d['total_purchase_return']);
        $this->assertSame('Cust', $d['recent_orders']->first()->name);
        $this->assertSame(1, $d['recent_receipts']->count());
    }

    public function test_quick_action_links_point_to_inventory_and_pos_routes(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('orders.index'), false)
            ->assertSee(route('inventory.receive.create'), false)
            ->assertSee(route('inventory.manage-stock'), false)
            ->assertSee(route('products.index'), false);
    }

    public function test_dashboard_expiry_watch_inventory_value_and_avg_sale(): void
    {
        $user = $this->makeUser();

        $expiryWithin90 = Carbon::today()->addDays(45);
        Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'ExpiresInWindow',
            'expiredate' => $expiryWithin90->toDateString(),
            'quantity' => 4,
            'stock_alert' => 1,
            'price' => 12.5,
        ]));

        $p = Product::where('product_name', 'ExpiresInWindow')->first();
        $this->assertNotNull($p);

        $order = new Order;
        $order->name = 'Avg Test';
        $order->mobile = '0';
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $p->id,
            'quantity' => 1,
            'unitprice' => 12,
            'amount' => 100,
            'discount' => 0,
        ]);

        $metrics = DashboardController::dashboardViewData();

        $this->assertGreaterThanOrEqual(1, $metrics['expiring_soon_count']);
        $this->assertSame(50.0, $metrics['inventory_retail_value']);
        $this->assertSame(100.0, $metrics['today_sales']);
        $this->assertSame(1, $metrics['orders_today']);
        $this->assertSame(100.0, $metrics['avg_order_value_today']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Expiry watch (90 days)', false)
            ->assertSee('Est. inventory value', false);
    }
}
