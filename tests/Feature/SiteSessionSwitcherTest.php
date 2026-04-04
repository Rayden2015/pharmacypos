<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSessionSwitcherTest extends TestCase
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
            'company_name' => 'Tenant B Switch',
            'company_email' => 'b.'.uniqid('', true).'@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'tenant-b-sw-'.uniqid(),
            'is_active' => true,
        ]);

        $siteB = Site::query()->create([
            'company_id' => $companyB->id,
            'name' => 'Branch B',
            'code' => 'SWB-'.substr(uniqid(), -4),
            'address' => null,
            'is_active' => true,
            'is_default' => true,
        ]);

        return [
            'companyA' => $companyA,
            'siteA' => $siteA,
            'companyB' => $companyB,
            'siteB' => $siteB,
        ];
    }

    private function secondSiteSameCompany(Company $company, Site $existingDefault): Site
    {
        return Site::query()->create([
            'company_id' => $company->id,
            'name' => 'Second branch '.uniqid(),
            'code' => 'SEC-'.substr(uniqid(), -4),
            'address' => null,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    public function test_tenant_admin_session_switcher_lists_all_active_branches_in_company(): void
    {
        $f = $this->twoTenantFixtures();
        $branch2 = $this->secondSiteSameCompany($f['companyA'], $f['siteA']);

        $admin = User::create([
            'name' => 'TA',
            'email' => uniqid('ta', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $f['companyA']->id,
            'site_id' => $f['siteA']->id,
            'tenant_role' => 'tenant_admin',
            'mobile' => '0244000001',
            'status' => '1',
        ]);

        $ids = Site::forSessionSwitcher($admin)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        $this->assertContains((int) $f['siteA']->id, $ids);
        $this->assertContains((int) $branch2->id, $ids);
    }

    public function test_non_admin_user_sees_only_home_branch_in_session_switcher(): void
    {
        $f = $this->twoTenantFixtures();
        $this->secondSiteSameCompany($f['companyA'], $f['siteA']);

        $cashier = User::create([
            'name' => 'Cashier',
            'email' => uniqid('csh', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $f['companyA']->id,
            'site_id' => $f['siteA']->id,
            'tenant_role' => 'cashier',
            'mobile' => '0244000002',
            'status' => '1',
        ]);

        $ids = Site::forSessionSwitcher($cashier)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertCount(1, $ids);
        $this->assertSame((int) $f['siteA']->id, $ids[0]);
    }

    public function test_tenant_admin_can_switch_session_to_second_branch(): void
    {
        $f = $this->twoTenantFixtures();
        $branch2 = $this->secondSiteSameCompany($f['companyA'], $f['siteA']);

        $admin = User::create([
            'name' => 'TA Switch',
            'email' => uniqid('tasw', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $f['companyA']->id,
            'site_id' => $f['siteA']->id,
            'tenant_role' => 'tenant_admin',
            'mobile' => '0244000003',
            'status' => '1',
        ]);

        session(['current_site_id' => $f['siteA']->id]);

        $this->actingAs($admin)
            ->post(route('sites.switch'), ['site_id' => $branch2->id])
            ->assertRedirect();

        $this->assertSame((int) $branch2->id, (int) session('current_site_id'));
    }

    public function test_cashier_cannot_switch_to_peer_branch(): void
    {
        $f = $this->twoTenantFixtures();
        $branch2 = $this->secondSiteSameCompany($f['companyA'], $f['siteA']);

        $cashier = User::create([
            'name' => 'Cashier',
            'email' => uniqid('csh2', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $f['companyA']->id,
            'site_id' => $f['siteA']->id,
            'tenant_role' => 'cashier',
            'mobile' => '0244000004',
            'status' => '1',
        ]);

        session(['current_site_id' => $f['siteA']->id]);

        $this->actingAs($cashier)
            ->post(route('sites.switch'), ['site_id' => $branch2->id])
            ->assertForbidden();
    }

    public function test_tenant_user_cannot_switch_to_another_company_site(): void
    {
        $f = $this->twoTenantFixtures();

        $userA = User::create([
            'name' => 'Staff A',
            'email' => uniqid('sa', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $f['companyA']->id,
            'site_id' => $f['siteA']->id,
            'tenant_role' => 'tenant_admin',
            'mobile' => '0244000005',
            'status' => '1',
        ]);

        session(['current_site_id' => $f['siteA']->id]);

        $this->actingAs($userA)
            ->post(route('sites.switch'), ['site_id' => $f['siteB']->id])
            ->assertForbidden();
    }

    public function test_tenant_admin_can_select_dashboard_all_branches(): void
    {
        $f = $this->twoTenantFixtures();
        $this->secondSiteSameCompany($f['companyA'], $f['siteA']);

        $admin = User::create([
            'name' => 'TA All Branches',
            'email' => uniqid('taab', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $f['companyA']->id,
            'site_id' => $f['siteA']->id,
            'tenant_role' => 'tenant_admin',
            'mobile' => '0244000007',
            'status' => '1',
        ]);

        $this->actingAs($admin)
            ->post(route('sites.switch'), ['site_id' => 'all'])
            ->assertRedirect();

        $this->assertTrue((bool) session('dashboard_all_branches'));
        $this->assertFalse((bool) session('dashboard_all_sites'));
    }

    public function test_cashier_cannot_select_dashboard_all_branches_even_if_bypassing_ui(): void
    {
        $f = $this->twoTenantFixtures();
        $this->secondSiteSameCompany($f['companyA'], $f['siteA']);

        $cashier = User::create([
            'name' => 'Cashier All',
            'email' => uniqid('csha', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $f['companyA']->id,
            'site_id' => $f['siteA']->id,
            'tenant_role' => 'cashier',
            'mobile' => '0244000008',
            'status' => '1',
        ]);

        $this->actingAs($cashier)
            ->post(route('sites.switch'), ['site_id' => 'all'])
            ->assertForbidden();
    }

    public function test_super_admin_session_switcher_includes_active_sites(): void
    {
        $f = $this->twoTenantFixtures();
        $super = User::create([
            'name' => 'SA',
            'email' => uniqid('sa', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => true,
            'company_id' => null,
            'site_id' => $f['siteA']->id,
            'mobile' => '0244000006',
            'status' => '1',
        ]);

        $ids = Site::forSessionSwitcher($super)->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains((int) $f['siteA']->id, $ids);
        $this->assertContains((int) $f['siteB']->id, $ids);
    }
}
