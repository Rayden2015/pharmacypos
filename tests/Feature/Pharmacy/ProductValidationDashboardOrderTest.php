<?php

namespace Tests\Feature\Pharmacy;

use App\Models\InventoryMovement;
use App\Models\Manufacturer;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductValidationDashboardOrderTest extends TestCase
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
            'email' => 'validation-admin@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    public function test_dashboard_route_loads_overview_metrics(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Today\'s sales', false)
            ->assertSee('Quick actions', false);
    }

    public function test_product_store_requires_manufacturer_and_expire_date(): void
    {
        $user = $this->makeAdmin();
        $name = 'Invalid Product '.uniqid('', true);
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;

        $response = $this->actingAs($user)->post(route('products.store'), [
            'product_name' => $name,
            // manufacturer_id missing
            'description' => 'x',
            'price' => 10,
            'quantity' => 1,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
        ]);

        $response->assertSessionHasErrors(['manufacturer_id']);

        $response2 = $this->actingAs($user)->from(url('addproduct'))->post(route('products.store'), [
            'product_name' => $name.'-2',
            'manufacturer_id' => $mId,
            'price' => 10,
            'quantity' => 1,
            'stock_alert' => 5,
            'form' => 'Tablet',
            // expiredate missing
        ]);

        $response2->assertSessionHasErrors(['expiredate']);
    }

    public function test_product_store_rejects_non_image_upload(): void
    {
        $user = $this->makeAdmin();
        $name = 'Bad Image Product '.uniqid('', true);
        $file = UploadedFile::fake()->create('receipt.pdf', 120);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'product_name' => $name,
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id,
            'description' => 'd',
            'price' => 10,
            'quantity' => 1,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => $file,
        ]);

        $response->assertSessionHasErrors('product_img');
    }

    public function test_product_store_rejects_oversized_image(): void
    {
        $user = $this->makeAdmin();
        $name = 'Huge Image Product '.uniqid('', true);
        // max:5120 = kilobytes (~5 MB cap in validation rule)
        $file = UploadedFile::fake()->image('too-big.jpg')->size(6000);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'product_name' => $name,
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id,
            'description' => 'd',
            'price' => 10,
            'quantity' => 1,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => $file,
        ]);

        $response->assertSessionHasErrors('product_img');
        $this->assertDatabaseMissing('products', ['product_name' => $name]);
    }

    public function test_pos_order_persist_packaging_snapshot_on_order_detail(): void
    {
        $user = $this->makeAdmin();

        $product = Product::create([
            'product_name' => 'Snapshot SKU '.uniqid('', true),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'Lab'], ['name' => 'Lab'])->id,
            'price' => 25,
            'supplierprice' => 15,
            'quantity' => 200,
            'stock_alert' => 20,
            'form' => 'Tablet',
            'unit_of_measure' => 'Pack',
            'volume' => '14 tablets',
            'expiredate' => '2031-12-01',
            'product_img' => 'product.png',
        ]);

        $this->actingAs($user)->post(route('orders.store'), [
            'customerName' => 'Walk-in Customer',
            'customerMobile' => '0244999000',
            'paymentMethod' => 'Cash',
            'paidAmount' => 50,
            'balance' => 0,
            'product_id' => [(string) $product->id],
            'quantity' => ['2'],
            'discount' => ['0'],
        ]);

        $detail = Order_detail::where('product_id', $product->id)->latest('id')->first();
        $this->assertNotNull($detail);
        $this->assertSame('Pack', $detail->unit_of_measure);
        $this->assertSame('14 tablets', $detail->volume);

        $product->refresh();
        $this->assertSame(198, (int) $product->quantity);

        $sale = InventoryMovement::where('product_id', $product->id)->where('change_type', 'sale')->sole();
        $this->assertSame(-2, $sale->quantity_delta);
        $this->assertSame(198, $sale->quantity_after);
    }
}
