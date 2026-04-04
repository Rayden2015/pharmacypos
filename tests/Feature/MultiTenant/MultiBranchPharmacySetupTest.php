<?php

namespace Tests\Feature\MultiTenant;

use App\Http\Controllers\DashboardController;
use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\Supplier;
use App\Models\User;
use App\Support\TenantRolesProvisioner;
use Carbon\Carbon;
use Database\Seeders\PermissionCatalogSeeder;
use Database\Seeders\SubscriptionPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Assessment tests: multi-tenant pharmacy onboarding (tenant + head office + admin),
 * branch lifecycle, and cross-tenant isolation for dashboard/session data.
 */
class MultiBranchPharmacySetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(PermissionCatalogSeeder::class);
        $this->seed(SubscriptionPackageSeeder::class);
    }

    private function makeSuperAdmin(): User
    {
        return User::create([
            'name' => 'Platform QA',
            'email' => uniqid('plat', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => true,
            'company_id' => null,
            'mobile' => '0244000000',
            'status' => '1',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function newTenantAdminFields(string $emailKey = 'owner'): array
    {
        return [
            'admin_name' => 'Provisioned Admin',
            'admin_email' => $emailKey.'.'.uniqid('', true).'@example.test',
            'admin_password' => 'provisioned-secret',
            'admin_password_confirmation' => 'provisioned-secret',
            'admin_mobile' => '0244111222',
        ];
    }

    /**
     * Second tenant (company B) with its own default branch — used for isolation tests.
     *
     * @return array{companyA: Company, siteA: Site, companyB: Company, siteB: Site}
     */
    private function twoPharmacyTenants(): array
    {
        $companyA = Company::query()->orderBy('id')->firstOrFail();
        $siteA = Site::query()->where('company_id', $companyA->id)->where('is_default', true)->firstOrFail();

        $companyB = Company::query()->create([
            'company_name' => 'Competitor Pharmacy',
            'company_email' => 'b.'.uniqid('', true).'@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'competitor-'.uniqid(),
            'is_active' => true,
        ]);

        $siteB = Site::query()->create([
            'company_id' => $companyB->id,
            'name' => 'Competitor Branch',
            'code' => 'CMP-'.$companyB->id,
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

    private function makeTenantAdminForCompany(Company $company, Site $homeBranch): User
    {
        TenantRolesProvisioner::syncSystemRolesForCompany($company->id);

        $user = User::create([
            'name' => 'TA '.$company->company_name,
            'email' => uniqid('ta', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 0,
            'is_super_admin' => false,
            'company_id' => $company->id,
            'site_id' => $homeBranch->id,
            'tenant_role' => 'tenant_admin',
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($company->id);
        $user->assignRole('Tenant Admin');
        $registrar->setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    private function baseProductAttributes(int $companyId): array
    {
        $m = Manufacturer::firstOrCreate(['name' => 'TestMfr'], ['name' => 'TestMfr']);

        return [
            'company_id' => $companyId,
            'description' => 'd',
            'manufacturer_id' => $m->id,
            'price' => 10,
            'supplierprice' => 5,
            'form' => 'Tablet',
            'expiredate' => '2032-01-01',
            'product_img' => 'product.png',
            'quantity' => 100,
            'stock_alert' => 2,
        ];
    }

    private function placeSaleToday(int $siteId, int $productId, float $amount, string $customerLabel = 'Walk-in'): void
    {
        $order = new Order;
        $order->name = $customerLabel;
        $order->mobile = '0';
        $order->site_id = $siteId;
        $order->created_at = Carbon::today()->midDay();
        $order->updated_at = Carbon::today()->midDay();
        $order->save();

        Order_detail::create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'quantity' => 1,
            'unitprice' => $amount,
            'amount' => $amount,
            'discount' => 0,
            'created_at' => Carbon::today()->midDay(),
            'updated_at' => Carbon::today()->midDay(),
        ]);
    }

    public function test_super_admin_provisioning_creates_head_office_default_and_assigns_admin(): void
    {
        $super = $this->makeSuperAdmin();
        $fields = $this->newTenantAdminFields('prov');
        $email = 'co.'.uniqid('', true).'@example.test';

        $this->actingAs($super)->post(route('super-admin.companies.store'), array_merge([
            'company_name' => 'Green Cross Ltd',
            'company_email' => $email,
            'company_mobile' => '',
            'company_address' => 'Accra',
            'is_active' => '1',
        ], $fields))->assertRedirect(route('super-admin.companies.index'));

        $company = Company::query()->where('company_email', $email)->firstOrFail();

        $this->assertDatabaseHas('sites', [
            'company_id' => $company->id,
            'name' => 'Head office',
            'is_default' => true,
        ]);

        $head = Site::query()->where('company_id', $company->id)->where('is_default', true)->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => $fields['admin_email'],
            'company_id' => $company->id,
            'site_id' => $head->id,
            'tenant_role' => 'tenant_admin',
        ]);

        $admin = User::query()->where('email', $fields['admin_email'])->firstOrFail();
        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $this->assertTrue($admin->hasRole('Tenant Admin'));
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }

    public function test_tenant_admin_cannot_delete_head_office(): void
    {
        $x = $this->twoPharmacyTenants();
        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)
            ->delete(route('sites.destroy', $x['siteA']))
            ->assertRedirect(route('sites.index'));

        $this->assertTrue(session()->has('error'));
        $this->assertDatabaseHas('sites', ['id' => $x['siteA']->id]);
    }

    public function test_tenant_admin_head_office_stays_default_when_update_omits_default_checkbox(): void
    {
        $x = $this->twoPharmacyTenants();
        $branch2 = Site::query()->create([
            'company_id' => $x['companyA']->id,
            'name' => 'Annex',
            'code' => 'ANX-'.substr(uniqid(), -5),
            'is_active' => true,
            'is_default' => false,
        ]);

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);
        $this->assertTrue($x['siteA']->is_default);

        $this->actingAs($adminA)->put(route('sites.update', $x['siteA']), [
            'name' => 'Renamed head office',
            'code' => $x['siteA']->code,
            'is_active' => '1',
        ])->assertRedirect(route('sites.index'));

        $x['siteA']->refresh();
        $branch2->refresh();
        $this->assertTrue($x['siteA']->is_default);
        $this->assertFalse($branch2->is_default);
        $this->assertSame('Renamed head office', $x['siteA']->name);
    }

    public function test_tenant_admin_cannot_open_edit_for_another_tenants_branch(): void
    {
        $x = $this->twoPharmacyTenants();
        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)
            ->get(route('sites.edit', $x['siteB']))
            ->assertForbidden();
    }

    public function test_tenant_admin_sites_index_does_not_list_other_company_branches(): void
    {
        $x = $this->twoPharmacyTenants();
        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)
            ->get(route('sites.index'))
            ->assertOk()
            ->assertSee($x['siteA']->name, false)
            ->assertDontSee($x['siteB']->name, false);
    }

    public function test_tenant_admin_create_branch_is_attached_to_their_company(): void
    {
        $x = $this->twoPharmacyTenants();
        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $code = 'NEW-'.substr(uniqid(), -5);
        $this->actingAs($adminA)->post(route('sites.store'), [
            'name' => 'Uptown clinic pharmacy',
            'code' => $code,
            'is_default' => '0',
        ])->assertRedirect(route('sites.index'));

        $this->assertDatabaseHas('sites', [
            'company_id' => $x['companyA']->id,
            'name' => 'Uptown clinic pharmacy',
            'code' => $code,
            'is_default' => false,
        ]);
    }

    public function test_dashboard_all_branches_totals_exclude_other_tenant_sales(): void
    {
        $x = $this->twoPharmacyTenants();
        $siteA2 = Site::query()->create([
            'company_id' => $x['companyA']->id,
            'name' => 'Branch 2 A',
            'code' => 'A2-'.substr(uniqid(), -4),
            'is_active' => true,
            'is_default' => false,
        ]);

        $productA = Product::create(array_merge($this->baseProductAttributes($x['companyA']->id), [
            'product_name' => 'Scope SKU A',
        ]));
        $productB = Product::create(array_merge($this->baseProductAttributes($x['companyB']->id), [
            'product_name' => 'Scope SKU B',
        ]));

        $this->placeSaleToday($x['siteA']->id, $productA->id, 40.0);
        $this->placeSaleToday($siteA2->id, $productA->id, 35.0);
        $this->placeSaleToday($x['siteB']->id, $productB->id, 999.0);

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)->withSession([
            'dashboard_all_branches' => true,
            'dashboard_all_sites' => false,
            'current_site_id' => $x['siteA']->id,
        ]);

        $metrics = DashboardController::dashboardViewData();
        $this->assertSame(75.0, $metrics['today_sales']);
        $this->assertSame(2, $metrics['orders_today']);
    }

    public function test_dashboard_single_branch_excludes_other_tenant_sales(): void
    {
        $x = $this->twoPharmacyTenants();

        $productA = Product::create(array_merge($this->baseProductAttributes($x['companyA']->id), [
            'product_name' => 'Single scope A',
        ]));
        $productB = Product::create(array_merge($this->baseProductAttributes($x['companyB']->id), [
            'product_name' => 'Single scope B',
        ]));

        $this->placeSaleToday($x['siteA']->id, $productA->id, 22.0);
        $this->placeSaleToday($x['siteB']->id, $productB->id, 888.0);

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)->withSession([
            'dashboard_all_branches' => false,
            'dashboard_all_sites' => false,
            'current_site_id' => $x['siteA']->id,
        ]);

        $metrics = DashboardController::dashboardViewData();
        $this->assertSame(22.0, $metrics['today_sales']);
        $this->assertSame(1, $metrics['orders_today']);
    }

    public function test_platform_super_admin_all_sites_dashboard_still_sees_cross_tenant_totals(): void
    {
        $x = $this->twoPharmacyTenants();

        $productA = Product::create(array_merge($this->baseProductAttributes($x['companyA']->id), [
            'product_name' => 'Platform SKU A',
        ]));
        $productB = Product::create(array_merge($this->baseProductAttributes($x['companyB']->id), [
            'product_name' => 'Platform SKU B',
        ]));

        $this->placeSaleToday($x['siteA']->id, $productA->id, 10.0);
        $this->placeSaleToday($x['siteB']->id, $productB->id, 25.0);

        $super = $this->makeSuperAdmin();

        $this->actingAs($super)->withSession([
            'dashboard_all_sites' => true,
            'dashboard_all_branches' => false,
        ]);

        $metrics = DashboardController::dashboardViewData();
        $this->assertSame(35.0, $metrics['today_sales']);
    }

    public function test_pos_orders_index_lists_only_same_tenant_orders(): void
    {
        $x = $this->twoPharmacyTenants();
        $productA = Product::create(array_merge($this->baseProductAttributes($x['companyA']->id), [
            'product_name' => 'POS iso A '.uniqid(),
        ]));
        $productB = Product::create(array_merge($this->baseProductAttributes($x['companyB']->id), [
            'product_name' => 'POS iso B '.uniqid(),
        ]));

        $this->placeSaleToday($x['siteA']->id, $productA->id, 5.0, 'Customer_Alpha_POS');
        $this->placeSaleToday($x['siteB']->id, $productB->id, 5.0, 'Customer_Beta_Leak_POS');

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertViewHas('order', function ($orders) {
                $names = $orders->pluck('name')->all();

                return in_array('Customer_Alpha_POS', $names, true)
                    && ! in_array('Customer_Beta_Leak_POS', $names, true);
            });
    }

    public function test_reports_periodic_excludes_other_tenant_lines(): void
    {
        $x = $this->twoPharmacyTenants();
        $productA = Product::create(array_merge($this->baseProductAttributes($x['companyA']->id), [
            'product_name' => 'PeriodicLineA '.uniqid(),
        ]));
        $productB = Product::create(array_merge($this->baseProductAttributes($x['companyB']->id), [
            'product_name' => 'PeriodicLineB '.uniqid(),
        ]));

        $this->placeSaleToday($x['siteA']->id, $productA->id, 12.0);
        $this->placeSaleToday($x['siteB']->id, $productB->id, 99.0);

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);
        $today = Carbon::today()->toDateString();

        $this->actingAs($adminA)
            ->get(route('reports.periodic', [
                'start_date' => $today,
                'end_date' => $today,
            ]))
            ->assertOk()
            ->assertSee($productA->product_name, false)
            ->assertDontSee($productB->product_name, false);
    }

    public function test_reports_sales_excludes_other_tenant_invoices(): void
    {
        $x = $this->twoPharmacyTenants();
        $productA = Product::create(array_merge($this->baseProductAttributes($x['companyA']->id), [
            'product_name' => 'SalesInvA '.uniqid(),
        ]));
        $productB = Product::create(array_merge($this->baseProductAttributes($x['companyB']->id), [
            'product_name' => 'SalesInvB '.uniqid(),
        ]));

        $this->placeSaleToday($x['siteA']->id, $productA->id, 7.0, 'SalesAlphaName');
        $this->placeSaleToday($x['siteB']->id, $productB->id, 7.0, 'SalesBetaLeak');

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);
        $today = Carbon::today()->toDateString();

        $this->actingAs($adminA)
            ->get(route('reports.sales', [
                'start_date' => $today,
                'end_date' => $today,
            ]))
            ->assertOk()
            ->assertSee('SalesAlphaName', false)
            ->assertDontSee('SalesBetaLeak', false);
    }

    public function test_tenant_admin_suppliers_list_is_scoped_to_their_company(): void
    {
        $x = $this->twoPharmacyTenants();

        Supplier::query()->create([
            'company_id' => $x['companyA']->id,
            'supplier_name' => 'Vendor_Only_A_'.uniqid(),
            'address' => 'A',
            'mobile' => '0200000001',
            'email' => 'a@example.test',
        ]);
        Supplier::query()->create([
            'company_id' => $x['companyB']->id,
            'supplier_name' => 'Vendor_Only_B_'.uniqid(),
            'address' => 'B',
            'mobile' => '0200000002',
            'email' => 'b@example.test',
        ]);

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Vendor_Only_A', false)
            ->assertDontSee('Vendor_Only_B', false);
    }

    public function test_tenant_cannot_view_stock_receipt_from_another_organization(): void
    {
        $x = $this->twoPharmacyTenants();

        $productB = Product::create(array_merge($this->baseProductAttributes($x['companyB']->id), [
            'product_name' => 'ReceiptIsoB '.uniqid(),
        ]));

        $adminB = $this->makeTenantAdminForCompany($x['companyB'], $x['siteB']);

        $receipt = StockReceipt::query()->create([
            'product_id' => $productB->id,
            'user_id' => $adminB->id,
            'site_id' => $x['siteB']->id,
            'quantity' => 3,
            'received_at' => Carbon::today()->toDateString(),
        ]);

        $adminA = $this->makeTenantAdminForCompany($x['companyA'], $x['siteA']);

        $this->actingAs($adminA)
            ->get(route('inventory.receipts.show', $receipt))
            ->assertForbidden();
    }
}
