<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_guest_cannot_view_customer_edit(): void
    {
        $customer = Customer::create([
            'name' => 'Guest Test',
            'mobile' => '0244111001',
            'site_id' => Site::defaultId(),
            'is_active' => true,
        ]);

        $this->get(route('customers.edit', $customer))->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_edit_page(): void
    {
        $user = User::create([
            'name' => 'Cust Editor',
            'email' => uniqid('cust', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244111002',
            'status' => '1',
            'site_id' => Site::defaultId(),
        ]);

        $customer = Customer::create([
            'name' => 'Editable',
            'mobile' => '0244111003',
            'site_id' => Site::defaultId(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Edit customer', false)
            ->assertSee('Editable', false);
    }

    public function test_user_cannot_edit_customer_from_other_branch(): void
    {
        $main = Site::query()->where('is_default', true)->firstOrFail();
        $other = Site::create([
            'name' => 'Remote branch',
            'code' => 'REM',
            'address' => null,
            'is_active' => true,
            'is_default' => false,
        ]);

        $user = User::create([
            'name' => 'Branch Staff',
            'email' => uniqid('branch', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244111004',
            'status' => '1',
            'site_id' => $main->id,
        ]);

        $customer = Customer::create([
            'name' => 'Remote only',
            'mobile' => '0244111005',
            'site_id' => $other->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertForbidden();
    }
}
