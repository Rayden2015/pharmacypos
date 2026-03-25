<?php

namespace Tests\Feature\EndToEnd;

use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\Site;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

/**
 * End-to-end style coverage for sales report: real login session, KPI math, search, CSV, print.
 */
class SalesReportEndToEndTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeReportUser(string $email = 'sales-e2e@example.test', string $password = 'secret-123'): User
    {
        $this->seedPermissionsCatalog();
        $companyId = Company::defaultId();
        Site::query()->whereKey(Site::defaultId())->update(['company_id' => $companyId]);

        $user = User::create([
            'name' => 'Sales E2E',
            'email' => $email,
            'password' => bcrypt($password),
            'confirm_password' => bcrypt($password),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244777000',
            'status' => '1',
            'company_id' => $companyId,
        ]);

        return $this->grantPermissions($user, ['reports.view', 'reports.export']);
    }

    private function catalogProduct(): Product
    {
        return Product::create([
            'company_id' => Company::defaultId(),
            'product_name' => 'E2E Line Item '.uniqid('', true),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'E2E Mfg'], ['name' => 'E2E Mfg'])->id,
            'price' => 100,
            'supplierprice' => 50,
            'quantity' => 1000,
            'stock_alert' => 1,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);
    }

    /**
     * @return array{order: Order, product: Product}
     */
    private function placeInvoice(
        User $actor,
        Product $product,
        int $siteId,
        string $customerName,
        string $mobile,
        int $qty,
        int $unitprice,
        int $lineAmount,
        string $orderTimestamp
    ): array {
        $order = new Order;
        $order->name = $customerName;
        $order->mobile = $mobile;
        $order->site_id = $siteId;
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'unitprice' => $unitprice,
            'discount' => 0,
            'amount' => $lineAmount,
            'unit_of_measure' => null,
            'volume' => null,
        ]);

        Transaction::create([
            'order_id' => $order->id,
            'user_id' => $actor->id,
            'transaction_amount' => $lineAmount,
            'paid_amount' => (float) $lineAmount,
            'balance' => 0,
            'payment_method' => 'Cash',
            'transaction_date' => substr($orderTimestamp, 0, 10),
        ]);

        DB::table('orders')->where('id', $order->id)->update([
            'created_at' => $orderTimestamp,
            'updated_at' => $orderTimestamp,
        ]);
        DB::table('order_details')->where('order_id', $order->id)->update([
            'created_at' => $orderTimestamp,
            'updated_at' => $orderTimestamp,
        ]);

        return ['order' => $order->fresh(), 'product' => $product];
    }

    public function test_login_session_then_sales_report_shows_expected_kpis_and_rows(): void
    {
        $password = 'e2e-sales-pass';
        $user = $this->makeReportUser('e2e-sales-viewer@example.test', $password);
        $siteId = Site::defaultId();
        $product = $this->catalogProduct();

        $day = '2026-06-10 14:00:00';
        $this->placeInvoice($user, $product, $siteId, 'Customer Alpha', '0244111001', 1, 100, 90, $day);
        $this->placeInvoice($user, $product, $siteId, 'Customer Beta', '0244111002', 2, 50, 100, $day);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
        ])->assertRedirect('/home');

        $dayOnly = '2026-06-10';
        $html = $this->get(route('reports.sales', [
            'start_date' => $dayOnly,
            'end_date' => $dayOnly,
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('Customer Alpha', $html);
        $this->assertStringContainsString('Customer Beta', $html);
        // Gross 100 + 2*50 = 200; net 90 + 100 = 190; deductions 10; 2 invoices
        $this->assertStringContainsString('#200.00', $html);
        $this->assertStringContainsString('#190.00', $html);
        $this->assertStringContainsString('#10.00', $html);
        $this->assertStringContainsString('Invoice count', $html);
        $this->assertStringContainsString('text-secondary">2</h5>', $html);
    }

    public function test_search_narrows_table_and_kpis(): void
    {
        $user = $this->makeReportUser();
        $siteId = Site::defaultId();
        $product = $this->catalogProduct();
        $day = '2026-06-15 10:00:00';

        $this->placeInvoice($user, $product, $siteId, 'Qux Medical Buyer', '0244222001', 1, 80, 80, $day);
        $this->placeInvoice($user, $product, $siteId, 'Other Shop', '0244222002', 1, 200, 200, $day);

        $dayOnly = '2026-06-15';
        $html = $this->actingAs($user)->get(route('reports.sales', [
            'start_date' => $dayOnly,
            'end_date' => $dayOnly,
            'q' => 'Qux Medical',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('Qux Medical Buyer', $html);
        $this->assertStringNotContainsString('Other Shop', $html);
        $this->assertStringContainsString('#80.00', $html);
        $this->assertStringNotContainsString('#280.00', $html);
    }

    public function test_csv_export_contains_rows_for_same_filters(): void
    {
        $user = $this->makeReportUser();
        $siteId = Site::defaultId();
        $product = $this->catalogProduct();
        $day = '2026-07-01 12:00:00';

        $this->placeInvoice($user, $product, $siteId, 'CSV Person', '0244333001', 1, 33, 30, $day);

        $response = $this->actingAs($user)->get(route('reports.sales.export', [
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-01',
            'q' => 'CSV Person',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Invoice no.', $csv);
        $this->assertStringContainsString('CSV Person', $csv);
        $this->assertStringContainsString('33.00', $csv);
        $this->assertStringContainsString('30.00', $csv);
    }

    public function test_print_view_shows_kpi_strip_and_invoice_columns(): void
    {
        $user = $this->makeReportUser();
        $siteId = Site::defaultId();
        $product = $this->catalogProduct();
        $this->placeInvoice($user, $product, $siteId, 'Print Me', '0244444001', 3, 10, 25, '2026-08-01 09:00:00');

        $html = $this->actingAs($user)->get(route('reports.sales.print', [
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'q' => 'Print Me',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('Total sales amount', $html);
        $this->assertStringContainsString('Net revenue', $html);
        $this->assertStringContainsString('Line discounts', $html);
        $this->assertStringContainsString('Print Me', $html);
        $this->assertStringContainsString('Range total (net revenue)', $html);
        $this->assertStringContainsString('Search: Print Me', $html);
        // 3 * 10 gross = 30, net 25, deductions 5
        $this->assertStringContainsString('#25.00', $html);
    }

    public function test_kpi_shows_percent_change_when_prior_window_has_sales(): void
    {
        $user = $this->makeReportUser();
        $siteId = Site::defaultId();
        $product = $this->catalogProduct();

        $this->placeInvoice($user, $product, $siteId, 'Prior', '0244555001', 1, 50, 50, '2026-09-19 11:00:00');
        $this->placeInvoice($user, $product, $siteId, 'Current', '0244555002', 1, 100, 100, '2026-09-20 11:00:00');

        // Single-day range 2026-09-20 → prior window is 2026-09-19 only; net was 50, now 100 → +100%
        $html = $this->actingAs($user)->get(route('reports.sales', [
            'start_date' => '2026-09-20',
            'end_date' => '2026-09-20',
        ]))->assertOk()->getContent();

        $this->assertStringContainsString('+100.0%', $html);
        $this->assertStringContainsString('Current', $html);
        $this->assertStringNotContainsString('Prior', $html);
    }
}
