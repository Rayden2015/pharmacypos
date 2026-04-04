<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Company;
use App\Models\Site;
use App\Models\SubscriptionPackage;
use App\Models\SubscriptionPayment;
use App\Models\TenantSubscription;
use App\Models\User;
use Database\Seeders\PermissionCatalogSeeder;
use Database\Seeders\SubscriptionPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(PermissionCatalogSeeder::class);
        $this->seed(SubscriptionPackageSeeder::class);
    }

    /**
     * @return array<string, string>
     */
    private function newTenantAdminFields(): array
    {
        return [
            'admin_name' => 'Tenant Owner',
            'admin_email' => 'owner.'.uniqid('', true).'@example.test',
            'admin_password' => 'secretpass',
            'admin_password_confirmation' => 'secretpass',
            'admin_mobile' => '0244999888',
        ];
    }

    private function makeSuperAdmin(): User
    {
        return User::create([
            'name' => 'Platform Admin',
            'email' => uniqid('super', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => true,
            'company_id' => null,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    private function makeTenantStaff(): User
    {
        return User::create([
            'name' => 'Branch Staff',
            'email' => uniqid('staff', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222001',
            'status' => '1',
        ]);
    }

    public function test_guest_is_redirected_from_super_admin_routes(): void
    {
        $this->get(route('super-admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('super-admin.companies.index'))->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_access_super_admin_routes(): void
    {
        $user = $this->makeTenantStaff();

        foreach ([
            route('super-admin.dashboard'),
            route('super-admin.domain'),
            route('super-admin.companies.index'),
            route('super-admin.companies.create'),
            route('super-admin.packages.index'),
            route('super-admin.subscriptions.index'),
            route('super-admin.payments.index'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_non_super_admin_cannot_post_to_super_admin_endpoints(): void
    {
        $user = $this->makeTenantStaff();
        $company = Company::query()->firstOrFail();
        $pkg = SubscriptionPackage::query()->firstOrFail();

        $this->actingAs($user)->post(route('super-admin.companies.store'), [
            'company_name' => 'X',
            'company_email' => 'x@example.test',
        ])->assertForbidden();

        $this->actingAs($user)->post(route('super-admin.packages.store'), [
            'name' => 'Plan X',
            'billing_cycle' => 'monthly',
            'price' => 10,
        ])->assertForbidden();

        $this->actingAs($user)->post(route('super-admin.subscriptions.store'), [
            'company_id' => $company->id,
            'subscription_package_id' => $pkg->id,
            'status' => 'active',
        ])->assertForbidden();

        $this->actingAs($user)->post(route('super-admin.payments.store'), [
            'company_id' => $company->id,
            'amount' => 100,
            'status' => 'paid',
        ])->assertForbidden();
    }

    public function test_super_admin_is_redirected_from_pos_orders(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->get(route('orders.index'))->assertRedirect(route('super-admin.dashboard'));
    }

    public function test_super_admin_can_view_platform_dashboard_and_domain(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->get(route('super-admin.dashboard'))->assertOk()->assertSee('Super Admin', false);
        $this->actingAs($admin)->get(route('super-admin.domain'))->assertOk()->assertSee('Domain mapping', false);
    }

    public function test_super_admin_can_list_and_create_company(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->get(route('super-admin.companies.index'))->assertOk();
        $this->actingAs($admin)->get(route('super-admin.companies.create'))->assertOk();

        $email = 'newco.'.uniqid().'@example.test';
        $adminFields = $this->newTenantAdminFields();
        $response = $this->actingAs($admin)->post(route('super-admin.companies.store'), array_merge([
            'company_name' => 'Acme Pharmacies',
            'company_email' => $email,
            'company_mobile' => '',
            'company_address' => 'Accra',
            'is_active' => '1',
        ], $adminFields));

        $response->assertRedirect(route('super-admin.companies.index'));
        $this->assertDatabaseHas('companies', [
            'company_name' => 'Acme Pharmacies',
            'company_email' => $email,
        ]);

        $company = Company::query()->where('company_email', $email)->firstOrFail();
        $this->assertDatabaseHas('sites', [
            'company_id' => $company->id,
            'is_default' => true,
            'name' => 'Head office',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => $adminFields['admin_email'],
            'company_id' => $company->id,
            'tenant_role' => 'tenant_admin',
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => User::query()->where('email', $adminFields['admin_email'])->value('id'),
            'company_id' => $company->id,
        ]);
    }

    public function test_super_admin_can_create_tenant_admin_for_existing_company(): void
    {
        $admin = $this->makeSuperAdmin();
        $company = Company::query()->firstOrFail();
        $site = Site::query()->where('company_id', $company->id)->first();
        if (! $site) {
            $site = Site::query()->create([
                'company_id' => $company->id,
                'name' => 'Bootstrap branch',
                'code' => 'BT-'.$company->id,
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        $fields = $this->newTenantAdminFields();
        $this->actingAs($admin)->post(route('super-admin.tenant-admins.store'), array_merge([
            'company_id' => (string) $company->id,
            'site_id' => (string) $site->id,
        ], $fields))->assertRedirect(route('super-admin.companies.index'));

        $this->assertDatabaseHas('users', [
            'email' => $fields['admin_email'],
            'company_id' => $company->id,
            'tenant_role' => 'tenant_admin',
            'site_id' => $site->id,
        ]);
    }

    public function test_super_admin_company_store_requires_tenant_admin(): void
    {
        $admin = $this->makeSuperAdmin();
        $email = 'incomplete.'.uniqid().'@example.test';

        $this->actingAs($admin)->post(route('super-admin.companies.store'), [
            'company_name' => 'Incomplete Co',
            'company_email' => $email,
            'is_active' => '1',
        ])->assertSessionHasErrors(['admin_name', 'admin_email', 'admin_password']);
    }

    public function test_super_admin_can_create_company_with_initial_subscription(): void
    {
        $admin = $this->makeSuperAdmin();
        $pkg = SubscriptionPackage::query()->where('billing_cycle', 'monthly')->firstOrFail();

        $email = 'subco.'.uniqid().'@example.test';
        $this->actingAs($admin)->post(route('super-admin.companies.store'), array_merge([
            'company_name' => 'Subscribed Co',
            'company_email' => $email,
            'subscription_package_id' => $pkg->id,
            'is_active' => '1',
        ], $this->newTenantAdminFields()))->assertRedirect(route('super-admin.companies.index'));

        $company = Company::query()->where('company_email', $email)->firstOrFail();
        $this->assertDatabaseHas('tenant_subscriptions', [
            'company_id' => $company->id,
            'subscription_package_id' => $pkg->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_update_company(): void
    {
        $admin = $this->makeSuperAdmin();
        $company = Company::query()->firstOrFail();

        $this->actingAs($admin)->get(route('super-admin.companies.edit', $company))->assertOk();

        $newName = 'Renamed '.uniqid();
        $this->actingAs($admin)->put(route('super-admin.companies.update', $company), [
            'company_name' => $newName,
            'company_email' => $company->company_email,
            'slug' => $company->slug,
            'is_active' => '1',
        ])->assertRedirect(route('super-admin.companies.index'));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'company_name' => $newName,
        ]);
    }

    public function test_super_admin_can_delete_company_without_sites_or_users(): void
    {
        $admin = $this->makeSuperAdmin();
        $company = Company::query()->create([
            'company_name' => 'Deletable Co',
            'company_email' => 'del.'.uniqid().'@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'deletable-'.uniqid(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)->delete(route('super-admin.companies.destroy', $company))
            ->assertRedirect(route('super-admin.companies.index'));

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_super_admin_can_manage_packages(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)->get(route('super-admin.packages.index'))->assertOk();
        $this->actingAs($admin)->get(route('super-admin.packages.create'))->assertOk();

        $this->actingAs($admin)->post(route('super-admin.packages.store'), [
            'name' => 'Starter',
            'billing_cycle' => 'monthly',
            'price' => 29.99,
            'billing_days' => 30,
            'sort_order' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('super-admin.packages.index'));

        $package = SubscriptionPackage::query()->where('name', 'Starter')->where('billing_cycle', 'monthly')->firstOrFail();

        $this->actingAs($admin)->get(route('super-admin.packages.edit', $package))->assertOk();

        $this->actingAs($admin)->put(route('super-admin.packages.update', $package), [
            'name' => 'Starter Plus',
            'billing_cycle' => 'monthly',
            'price' => 39.99,
            'billing_days' => 30,
            'sort_order' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('super-admin.packages.index'));

        $this->assertDatabaseHas('subscription_packages', [
            'id' => $package->id,
            'name' => 'Starter Plus',
        ]);

        $this->actingAs($admin)->delete(route('super-admin.packages.destroy', $package->fresh()))
            ->assertRedirect(route('super-admin.packages.index'));

        $this->assertDatabaseMissing('subscription_packages', ['id' => $package->id]);
    }

    public function test_super_admin_can_list_and_create_subscription(): void
    {
        $admin = $this->makeSuperAdmin();
        $company = Company::query()->firstOrFail();
        $pkg = SubscriptionPackage::query()->firstOrFail();

        $this->actingAs($admin)->get(route('super-admin.subscriptions.index'))->assertOk();
        $this->actingAs($admin)->get(route('super-admin.subscriptions.create'))->assertOk();

        $this->actingAs($admin)->post(route('super-admin.subscriptions.store'), [
            'company_id' => $company->id,
            'subscription_package_id' => $pkg->id,
            'status' => 'active',
            'amount' => 99,
            'payment_method' => 'Credit Card',
        ])->assertRedirect(route('super-admin.subscriptions.index'));

        $this->assertDatabaseHas('tenant_subscriptions', [
            'company_id' => $company->id,
            'subscription_package_id' => $pkg->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_list_and_create_purchase_transaction(): void
    {
        $admin = $this->makeSuperAdmin();
        $company = Company::query()->firstOrFail();

        $this->actingAs($admin)->get(route('super-admin.payments.index'))->assertOk();
        $this->actingAs($admin)->get(route('super-admin.payments.create'))->assertOk();

        $this->actingAs($admin)->post(route('super-admin.payments.store'), [
            'company_id' => $company->id,
            'invoice_reference' => 'INV-'.uniqid(),
            'amount' => 250.5,
            'payment_method' => 'Card',
            'status' => 'paid',
            'description' => 'Annual renewal',
        ])->assertRedirect(route('super-admin.payments.index'));

        $this->assertDatabaseHas('subscription_payments', [
            'company_id' => $company->id,
            'amount' => 250.5,
            'status' => 'paid',
        ]);
    }

    public function test_super_admin_companies_index_supports_search_filter(): void
    {
        $admin = $this->makeSuperAdmin();
        $company = Company::query()->firstOrFail();

        $this->actingAs($admin)->get(route('super-admin.companies.index', [
            'search' => $company->company_name,
        ]))->assertOk()->assertSee($company->company_name, false);
    }
}
