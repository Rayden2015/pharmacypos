<?php

namespace Tests\Feature\Pharmacy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class ReportsPermissionTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_sales_report_forbidden_without_reports_view(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'No Reports',
            'email' => uniqid('nr', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
        $this->grantPermissions($user, ['pos.access']);

        $this->actingAs($user)
            ->get(route('reports.sales'))
            ->assertForbidden();
    }

    public function test_sales_export_forbidden_without_reports_export(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'View Only',
            'email' => uniqid('vo', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
        $this->grantPermissions($user, ['reports.view']);

        $this->actingAs($user)
            ->get(route('reports.sales.export'))
            ->assertForbidden();
    }

    public function test_dashboard_csv_forbidden_without_reports_export(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'Dash No Export',
            'email' => uniqid('dne', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
        $this->grantPermissions($user, ['reports.view', 'pos.access']);

        $this->actingAs($user)
            ->get(route('dashboard.export'))
            ->assertForbidden();
    }

    public function test_pos_forbidden_without_pos_access(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'No POS',
            'email' => uniqid('np', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
        $this->grantPermissions($user, ['reports.view']);

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertForbidden();
    }

    public function test_periodic_line_items_report_ok_with_reports_view(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'Periodic viewer',
            'email' => uniqid('per', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
        $this->grantPermissions($user, ['reports.view']);

        $this->actingAs($user)
            ->get(route('reports.periodic'))
            ->assertOk();
    }

    public function test_periodic_export_forbidden_without_reports_export(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'Periodic view only',
            'email' => uniqid('peo', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
        $this->grantPermissions($user, ['reports.view']);

        $today = now()->toDateString();
        $this->actingAs($user)
            ->get(route('reports.periodic.export', ['start_date' => $today, 'end_date' => $today]))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('reports.periodic.pdf', ['start_date' => $today, 'end_date' => $today]))
            ->assertForbidden();
    }

    public function test_periodic_export_and_pdf_ok_with_reports_export(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'Periodic exporter',
            'email' => uniqid('pex', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
        $this->grantPermissions($user, ['reports.view', 'reports.export']);

        $today = now()->toDateString();
        $this->actingAs($user)
            ->get(route('reports.periodic.export', ['start_date' => $today, 'end_date' => $today]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($user)
            ->get(route('reports.periodic.pdf', ['start_date' => $today, 'end_date' => $today]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
