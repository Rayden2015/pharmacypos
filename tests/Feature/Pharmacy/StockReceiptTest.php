<?php

namespace Tests\Feature\Pharmacy;

use App\Models\InventoryMovement;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReceiptTest extends TestCase
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
            'name' => 'Receipt Admin',
            'email' => 'receipt-admin@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    public function test_receive_stock_increments_on_hand_and_creates_receipt_and_ledger_row(): void
    {
        $user = $this->makeAdmin();
        $product = Product::create([
            'product_name' => 'Receipt SKU '.uniqid('', true),
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

        $response = $this->actingAs($user)->post(route('inventory.receive.store'), [
            'product_id' => $product->id,
            'quantity' => 30,
            'batch_number' => 'BN-2026-01',
            'expiry_date' => '2028-12-01',
            'document_reference' => 'INV-9001',
            'received_at' => '2026-03-20',
            'notes' => 'Cold chain OK',
        ]);

        $receipt = StockReceipt::where('product_id', $product->id)->sole();
        $response->assertRedirect(route('inventory.receipts.show', $receipt));

        $product->refresh();
        $this->assertSame(130, (int) $product->quantity);

        $movement = InventoryMovement::where('product_id', $product->id)->where('change_type', 'receipt')->sole();
        $this->assertSame(100, $movement->quantity_before);
        $this->assertSame(30, $movement->quantity_delta);
        $this->assertSame(130, $movement->quantity_after);
        $this->assertSame($receipt->id, $movement->stock_receipt_id);
        $this->assertStringContainsString('BN-2026-01', (string) $movement->note);
    }

    public function test_receive_stock_create_page_prefills_product_from_query(): void
    {
        $user = $this->makeAdmin();
        $product = Product::create([
            'product_name' => 'Prefill '.uniqid('', true),
            'description' => 'd',
            'manufacturer_id' => Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 5,
            'stock_alert' => 2,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $this->actingAs($user)
            ->get(route('inventory.receive.create', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('value="'.$product->id.'"', false);
    }
}
