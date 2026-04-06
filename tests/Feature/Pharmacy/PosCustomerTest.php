<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Customer;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class PosCustomerTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makePosUser(): User
    {
        $this->seedPermissionsCatalog();

        $user = User::create([
            'name' => 'POS Cashier',
            'email' => uniqid('pos', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        return $this->grantPermissions($user, ['pos.access']);
    }

    public function test_pos_lookup_returns_customer_name_by_mobile(): void
    {
        $user = $this->makePosUser();
        $site = Site::query()->findOrFail($user->site_id ?? Site::defaultId());

        $mobile = '0244'.substr(str_replace('.', '', uniqid('', true)), 0, 6);

        Customer::create([
            'name' => 'Registered Customer',
            'mobile' => $mobile,
            'site_id' => $site->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('orders.customers.lookup', ['phone' => ' '.$mobile.' ']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('name', 'Registered Customer');
    }

    public function test_pos_lookup_matches_local_and_international_last_nine_digits(): void
    {
        $user = $this->makePosUser();
        $site = Site::query()->findOrFail($user->site_id ?? Site::defaultId());

        $tail = '5'.substr(preg_replace('/\D/', '', uniqid('', true)), 0, 8);
        $local = '0'.$tail;
        $international = '+2333'.$tail;

        Customer::create([
            'name' => 'Intl Stored',
            'mobile' => $international,
            'site_id' => $site->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('orders.customers.lookup', ['phone' => $local]))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('name', 'Intl Stored')
            ->assertJsonPath('mobile', $international);
    }

    public function test_pos_order_creates_customer_when_name_and_mobile_provided(): void
    {
        $user = $this->makePosUser();
        $siteId = (int) ($user->site_id ?? Site::defaultId());
        session(['current_site_id' => $siteId]);

        $product = Product::create([
            'product_name' => 'POS Cus SKU '.uniqid(),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'M'], ['name' => 'M'])->id,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 50,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $phone = '0555'.substr(preg_replace('/\D/', '', uniqid('', true)), 0, 6);
        $name = 'New Walk-in '.uniqid();

        $this->actingAs($user)->post(route('orders.store'), [
            'customerName' => $name,
            'customerMobile' => $phone,
            'paymentMethod' => 'Cash',
            'paidAmount' => 100,
            'balance' => 90,
            'product_id' => [(string) $product->id],
            'quantity' => ['1'],
            'discount' => ['0'],
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'name' => $name,
            'mobile' => $phone,
            'site_id' => $siteId,
        ]);
    }

    public function test_pos_order_updates_existing_customer_name_when_mobile_matches(): void
    {
        $user = $this->makePosUser();
        $siteId = (int) ($user->site_id ?? Site::defaultId());
        session(['current_site_id' => $siteId]);

        $phone = '0566'.substr(preg_replace('/\D/', '', uniqid('', true)), 0, 6);

        $customer = Customer::create([
            'name' => 'Old Label',
            'mobile' => $phone,
            'site_id' => $siteId,
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_name' => 'POS Upd SKU '.uniqid(),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'M2'], ['name' => 'M2'])->id,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 50,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $newName = 'Updated Name '.uniqid();

        $this->actingAs($user)->post(route('orders.store'), [
            'customerName' => $newName,
            'customerMobile' => $phone,
            'paymentMethod' => 'Cash',
            'paidAmount' => 100,
            'balance' => 90,
            'product_id' => [(string) $product->id],
            'quantity' => ['1'],
            'discount' => ['0'],
        ])->assertRedirect();

        $customer->refresh();
        $this->assertSame($newName, $customer->name);
    }
}
