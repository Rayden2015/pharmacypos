<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\PermissionCatalogSeeder;
use Database\Seeders\TenantRolesBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_tenant_admin_can_view_roles_index(): void
    {
        $this->seed(PermissionCatalogSeeder::class);

        $company = Company::query()->firstOrFail();
        $site = Site::query()->where('company_id', $company->id)->firstOrFail();

        $user = User::create([
            'name' => 'Tenant Admin',
            'email' => 'ta.'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'company_id' => $company->id,
            'site_id' => $site->id,
            'tenant_role' => 'tenant_admin',
            'mobile' => '0244000001',
            'status' => '1',
        ]);

        $this->seed(TenantRolesBootstrapSeeder::class);

        $this->actingAs($user)->get(route('roles.index'))->assertOk()->assertSee('Roles', false);
    }

    public function test_platform_super_admin_cannot_access_tenant_roles(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
        $this->seed(TenantRolesBootstrapSeeder::class);

        $admin = User::create([
            'name' => 'Platform',
            'email' => 'sa.'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => true,
            'company_id' => null,
            'site_id' => Site::query()->value('id'),
            'mobile' => '0244000002',
            'status' => '1',
        ]);

        $this->actingAs($admin)->get(route('roles.index'))->assertForbidden();
    }
}
