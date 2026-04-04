<?php

namespace Tests\Feature\Pharmacy;

use App\Http\Controllers\DashboardController;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(string $name = 'Dash User', bool $superAdmin = false): User
    {
        if (! $superAdmin) {
            $this->seedPermissionsCatalog();
        }

        $user = User::create([
            'name' => $name,
            'email' => uniqid('dash', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => $superAdmin,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        if (! $superAdmin) {
            return $this->grantPermissions($user, [
                'pos.access',
                'reports.view',
                'reports.export',
                'products.view',
                'inventory.view',
            ]);
        }

        return $user;
    }

    private function baseProductAttributes(): array
    {
        $m = Manufacturer::firstOrCreate(['name' => 'BrandCo'], ['name' => 'BrandCo']);

        return [
            'description' => 'd',
            'manufacturer_id' => $m->id,
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

    public function test_dashboard_csv_export_is_authenticated(): void
    {
        $this->get(route('dashboard.export'))->assertRedirect(route('login'));

        $user = $this->makeUser();
        $this->actingAs($user)
            ->get(route('dashboard.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
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
                ->assertSee('Sales vs purchase', false)
                ->assertSee('Low stock products', false)
                ->assertSee('Recent activity', false)
                ->assertSee('Total sales (MTD)', false)
                ->assertSee('Sales / purchase returns', false)
                ->assertSee('Total purchase (MTD)', false)
                ->assertSee('Today\'s sales', false)
                ->assertSee('POS orders today', false)
                ->assertSee('Payments today', false)
                ->assertSee('Low stock alerts', false)
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
        $order->site_id = Site::defaultId();
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
            'site_id' => $order->site_id,
            'company_id' => (int) Site::query()->findOrFail($order->site_id)->company_id,
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
            'site_id' => Site::defaultId(),
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
        $order->site_id = Site::defaultId();
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
            'site_id' => Site::defaultId(),
            'quantity' => 2,
            'received_at' => Carbon::today()->toDateString(),
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

    public function test_dashboard_invoice_due_uses_recorded_balances(): void
    {
        $user = $this->makeUser();

        $product = Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'Credit Sale SKU',
            'quantity' => 100,
            'stock_alert' => 5,
            'price' => 20,
        ]));

        $order = new Order;
        $order->name = 'Credit Customer';
        $order->mobile = '0244999999';
        $order->site_id = Site::defaultId();
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unitprice' => 20,
            'amount' => 20,
            'discount' => 0,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'site_id' => $order->site_id,
            'company_id' => (int) Site::query()->findOrFail($order->site_id)->company_id,
            'user_id' => $user->id,
            'transaction_amount' => 20,
            'paid_amount' => 5,
            'balance' => 15,
            'payment_method' => 'Cash',
            'transaction_date' => Carbon::today()->toDateString(),
        ]);

        $d = DashboardController::dashboardViewData();

        $this->assertSame(15.0, $d['invoice_due']);
        $this->assertSame(15.0, $d['ar_open_total']);
        $this->assertSame(1, $d['transactions_with_balance_mtd']);
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
        $order->site_id = Site::defaultId();
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

    public function test_super_admin_dashboard_all_sites_aggregates_sales_across_branches(): void
    {
        $siteA = Site::query()->where('is_default', true)->first();
        $this->assertNotNull($siteA);
        $siteB = Site::create([
            'name' => 'North Branch',
            'code' => 'NORTH',
            'is_active' => true,
            'is_default' => false,
        ]);

        $super = $this->makeUser('Super Admin', true);
        $product = Product::create(array_merge($this->baseProductAttributes(), [
            'product_name' => 'Multi-Site SKU',
            'quantity' => 100,
            'stock_alert' => 5,
        ]));

        foreach ([[$siteA->id, 10.0], [$siteB->id, 25.0]] as [$siteId, $amount]) {
            $order = new Order;
            $order->name = 'Walk-in';
            $order->mobile = '0';
            $order->site_id = $siteId;
            $order->save();

            Order_detail::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unitprice' => $amount,
                'amount' => $amount,
                'discount' => 0,
            ]);
        }

        $this->actingAs($super)
            ->withSession(['current_site_id' => $siteA->id, 'dashboard_all_sites' => false])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(number_format(10, 2), false);

        $this->actingAs($super)
            ->withSession(['current_site_id' => $siteA->id, 'dashboard_all_sites' => true])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(number_format(35, 2), false)
            ->assertSee('Dashboard metrics:', false)
            ->assertSee('All sites', false);
    }

    public function test_non_super_admin_cannot_switch_dashboard_to_all_sites(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)
            ->post(route('sites.switch'), ['site_id' => 'all'])
            ->assertForbidden();
    }
}
