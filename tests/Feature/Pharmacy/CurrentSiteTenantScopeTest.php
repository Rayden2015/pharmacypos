<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Support\CurrentSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentSiteTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_user_without_session_uses_own_branch_not_platform_default(): void
    {
        $platformSite = Site::query()->where('is_default', true)->firstOrFail();

        $companyB = Company::query()->create([
            'company_name' => 'Other Pharmacy',
            'company_email' => 'other.'.uniqid().'@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'other-'.uniqid(),
            'is_active' => true,
        ]);

        $siteB = Site::query()->create([
            'company_id' => $companyB->id,
            'name' => 'Other Main',
            'code' => 'OTH-'.$companyB->id,
            'is_active' => true,
            'is_default' => true,
        ]);

        $user = User::create([
            'name' => 'B Admin',
            'email' => 'badmin.'.uniqid().'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 0,
            'is_super_admin' => false,
            'company_id' => $companyB->id,
            'site_id' => $siteB->id,
            'mobile' => '0244111222',
            'status' => '1',
        ]);

        $this->actingAs($user);

        $this->assertNotSame(
            (int) $platformSite->id,
            CurrentSite::id(),
            'Without current_site_id session, tenant staff should not inherit the platform default branch.'
        );
        $this->assertSame((int) $siteB->id, CurrentSite::id());
    }
}
