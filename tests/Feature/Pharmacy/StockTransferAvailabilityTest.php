<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Company;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_returns_quantity_at_source_branch(): void
    {
        $companyId = Company::defaultId();
        $siteA = Site::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('id')->firstOrFail();

        $siteB = Site::query()->where('company_id', $companyId)->where('id', '!=', $siteA->id)->first();
        if (! $siteB) {
            $siteB = Site::query()->create([
                'company_id' => $companyId,
                'name' => 'Branch B',
                'code' => 'B-'.uniqid(),
                'is_active' => true,
                'is_default' => false,
            ]);
        }

        $user = User::create([
            'name' => 'Transfer viewer',
            'email' => uniqid('st', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'company_id' => $companyId,
            'site_id' => $siteA->id,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        $mId = Manufacturer::firstOrCreate(['name' => 'MfgST'], ['name' => 'MfgST'])->id;
        $product = Product::query()->create([
            'product_name' => 'StockXfer '.uniqid(),
            'description' => 'd',
            'manufacturer_id' => $mId,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 0,
            'stock_alert' => 1,
            'form' => 'Tablet',
            'expiredate' => '2030-12-01',
            'product_img' => 'product.png',
            'company_id' => $companyId,
        ]);

        ProductSiteStock::query()->updateOrCreate(
            ['product_id' => $product->id, 'site_id' => $siteA->id],
            ['quantity' => 37]
        );
        ProductSiteStock::query()->updateOrCreate(
            ['product_id' => $product->id, 'site_id' => $siteB->id],
            ['quantity' => 5]
        );
        Product::syncQuantityFromSiteStocks($product->id);

        $this->actingAs($user)
            ->getJson(route('inventory.stock-transfer.availability', [
                'from_site_id' => $siteA->id,
                'product_id' => $product->id,
            ]))
            ->assertOk()
            ->assertJson([
                'available' => 37,
                'product_name' => $product->product_name,
            ]);

        $this->actingAs($user)
            ->getJson(route('inventory.stock-transfer.availability', [
                'from_site_id' => $siteB->id,
                'product_id' => $product->id,
            ]))
            ->assertOk()
            ->assertJson(['available' => 5]);
    }

    public function test_returns_zero_when_no_product_site_stock_row(): void
    {
        $companyId = Company::defaultId();
        $site = Site::query()->where('company_id', $companyId)->firstOrFail();

        $user = User::create([
            'name' => 'Xfer 2',
            'email' => uniqid('st2', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'company_id' => $companyId,
            'site_id' => $site->id,
            'mobile' => '0244222001',
            'status' => '1',
        ]);

        $mId = Manufacturer::firstOrCreate(['name' => 'MfgST2'], ['name' => 'MfgST2'])->id;
        $product = Product::query()->create([
            'product_name' => 'No PSS '.uniqid(),
            'description' => 'd',
            'manufacturer_id' => $mId,
            'price' => 1,
            'supplierprice' => 1,
            'quantity' => 0,
            'stock_alert' => 1,
            'form' => 'Tablet',
            'expiredate' => '2030-12-01',
            'product_img' => 'product.png',
            'company_id' => $companyId,
        ]);

        $this->actingAs($user)
            ->getJson(route('inventory.stock-transfer.availability', [
                'from_site_id' => $site->id,
                'product_id' => $product->id,
            ]))
            ->assertOk()
            ->assertJson(['available' => 0]);
    }
}
