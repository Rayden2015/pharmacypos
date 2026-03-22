<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Tests the Dreams-style "Create product" form parity with {@see \App\Http\Controllers\ProductController::store}.
 */
class CreateProductFormTest extends TestCase
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
            'name' => 'Form Tester',
            'email' => uniqid('cpt', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalPayload(string $productName, int $manufacturerId): array
    {
        return [
            'site_id' => Site::defaultId(),
            'product_name' => $productName,
            'selling_type' => 'retail',
            'discount_type' => 'none',
            'product_type' => 'single',
            'feature_expiry' => '1',
            'feature_warranty' => '0',
            'manufacturer_id' => $manufacturerId,
            'price' => 10,
            'supplierprice' => 0,
            'quantity' => 20,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-06-01',
        ];
    }

    public function test_add_product_page_loads_for_authenticated_user(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(url('addproduct'))
            ->assertOk()
            ->assertSee('Create product', false)
            ->assertSee('Product information', false)
            ->assertSee('Pricing', false)
            ->assertSee('stocks', false)
            ->assertSee('Custom fields', false);
    }

    public function test_create_product_with_full_dream_style_fields_persists_and_stocks_site(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Dream Mfg'], ['name' => 'Dream Mfg'])->id;
        $supplier = Supplier::firstOrCreate(
            ['supplier_name' => 'Wholesale Co'],
            [
                'supplier_name' => 'Wholesale Co',
                'address' => 'Test address',
                'mobile' => '0244000001',
                'email' => 'wholesale@example.test',
            ]
        );
        $siteId = Site::defaultId();
        $name = 'Full Dream Product '.uniqid('', true);

        $payload = array_merge($this->minimalPayload($name, $mId), [
            'slug' => 'custom-slug-test',
            'sku' => 'SKU-TEST-'.uniqid(),
            'item_code' => 'IC-12345',
            'category' => 'General OTC',
            'sub_category' => 'Pain relief',
            'barcode_symbology' => 'EAN-13',
            'tax_type' => 'standard',
            'description' => 'Short desc for listings.',
            'alias' => 'Alias search',
            'supplierprice' => 4.5,
            'preferred_supplier_id' => $supplier->id,
            'unit_of_measure' => 'Tablet',
            'volume' => '30 tablets',
            'warehouse_note' => 'Cold room A',
            'manufactured_date' => '2025-01-15',
        ]);

        $response = $this->actingAs($user)->post(route('products.store'), $payload);

        $response->assertRedirect('/products');

        $product = Product::query()->where('product_name', $name)->first();
        $this->assertNotNull($product);
        $this->assertSame('custom-slug-test', $product->slug);
        $this->assertSame($payload['sku'], $product->sku);
        $this->assertSame('IC-12345', $product->item_code);
        $this->assertSame('General OTC', $product->category);
        $this->assertSame('Pain relief', $product->sub_category);
        $this->assertSame('EAN-13', $product->barcode_symbology);
        $this->assertSame('standard', $product->tax_type);
        $this->assertSame('Cold room A', $product->warehouse_note);
        $this->assertSame('2025-01-15', $product->manufactured_date instanceof \Carbon\CarbonInterface
            ? $product->manufactured_date->toDateString()
            : (string) $product->manufactured_date);

        $pss = ProductSiteStock::query()
            ->where('product_id', $product->id)
            ->where('site_id', $siteId)
            ->first();
        $this->assertNotNull($pss);
        $this->assertSame(20, (int) $pss->quantity);
    }

    public function test_expiry_disabled_sets_far_future_expiredate(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $name = 'No Expiry Track '.uniqid('', true);

        $payload = array_merge($this->minimalPayload($name, $mId), [
            'feature_expiry' => '0',
            'expiredate' => '',
        ]);

        $this->actingAs($user)->post(route('products.store'), $payload)->assertRedirect('/products');

        $product = Product::query()->where('product_name', $name)->first();
        $this->assertNotNull($product);
        $this->assertSame('2099-12-31', $product->expiredate);
    }

    public function test_discount_percent_stores_value(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $name = 'Discounted '.uniqid('', true);

        $payload = array_merge($this->minimalPayload($name, $mId), [
            'discount_type' => 'percent',
            'discount_value' => 12.5,
        ]);

        $this->actingAs($user)->post(route('products.store'), $payload)->assertRedirect('/products');

        $product = Product::query()->where('product_name', $name)->first();
        $this->assertNotNull($product);
        $this->assertSame('percent', $product->discount_type);
        $this->assertEquals(12.5, (float) $product->discount_value);
    }

    public function test_description_over_sixty_words_fails_validation(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $name = 'Long Desc '.uniqid('', true);
        $words = implode(' ', array_fill(0, 61, 'word'));

        $payload = array_merge($this->minimalPayload($name, $mId), [
            'description' => $words,
        ]);

        $this->actingAs($user)
            ->from(url('addproduct'))
            ->post(route('products.store'), $payload)
            ->assertSessionHasErrors(['description']);

        $this->assertDatabaseMissing('products', ['product_name' => $name]);
    }

    public function test_variable_product_type_saves_and_redirects_with_notice(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $name = 'Variable '.uniqid('', true);

        $payload = array_merge($this->minimalPayload($name, $mId), [
            'product_type' => 'variable',
        ]);

        $response = $this->actingAs($user)->post(route('products.store'), $payload);

        $response->assertRedirect('/products');
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Variable product', (string) session('success'));

        $product = Product::query()->where('product_name', $name)->first();
        $this->assertNotNull($product);
        $this->assertSame('variable', $product->product_type);
    }

    public function test_warranty_fields_saved_when_toggle_on(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $name = 'Warranty '.uniqid('', true);

        $payload = array_merge($this->minimalPayload($name, $mId), [
            'feature_warranty' => '1',
            'warranty_term' => '1_year',
        ]);

        $this->actingAs($user)->post(route('products.store'), $payload)->assertRedirect('/products');

        $product = Product::query()->where('product_name', $name)->first();
        $this->assertNotNull($product);
        $this->assertSame('1_year', $product->warranty_term);
    }

    public function test_valid_image_upload_succeeds(): void
    {
        $user = $this->makeUser();
        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $name = 'With Image '.uniqid('', true);
        $file = UploadedFile::fake()->image('pill.jpg', 80, 80);

        $payload = array_merge($this->minimalPayload($name, $mId), [
            'product_img' => $file,
        ]);

        $this->actingAs($user)->post(route('products.store'), $payload)->assertRedirect('/products');

        $product = Product::query()->where('product_name', $name)->first();
        $this->assertNotNull($product);
        $this->assertNotSame('product.png', $product->product_img);
    }

}
