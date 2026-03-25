<?php

namespace Tests\Feature\Sites;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeSuperAdmin(): User
    {
        return User::create([
            'name' => 'Super Listing',
            'email' => 'super-list-'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => true,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    public function test_guest_is_redirected_from_branches_index(): void
    {
        $this->get(route('sites.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_branches_create(): void
    {
        $this->get(route('sites.create'))->assertRedirect(route('login'));
    }

    public function test_super_admin_branches_create_form_renders(): void
    {
        $user = $this->makeSuperAdmin();

        $this->actingAs($user)
            ->get(route('sites.create'))
            ->assertOk()
            ->assertSee('Branch manager', false)
            ->assertSee('Create branch', false);
    }

    public function test_super_admin_can_store_branch_with_contact_fields(): void
    {
        $user = $this->makeSuperAdmin();
        $companyId = Company::defaultId();

        $name = 'North Wing '.uniqid('', true);

        $this->actingAs($user)
            ->post(route('sites.store'), [
                'company_id' => (string) $companyId,
                'name' => $name,
                'code' => 'NW-'.substr(uniqid('', true), 0, 8),
                'address' => 'Plot 12',
                'manager_name' => 'Sam Branch Lead',
                'phone' => '+233 24 000 1111',
                'email' => 'north.wing@example.test',
            ])
            ->assertRedirect(route('sites.index'));

        $site = Site::query()->where('name', $name)->firstOrFail();
        $this->assertSame('Sam Branch Lead', $site->manager_name);
        $this->assertSame('+233 24 000 1111', $site->phone);
        $this->assertSame('north.wing@example.test', $site->email);
        $this->assertSame($companyId, (int) $site->company_id);
        $this->assertTrue($site->is_active);
    }

    public function test_store_rejects_invalid_email(): void
    {
        $user = $this->makeSuperAdmin();
        $companyId = Company::defaultId();

        $this->actingAs($user)
            ->post(route('sites.store'), [
                'company_id' => (string) $companyId,
                'name' => 'Bad Email Branch',
                'email' => 'not-an-email',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_branches_index_shows_table_headers_and_display_ids(): void
    {
        $user = $this->makeSuperAdmin();

        $site = Site::query()->where('is_default', true)->firstOrFail();
        $site->update([
            'company_id' => Company::defaultId(),
            'manager_name' => 'Alex Manager',
            'phone' => '+233241000000',
            'email' => 'branch.manager@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('sites.index'))
            ->assertOk()
            ->assertSee('Branches', false)
            ->assertSee('Branch name', false)
            ->assertSee('Manager', false)
            ->assertSee('Phone', false)
            ->assertSee('Email', false)
            ->assertSee('Status', false)
            ->assertSee($site->branchDisplayId(), false)
            ->assertSee('Alex Manager', false);
    }

    public function test_branches_search_filters_by_manager_name(): void
    {
        $user = $this->makeSuperAdmin();
        $companyId = Company::defaultId();

        $keep = Site::query()->where('is_default', true)->firstOrFail();
        $keep->update([
            'company_id' => $companyId,
            'name' => 'Alpha Branch',
            'manager_name' => 'UniqueKeeperMgr',
        ]);

        Site::query()->create([
            'company_id' => $companyId,
            'name' => 'Zeta Satellite 9f3c-exclude',
            'code' => 'BETA',
            'is_active' => true,
            'is_default' => false,
            'manager_name' => 'Other Person',
        ]);

        $this->actingAs($user);
        $response = $this->call('GET', '/sites', ['q' => 'UniqueKeeperMgr']);

        $response->assertOk()
            ->assertSee('Alpha Branch', false);

        // Each listed branch renders one "#BRN…" display id in the table.
        $this->assertSame(
            1,
            substr_count($response->getContent(), '#BRN'),
            'Branch search should list exactly one matching row when filtering by manager name.'
        );
    }

    public function test_super_admin_can_update_branch_contact_fields(): void
    {
        $user = $this->makeSuperAdmin();
        $site = Site::query()->where('is_default', true)->firstOrFail();
        $site->update(['company_id' => Company::defaultId()]);

        $this->actingAs($user)
            ->put(route('sites.update', $site), [
                'company_id' => (string) Company::defaultId(),
                'name' => $site->name,
                'code' => $site->code,
                'address' => '123 High Street',
                'manager_name' => 'Pat Supervisor',
                'phone' => '+1 555 0100',
                'email' => 'pat@branches.example',
                'is_active' => '1',
                'is_default' => '1',
            ])
            ->assertRedirect(route('sites.index'));

        $site->refresh();
        $this->assertSame('Pat Supervisor', $site->manager_name);
        $this->assertSame('+1 555 0100', $site->phone);
        $this->assertSame('pat@branches.example', $site->email);
        $this->assertSame('123 High Street', $site->address);
    }
}
