<?php

namespace Tests\Feature\Pharmacy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossSiteDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(bool $super = false): User
    {
        return User::create([
            'name' => 'Test',
            'email' => uniqid('cs', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => $super,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    public function test_guest_is_redirected_from_cross_site_dashboard(): void
    {
        $this->get(route('dashboard.cross-site'))->assertRedirect(route('login'));
    }

    public function test_non_super_admin_cannot_view_cross_site_dashboard(): void
    {
        $this->actingAs($this->makeUser(false))
            ->get(route('dashboard.cross-site'))
            ->assertForbidden();
    }

    public function test_super_admin_can_view_cross_site_dashboard(): void
    {
        $this->actingAs($this->makeUser(true))
            ->get(route('dashboard.cross-site'))
            ->assertOk()
            ->assertSee('Cross-site dashboard', false)
            ->assertSee('Performance by branch', false);
    }
}
