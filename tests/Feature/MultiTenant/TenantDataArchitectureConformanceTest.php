<?php

namespace Tests\Feature\MultiTenant;

use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TenantDataConformance;
use App\Support\TenantRolesProvisioner;
use Database\Seeders\PermissionCatalogSeeder;
use Database\Seeders\SubscriptionPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ensures legacy / upgraded tenant databases can be validated and nudged into the multi-tenant,
 * multi-branch conventions (user home branch, scoped payments, supplier company).
 */
class TenantDataArchitectureConformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionCatalogSeeder::class);
        $this->seed(SubscriptionPackageSeeder::class);
    }

    /**
     * @return array{companyA: Company, siteA: Site, companyB: Company, siteB: Site}
     */
    private function twoPharmacyTenants(): array
    {
        $companyA = Company::query()->orderBy('id')->firstOrFail();
        $siteA = Site::query()->where('company_id', $companyA->id)->where('is_default', true)->firstOrFail();

        $companyB = Company::query()->create([
            'company_name' => 'Second Chain',
            'company_email' => 'b.'.uniqid('', true).'@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'second-'.uniqid(),
            'is_active' => true,
        ]);

        $siteB = Site::query()->create([
            'company_id' => $companyB->id,
            'name' => 'Second Branch',
            'code' => 'S2-'.$companyB->id,
            'address' => null,
            'is_active' => true,
            'is_default' => true,
        ]);

        TenantRolesProvisioner::syncSystemRolesForCompany($companyB->id);

        return [
            'companyA' => $companyA,
            'siteA' => $siteA,
            'companyB' => $companyB,
            'siteB' => $siteB,
        ];
    }

    private function makeTenantStaff(Company $company, Site $home): User
    {
        TenantRolesProvisioner::syncSystemRolesForCompany($company->id);

        $user = User::create([
            'name' => 'Staff '.$company->company_name,
            'email' => uniqid('conf', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 0,
            'is_super_admin' => false,
            'company_id' => $company->id,
            'site_id' => $home->id,
            'tenant_role' => null,
            'mobile' => '0245000000',
            'status' => '1',
        ]);

        return $user->fresh();
    }

    public function test_conformance_is_clean_for_valid_multi_branch_setup(): void
    {
        $x = $this->twoPharmacyTenants();
        $userA = $this->makeTenantStaff($x['companyA'], $x['siteA']);
        $userB = $this->makeTenantStaff($x['companyB'], $x['siteB']);

        $this->assertTrue(TenantDataConformance::isClean());
        $this->assertTrue(TenantDataConformance::isClean($x['companyA']->id));
        $this->assertEmpty(TenantDataConformance::violations($userB->company_id));
    }

    public function test_detects_user_site_that_belongs_to_another_company(): void
    {
        $x = $this->twoPharmacyTenants();
        $userA = $this->makeTenantStaff($x['companyA'], $x['siteA']);

        User::query()->whereKey($userA->id)->update(['site_id' => $x['siteB']->id]);

        $violations = TenantDataConformance::violations();
        $types = array_column($violations, 'type');
        $this->assertContains('user_site_company_mismatch', $types, implode(', ', $types));
    }

    public function test_repair_realigns_user_home_branch_to_their_organization(): void
    {
        $x = $this->twoPharmacyTenants();
        $userA = $this->makeTenantStaff($x['companyA'], $x['siteA']);

        User::query()->whereKey($userA->id)->update(['site_id' => $x['siteB']->id]);

        $stats = TenantDataConformance::repair();
        $this->assertSame(1, $stats['users_site_aligned']);

        $userA->refresh();
        $this->assertSame((int) $x['siteA']->id, (int) $userA->site_id);
        $this->assertTrue(TenantDataConformance::isClean($x['companyA']->id));
    }

    public function test_repair_resyncs_transaction_scope_from_orders(): void
    {
        $x = $this->twoPharmacyTenants();
        $m = Manufacturer::firstOrCreate(['name' => 'MfrConf'], ['name' => 'MfrConf']);
        $product = Product::query()->create([
            'company_id' => $x['companyA']->id,
            'product_name' => 'Conf SKU',
            'description' => 'd',
            'manufacturer_id' => $m->id,
            'price' => 10,
            'supplierprice' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
            'quantity' => 100,
            'stock_alert' => 2,
        ]);

        $order = Order::query()->create([
            'name' => 'Walk',
            'mobile' => '0',
            'site_id' => $x['siteA']->id,
        ]);

        Order_detail::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unitprice' => 10,
            'amount' => 10,
            'discount' => 0,
        ]);

        $user = $this->makeTenantStaff($x['companyA'], $x['siteA']);

        Transaction::query()->create([
            'order_id' => $order->id,
            'site_id' => $x['siteB']->id,
            'company_id' => $x['companyB']->id,
            'user_id' => $user->id,
            'transaction_amount' => 10,
            'paid_amount' => 10,
            'balance' => 0,
            'payment_method' => 'Cash',
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertNotEmpty(TenantDataConformance::violations());

        $stats = TenantDataConformance::repair();
        $this->assertSame(1, $stats['transactions_scoped']);

        $tx = Transaction::query()->where('order_id', $order->id)->first();
        $this->assertSame((int) $x['siteA']->id, (int) $tx->site_id);
        $this->assertSame((int) $x['companyA']->id, (int) $tx->company_id);
    }

    public function test_repair_assigns_default_company_to_orphan_suppliers(): void
    {
        $x = $this->twoPharmacyTenants();
        $this->assertNotNull($x['companyA']->id);

        Supplier::query()->create([
            'supplier_name' => 'Legacy Vendor',
            'address' => '',
            'mobile' => '',
            'email' => 'v@example.test',
            'company_id' => null,
        ]);

        $types = array_column(TenantDataConformance::violations(), 'type');
        $this->assertContains('supplier_missing_company', $types);

        $stats = TenantDataConformance::repair();
        $this->assertSame(1, $stats['suppliers_company_set']);

        $this->assertNotContains('supplier_missing_company', array_column(TenantDataConformance::violations(), 'type'));
    }

    public function test_detects_orders_without_branch(): void
    {
        $x = $this->twoPharmacyTenants();

        Order::query()->create([
            'name' => 'Legacy',
            'mobile' => '0',
            'site_id' => null,
        ]);

        $types = array_column(TenantDataConformance::violations(), 'type');
        $this->assertContains('order_missing_site', $types);
    }

    public function test_reports_order_lines_that_reference_skus_from_another_organization(): void
    {
        $x = $this->twoPharmacyTenants();
        $m = Manufacturer::firstOrCreate(['name' => 'MfrX'], ['name' => 'MfrX']);

        $productB = Product::query()->create([
            'company_id' => $x['companyB']->id,
            'product_name' => 'WrongCo SKU',
            'description' => 'd',
            'manufacturer_id' => $m->id,
            'price' => 10,
            'supplierprice' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
            'quantity' => 10,
            'stock_alert' => 1,
        ]);

        $orderOnA = Order::query()->create([
            'name' => 'Mixed',
            'mobile' => '1',
            'site_id' => $x['siteA']->id,
        ]);

        Order_detail::query()->create([
            'order_id' => $orderOnA->id,
            'product_id' => $productB->id,
            'quantity' => 1,
            'unitprice' => 10,
            'amount' => 10,
            'discount' => 0,
        ]);

        $violations = TenantDataConformance::violations();
        $types = array_column($violations, 'type');
        $this->assertContains('order_line_product_company_mismatch', $types);

        TenantDataConformance::repair();
        $this->assertContains('order_line_product_company_mismatch', array_column(TenantDataConformance::violations(), 'type'));
    }
}
