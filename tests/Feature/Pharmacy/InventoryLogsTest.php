<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Logs User',
            'email' => 'logs-user@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
            'company_id' => Company::defaultId(),
        ]);
    }

    public function test_inventory_logs_page_lists_movements(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $siteId = Site::defaultId();

        $product = Product::create([
            'company_id' => Company::defaultId(),
            'product_name' => 'Ledger item '.uniqid('', true),
            'sku' => 'SKU-LOG-'.uniqid(),
            'description' => 'd',
            'manufacturer_id' => $mId,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 50,
            'stock_alert' => 2,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        InventoryMovement::create([
            'product_id' => $product->id,
            'site_id' => $siteId,
            'user_id' => $user->id,
            'quantity_before' => 40,
            'quantity_delta' => 10,
            'quantity_after' => 50,
            'change_type' => 'adjustment',
            'note' => 'Test adjustment',
        ]);

        $this->actingAs($user)
            ->get(route('inventory.logs'))
            ->assertOk()
            ->assertSee($product->product_name, false)
            ->assertSee($product->sku, false)
            ->assertSee('Adjustment', false)
            ->assertSee('#ADJ', false);
    }

    public function test_inventory_logs_export_streams_csv(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $siteId = Site::defaultId();

        $product = Product::create([
            'company_id' => Company::defaultId(),
            'product_name' => 'Export item '.uniqid('', true),
            'sku' => 'SKU-EXP-'.uniqid(),
            'description' => 'd',
            'manufacturer_id' => $mId,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 5,
            'stock_alert' => 2,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        InventoryMovement::create([
            'product_id' => $product->id,
            'site_id' => $siteId,
            'user_id' => $user->id,
            'quantity_before' => 0,
            'quantity_delta' => 5,
            'quantity_after' => 5,
            'change_type' => 'initial',
            'note' => null,
        ]);

        $response = $this->actingAs($user)->get(route('inventory.logs.export'));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString($product->product_name, $response->streamedContent());
        $this->assertStringContainsString('Opening balance', $response->streamedContent());
    }
}
