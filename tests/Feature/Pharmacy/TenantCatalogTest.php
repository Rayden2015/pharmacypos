<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * @return array{companyA: Company, siteA: Site, companyB: Company, siteB: Site}
     */
    private function twoTenantFixtures(): array
    {
        $companyA = Company::query()->orderBy('id')->firstOrFail();
        $siteA = Site::query()->where('is_default', true)->orderBy('id')->firstOrFail();

        $companyB = Company::query()->create([
            'company_name' => 'Tenant B Pharma',
            'company_email' => 'b.'.uniqid('', true).'@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'tenant-b-'.uniqid(),
            'is_active' => true,
        ]);

        $siteB = Site::query()->create([
            'company_id' => $companyB->id,
            'name' => 'Branch B',
            'code' => 'BRB-'.substr(uniqid(), -4),
            'address' => null,
            'is_active' => true,
            'is_default' => false,
        ]);

        return [
            'companyA' => $companyA,
            'siteA' => $siteA,
            'companyB' => $companyB,
            'siteB' => $siteB,
        ];
    }

    private function makeTenantUser(Company $company, Site $site): User
    {
        return User::create([
            'name' => 'Staff '.$company->id,
            'email' => uniqid('tc', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $company->id,
            'site_id' => $site->id,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    private function makeSuperAdmin(): User
    {
        return User::create([
            'name' => 'Super',
            'email' => uniqid('sa', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => true,
            'company_id' => null,
            'site_id' => Site::defaultId(),
            'mobile' => '0244222001',
            'status' => '1',
        ]);
    }

    private function makeProductForTenant(string $name, int $companyId, int $initialSiteId, int $stockAtSite): Product
    {
        $m = Manufacturer::firstOrCreate(['name' => 'CatMfg'], ['name' => 'CatMfg']);
        $p = new Product([
            'product_name' => $name,
            'description' => 'd',
            'manufacturer_id' => $m->id,
            'price' => 100,
            'supplierprice' => 50,
            'quantity' => $stockAtSite,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-12-01',
            'product_img' => 'product.png',
            'company_id' => $companyId,
        ]);
        $p->initial_site_id = $initialSiteId;
        $p->save();

        ProductSiteStock::query()->updateOrCreate(
            ['product_id' => $p->id, 'site_id' => $initialSiteId],
            ['quantity' => $stockAtSite]
        );
        Product::syncQuantityFromSiteStocks($p->id);

        return $p->fresh();
    }

    public function test_product_index_lists_only_current_tenant_catalog(): void
    {
        $f = $this->twoTenantFixtures();
        $userA = $this->makeTenantUser($f['companyA'], $f['siteA']);

        $pA = $this->makeProductForTenant('Alpha SKU '.uniqid(), $f['companyA']->id, $f['siteA']->id, 10);
        $pB = $this->makeProductForTenant('Beta SKU '.uniqid(), $f['companyB']->id, $f['siteB']->id, 10);

        $this->actingAs($userA)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee($pA->product_name, false)
            ->assertDontSee($pB->product_name, false);
    }

    public function test_grid_view_scopes_products_to_tenant(): void
    {
        $f = $this->twoTenantFixtures();
        $userA = $this->makeTenantUser($f['companyA'], $f['siteA']);
        $pB = $this->makeProductForTenant('Hidden From A '.uniqid(), $f['companyB']->id, $f['siteB']->id, 5);

        $this->actingAs($userA)
            ->get(url('grid'))
            ->assertOk()
            ->assertDontSee($pB->product_name, false);
    }

    public function test_super_admin_sees_products_from_all_tenants_on_index(): void
    {
        $f = $this->twoTenantFixtures();
        $admin = $this->makeSuperAdmin();

        $pA = $this->makeProductForTenant('SA-A '.uniqid(), $f['companyA']->id, $f['siteA']->id, 1);
        $pB = $this->makeProductForTenant('SA-B '.uniqid(), $f['companyB']->id, $f['siteB']->id, 1);

        $this->actingAs($admin)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee($pA->product_name, false)
            ->assertSee($pB->product_name, false);
    }

    public function test_pos_rejects_product_from_different_tenant_than_active_site(): void
    {
        $f = $this->twoTenantFixtures();
        $userA = $this->makeTenantUser($f['companyA'], $f['siteA']);
        $pB = $this->makeProductForTenant('Foreign Line '.uniqid(), $f['companyB']->id, $f['siteB']->id, 50);

        session(['current_site_id' => $f['siteA']->id]);

        $this->actingAs($userA)->post(route('orders.store'), [
            'customerName' => 'Walk-in',
            'customerMobile' => '0244111000',
            'paymentMethod' => 'Cash',
            'paidAmount' => 100,
            'balance' => 0,
            'product_id' => [(string) $pB->id],
            'quantity' => ['1'],
            'discount' => ['0'],
        ])->assertSessionHasErrors('product_id');
    }

    public function test_pos_succeeds_for_same_tenant_product_at_active_site(): void
    {
        $f = $this->twoTenantFixtures();
        $userA = $this->makeTenantUser($f['companyA'], $f['siteA']);
        $pA = $this->makeProductForTenant('Local Line '.uniqid(), $f['companyA']->id, $f['siteA']->id, 20);

        session(['current_site_id' => $f['siteA']->id]);

        $this->actingAs($userA)->post(route('orders.store'), [
            'customerName' => 'Walk-in',
            'customerMobile' => '0244222000',
            'paymentMethod' => 'Cash',
            'paidAmount' => 100,
            'balance' => 0,
            'product_id' => [(string) $pA->id],
            'quantity' => ['2'],
            'discount' => ['0'],
        ])->assertRedirect();

        $this->assertDatabaseHas('order_details', [
            'product_id' => $pA->id,
            'quantity' => 2,
        ]);
    }

    public function test_for_tenant_catalog_scope_filters_query(): void
    {
        $f = $this->twoTenantFixtures();
        $userA = $this->makeTenantUser($f['companyA'], $f['siteA']);
        $this->makeProductForTenant('Scoped-A '.uniqid(), $f['companyA']->id, $f['siteA']->id, 1);
        $this->makeProductForTenant('Scoped-B '.uniqid(), $f['companyB']->id, $f['siteB']->id, 1);

        $this->actingAs($userA);
        $ids = Product::query()->forTenantCatalog()->pluck('id')->sort()->values()->all();

        $this->assertTrue(
            Product::query()->where('company_id', $f['companyB']->id)->whereIn('id', $ids)->doesntExist()
        );
    }

    public function test_visible_for_dashboard_scopes_to_site_company(): void
    {
        $f = $this->twoTenantFixtures();
        $this->makeProductForTenant('Dash-A '.uniqid(), $f['companyA']->id, $f['siteA']->id, 1);
        $this->makeProductForTenant('Dash-B '.uniqid(), $f['companyB']->id, $f['siteB']->id, 1);

        $countA = Product::visibleForDashboard((int) $f['siteA']->id)->count();
        $countB = Product::visibleForDashboard((int) $f['siteB']->id)->count();

        $this->assertGreaterThanOrEqual(1, $countA);
        $this->assertGreaterThanOrEqual(1, $countB);

        $idsA = Product::visibleForDashboard((int) $f['siteA']->id)->pluck('company_id')->unique()->values()->all();
        $this->assertSame([(int) $f['companyA']->id], $idsA);
    }

    public function test_stock_adjustment_returns_not_found_for_product_outside_tenant_catalog(): void
    {
        $f = $this->twoTenantFixtures();
        $userA = $this->makeTenantUser($f['companyA'], $f['siteA']);
        $pB = $this->makeProductForTenant('Adj Foreign '.uniqid(), $f['companyB']->id, $f['siteB']->id, 10);

        $this->actingAs($userA)->post(route('inventory.stock-adjustment.store'), [
            'product_id' => $pB->id,
            'site_id' => $f['siteA']->id,
            'direction' => 'add',
            'quantity' => 1,
            'reason' => 'test',
        ])->assertNotFound();
    }

    public function test_orders_index_pos_product_list_is_tenant_scoped(): void
    {
        $f = $this->twoTenantFixtures();
        $userA = $this->makeTenantUser($f['companyA'], $f['siteA']);
        $pA = $this->makeProductForTenant('POS-A '.uniqid(), $f['companyA']->id, $f['siteA']->id, 5);
        $pB = $this->makeProductForTenant('POS-B '.uniqid(), $f['companyB']->id, $f['siteB']->id, 5);

        $this->actingAs($userA)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($pA->product_name, false)
            ->assertDontSee($pB->product_name, false);
    }
}
