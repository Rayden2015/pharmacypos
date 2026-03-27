<?php

namespace Tests\Feature\Settings;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\PermissionCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_guest_is_redirected_from_audit_log_index(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
        $this->get(route('settings.audit-log.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_audit_permission_gets_403_on_index(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
        $company = Company::create([
            'company_name' => 'Co A',
            'company_email' => 'a@example.test',
            'company_mobile' => '0244000001',
            'company_address' => 'Addr',
            'slug' => 'co-a-'.uniqid('', true),
            'is_active' => true,
        ]);
        $user = User::create([
            'name' => 'Cashier',
            'email' => 'cashier-'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 2,
            'is_super_admin' => false,
            'company_id' => $company->id,
            'mobile' => '0244222000',
            'status' => '1',
            'tenant_role' => 'cashier',
        ]);
        $this->grantPermissions($user, ['pos.access']);

        $this->actingAs($user)->get(route('settings.audit-log.index'))->assertForbidden();
    }

    public function test_tenant_user_cannot_view_other_company_audit_entry(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
        $c1 = Company::create([
            'company_name' => 'Co One',
            'company_email' => 'one@example.test',
            'company_mobile' => '0244000001',
            'company_address' => 'Addr',
            'slug' => 'co-one-'.uniqid('', true),
            'is_active' => true,
        ]);
        $c2 = Company::create([
            'company_name' => 'Co Two',
            'company_email' => 'two@example.test',
            'company_mobile' => '0244000002',
            'company_address' => 'Addr',
            'slug' => 'co-two-'.uniqid('', true),
            'is_active' => true,
        ]);
        $alice = User::create([
            'name' => 'Alice',
            'email' => 'alice-'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'company_id' => $c1->id,
            'mobile' => '0244222001',
            'status' => '1',
            'tenant_role' => 'branch_manager',
        ]);
        $bob = User::create([
            'name' => 'Bob',
            'email' => 'bob-'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'company_id' => $c2->id,
            'mobile' => '0244222002',
            'status' => '1',
            'tenant_role' => 'branch_manager',
        ]);
        $this->grantPermissions($alice, ['audit.view']);
        $this->grantPermissions($bob, ['audit.view']);

        $log = AuditLog::create([
            'user_id' => $alice->id,
            'action' => 'test.cross_tenant',
            'subject_type' => null,
            'subject_id' => null,
            'old_values' => null,
            'new_values' => ['note' => 'from alice'],
            'context' => ['test' => true],
            'created_at' => now(),
        ]);

        $this->actingAs($bob)->get(route('settings.audit-log.show', $log))->assertForbidden();
        $this->actingAs($alice)->get(route('settings.audit-log.show', $log))->assertOk();
    }

    public function test_export_csv_applies_same_scope_and_records_meta_audit(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
        $c1 = Company::create([
            'company_name' => 'Export Co',
            'company_email' => 'export@example.test',
            'company_mobile' => '0244000003',
            'company_address' => 'Addr',
            'slug' => 'export-co-'.uniqid('', true),
            'is_active' => true,
        ]);
        $alice = User::create([
            'name' => 'Export Alice',
            'email' => 'export-alice-'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'company_id' => $c1->id,
            'mobile' => '0244222003',
            'status' => '1',
            'tenant_role' => 'branch_manager',
        ]);
        $this->grantPermissions($alice, ['audit.view']);

        AuditLog::create([
            'user_id' => $alice->id,
            'action' => 'test.export.row',
            'subject_type' => null,
            'subject_id' => null,
            'old_values' => null,
            'new_values' => null,
            'context' => [],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($alice)->get(route('settings.audit-log.export'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('user_email', $response->streamedContent());
        $this->assertStringContainsString('test.export.row', $response->streamedContent());

        $this->assertTrue(
            AuditLog::query()->where('action', 'audit_log.export')->where('user_id', $alice->id)->exists()
        );
    }
}
