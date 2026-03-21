<?php

namespace Tests\Feature\Pharmacy;

use App\Models\InventoryMovement;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
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
            'name' => 'Inv Admin',
            'email' => 'inv-admin@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    public function test_product_store_records_initial_inventory_movement(): void
    {
        $user = $this->makeAdmin();
        $name = 'Stock Product '.uniqid('', true);
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;

        $this->actingAs($user)->post(route('products.store'), [
            'product_name' => $name,
            'manufacturer_id' => $mId,
            'description' => 'd',
            'price' => 10,
            'supplierprice' => 6,
            'quantity' => 100,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
        ])->assertRedirect('/products');

        $product = Product::where('product_name', $name)->first();
        $this->assertNotNull($product);

        $movement = InventoryMovement::where('product_id', $product->id)->sole();
        $this->assertNull($movement->quantity_before);
        $this->assertSame(100, $movement->quantity_delta);
        $this->assertSame(100, $movement->quantity_after);
        $this->assertSame('initial', $movement->change_type);
    }

    public function test_product_update_quantity_records_adjustment_with_before_and_after(): void
    {
        $user = $this->makeAdmin();
        $product = Product::create([
            'product_name' => 'Adj '.uniqid('', true),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 100,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $this->actingAs($user)->from(route('products.index'))->put(route('products.update', $product->id), [
            'product_name' => $product->product_name,
            'manufacturer_id' => $product->manufacturer_id,
            'preferred_supplier_id' => $product->preferred_supplier_id,
            'description' => $product->description,
            'price' => $product->price,
            'supplierprice' => $product->supplierprice,
            'quantity' => 130,
            'stock_alert' => $product->stock_alert,
            'form' => $product->form,
            'expiredate' => $product->expiredate,
            'inventory_note' => 'Restock +30',
        ])->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame(130, (int) $product->quantity);

        $adj = InventoryMovement::where('product_id', $product->id)->where('change_type', 'adjustment')->sole();
        $this->assertSame(100, $adj->quantity_before);
        $this->assertSame(30, $adj->quantity_delta);
        $this->assertSame(130, $adj->quantity_after);
        $this->assertSame('Restock +30', $adj->note);
    }

    public function test_inventory_history_page_is_reachable(): void
    {
        $user = $this->makeAdmin();
        $product = Product::create([
            'product_name' => 'Hist '.uniqid('', true),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 10,
            'stock_alert' => 2,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $this->actingAs($user)
            ->get(route('products.inventory-history', $product))
            ->assertOk()
            ->assertSee('Inventory history', false)
            ->assertSee(e($product->product_name), false);
    }
}
