<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class BatchManagementTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeInventoryUser(): User
    {
        $this->seedPermissionsCatalog();

        $user = User::create([
            'name' => 'Batch User',
            'email' => uniqid('batch', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        return $this->grantPermissions($user, ['inventory.view']);
    }

    public function test_batch_page_shows_receipt_line_and_stats(): void
    {
        $user = $this->makeInventoryUser();

        $m = Manufacturer::firstOrCreate(['name' => 'BMfg'], ['name' => 'BMfg']);
        $product = Product::create([
            'product_name' => 'Batch SKU Alpha',
            'description' => 'd',
            'manufacturer_id' => $m->id,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 100,
            'stock_alert' => 1,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'p.png',
        ]);

        $siteId = Site::defaultId();

        StockReceipt::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'site_id' => $siteId,
            'quantity' => 12,
            'batch_number' => 'LOT-100',
            'expiry_date' => Carbon::today()->addDays(30)->toDateString(),
            'supplier_id' => null,
            'document_reference' => 'GRN-1',
            'received_at' => Carbon::today()->toDateString(),
            'notes' => null,
        ]);

        $html = $this->actingAs($user)
            ->get(route('inventory.batches'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Batch SKU Alpha', $html);
        $this->assertStringContainsString('LOT-100', $html);
        $this->assertStringContainsString('Lines (filtered)', $html);
    }

    public function test_batch_filter_expired_only(): void
    {
        $user = $this->makeInventoryUser();
        $m = Manufacturer::firstOrCreate(['name' => 'BMfg2'], ['name' => 'BMfg2']);

        $p = Product::create([
            'product_name' => 'Exp Test Med',
            'description' => 'd',
            'manufacturer_id' => $m->id,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 50,
            'stock_alert' => 1,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'p.png',
        ]);

        $siteId = Site::defaultId();

        StockReceipt::create([
            'product_id' => $p->id,
            'user_id' => $user->id,
            'site_id' => $siteId,
            'quantity' => 1,
            'batch_number' => 'OLD',
            'expiry_date' => Carbon::today()->subDays(5)->toDateString(),
            'supplier_id' => null,
            'document_reference' => null,
            'received_at' => Carbon::today()->subMonth()->toDateString(),
            'notes' => null,
        ]);

        $this->actingAs($user)
            ->get(route('inventory.batches', ['expiry' => 'expired']))
            ->assertOk()
            ->assertSee('OLD', false)
            ->assertSee('Expired', false);

        $this->actingAs($user)
            ->get(route('inventory.batches', ['expiry' => 'ok']))
            ->assertOk()
            ->assertDontSee('OLD', false);
    }

    public function test_batch_export_streams_csv(): void
    {
        $user = $this->makeInventoryUser();

        $response = $this->actingAs($user)->get(route('inventory.batches.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Branch', $csv);
        $this->assertStringContainsString('Status', $csv);
    }
}
