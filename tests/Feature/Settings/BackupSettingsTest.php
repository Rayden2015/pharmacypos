<?php

namespace Tests\Feature\Settings;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_non_admin_cannot_access_backup_settings(): void
    {
        $user = User::create([
            'name' => 'Staff',
            'email' => uniqid('s', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'tenant_role' => 'cashier',
            'mobile' => '0244000000',
            'status' => '1',
            'company_id' => Company::defaultId(),
        ]);

        $this->actingAs($user)->get(route('settings.backup'))->assertForbidden();
    }

    public function test_tenant_admin_can_generate_tenant_backups(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => uniqid('ta', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'tenant_role' => 'tenant_admin',
            'mobile' => '0244000001',
            'status' => '1',
            'company_id' => Company::defaultId(),
        ]);

        $this->actingAs($user)->get(route('settings.backup'))->assertOk();

        $this->actingAs($user)->post(route('settings.backup.system'))->assertRedirect(route('settings.backup'));
        $this->actingAs($user)->post(route('settings.backup.database'))->assertRedirect(route('settings.backup'));
    }

    public function test_super_admin_can_generate_system_manifest_but_database_may_fail_on_memory_sqlite(): void
    {
        $user = User::create([
            'name' => 'SA',
            'email' => uniqid('sa', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_super_admin' => true,
            'is_admin' => 0,
            'mobile' => '0244000002',
            'status' => '1',
        ]);

        $this->actingAs($user)->get(route('settings.backup'))->assertOk();

        $this->actingAs($user)->post(route('settings.backup.system'))->assertRedirect(route('settings.backup'));

        $this->actingAs($user)
            ->post(route('settings.backup.database'))
            ->assertRedirect(route('settings.backup'))
            ->assertSessionHas('error');
    }
}
