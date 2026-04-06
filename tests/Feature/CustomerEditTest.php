<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
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

    public function test_edit_page_lists_matching_pos_orders_for_normalized_mobile(): void
    {
        $site = Site::query()->where('is_default', true)->firstOrFail();
        $user = User::create([
            'name' => 'Cashier',
            'email' => uniqid('saleshist', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244111006',
            'status' => '1',
            'site_id' => $site->id,
        ]);

        $customer = Customer::create([
            'name' => 'Buyer',
            'mobile' => '0244111006',
            'site_id' => $site->id,
            'is_active' => true,
        ]);

        $order = Order::create([
            'name' => 'Buyer',
            'mobile' => '0244 111 006',
            'site_id' => $site->id,
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Sales history', false)
            ->assertSee('#'.$order->id, false);
    }

    public function test_sales_history_does_not_include_other_company_orders(): void
    {
        $main = Site::query()->where('is_default', true)->firstOrFail();
        $companyB = Company::create([
            'company_name' => 'Other Pharmacy',
            'company_email' => uniqid('co', true).'@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'other-'.uniqid(),
            'is_active' => true,
        ]);
        $siteB = Site::create([
            'name' => 'Other tenant branch',
            'code' => 'OTH'.substr(uniqid(), -5),
            'address' => null,
            'is_active' => true,
            'is_default' => false,
            'company_id' => $companyB->id,
        ]);

        $user = User::create([
            'name' => 'Staff A',
            'email' => uniqid('tenantA', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244111999',
            'status' => '1',
            'site_id' => $main->id,
        ]);

        $customer = Customer::create([
            'name' => 'Same phone other org',
            'mobile' => '0244999111',
            'site_id' => $main->id,
            'is_active' => true,
        ]);

        $foreignOrder = Order::create([
            'name' => 'Walk-in',
            'mobile' => '0244999111',
            'site_id' => $siteB->id,
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('Sales history', false)
            ->assertSee('No matching sales yet', false)
            ->assertDontSee('#'.$foreignOrder->id, false);
    }

    public function test_sales_history_matches_when_order_phone_is_international_and_customer_local(): void
    {
        $site = Site::query()->where('is_default', true)->firstOrFail();
        $user = User::create([
            'name' => 'Hist intl',
            'email' => uniqid('histintl', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244111777',
            'status' => '1',
            'site_id' => $site->id,
        ]);

        $tail = '3'.substr(preg_replace('/\D/', '', uniqid('', true)), 0, 8);

        $customer = Customer::create([
            'name' => 'Local fmt',
            'mobile' => '0'.$tail,
            'site_id' => $site->id,
            'is_active' => true,
        ]);

        $order = Order::create([
            'name' => 'Local fmt',
            'mobile' => '+2333'.$tail,
            'site_id' => $site->id,
        ]);

        $this->actingAs($user)
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('#'.$order->id, false);
    }

    public function test_store_rejects_second_customer_with_same_last_nine_mobile_digits(): void
    {
        $site = Site::query()->where('is_default', true)->firstOrFail();
        $user = User::create([
            'name' => 'Directory staff',
            'email' => uniqid('dirstaff', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244111888',
            'status' => '1',
            'site_id' => $site->id,
        ]);

        $tail = '6'.substr(preg_replace('/\D/', '', uniqid('', true)), 0, 8);

        Customer::create([
            'name' => 'First',
            'mobile' => '+2333'.$tail,
            'site_id' => $site->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('customers.store'), [
                'name' => 'Duplicate tail',
                'mobile' => '0'.$tail,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('mobile');
    }
}
