<?php

namespace Tests\Feature\EndToEnd;

use App\Models\Manufacturer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HTTP flows from login through inventory and settings (closest to E2E without a browser).
 */
class ApplicationFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'e2e-admin@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244111222',
            'status' => '1',
        ]);
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/')->assertOk()->assertSee('Login', false);
    }

    public function test_guest_is_redirected_from_products_index(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_user_can_login_and_is_redirected_to_home(): void
    {
        $user = $this->makeAdmin();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret',
        ]);

        $response->assertRedirect('/home');
    }

    public function test_authenticated_user_can_view_home_dashboard(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->get('/home')
            ->assertOk();
    }

    public function test_authenticated_user_can_view_products_list(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Product', false);
    }

    public function test_authenticated_user_can_view_add_product_form(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->get(url('addproduct'))
            ->assertOk()
            ->assertSee('Add New Product', false);
    }

    public function test_authenticated_user_can_create_a_product(): void
    {
        $user = $this->makeAdmin();
        $name = 'E2E Test Product '.uniqid('', true);
        $m = Manufacturer::create(['name' => 'TestBrand '.uniqid('', true)]);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'product_name' => $name,
            'description' => 'Created by feature test',
            'manufacturer_id' => $m->id,
            'price' => 100,
            'quantity' => 50,
            'supplierprice' => 75,
            'stock_alert' => 10,
            'form' => 'Tablet',
            'unit_of_measure' => 'Tablet',
            'volume' => '500 mg',
            'expiredate' => '2030-06-15',
        ]);

        $response->assertRedirect('/products');

        $this->assertDatabaseHas('products', [
            'product_name' => $name,
            'manufacturer_id' => $m->id,
            'quantity' => 50,
            'stock_alert' => 10,
            'unit_of_measure' => 'Tablet',
            'volume' => '500 mg',
        ]);
    }

    public function test_authenticated_user_can_view_and_update_settings(): void
    {
        $user = $this->makeAdmin();
        Setting::clearRuntimeCache();

        $this->actingAs($user)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Currency', false);

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'currency_symbol' => 'GH₵',
            'currency_code' => 'GHS',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        Setting::clearRuntimeCache();
        $this->assertSame('GH₵', Setting::where('key', 'currency_symbol')->value('value'));
        $this->assertSame('GHS', Setting::where('key', 'currency_code')->value('value'));
    }

    public function test_authenticated_user_can_view_orders_pos_screen(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('ORDER', false);
    }
}
