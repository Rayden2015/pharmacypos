<?php

namespace Tests\Feature\Pharmacy;

use App\Models\AuditLog;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
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
            'name' => 'Audit Admin',
            'email' => 'audit-admin@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    public function test_product_update_records_model_audit_with_before_and_after(): void
    {
        $user = $this->makeAdmin();
        $product = Product::create([
            'product_name' => 'Audit SKU '.uniqid('', true),
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

        $pss = ProductSiteStock::query()
            ->where('product_id', $product->id)
            ->where('site_id', Site::defaultId())
            ->firstOrFail();

        $this->actingAs($user)->from(route('products.index'))->put(route('products.update', $product->id), [
            'product_name' => $product->product_name,
            'manufacturer_id' => $product->manufacturer_id,
            'preferred_supplier_id' => $product->preferred_supplier_id,
            'description' => $product->description,
            'price' => $product->price,
            'supplierprice' => $product->supplierprice,
            'quantity' => 125,
            'stock_alert' => $product->stock_alert,
            'form' => $product->form,
            'expiredate' => $product->expiredate,
            'inventory_note' => 'adjust',
        ])->assertRedirect(route('products.index'));

        // Product total is synced via query builder (no Product@updated); branch stock is Eloquent-updated.
        $log = AuditLog::query()
            ->where('action', ProductSiteStock::class.'@updated')
            ->where('subject_id', $pss->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, (int) $log->user_id);
        $this->assertIsArray($log->old_values);
        $this->assertIsArray($log->new_values);
        $this->assertArrayHasKey('quantity', $log->old_values);
        $this->assertArrayHasKey('quantity', $log->new_values);
        $this->assertSame(100, (int) $log->old_values['quantity']);
        $this->assertSame(125, (int) $log->new_values['quantity']);
    }

    public function test_site_switch_records_explicit_audit(): void
    {
        $user = User::create([
            'name' => 'Audit Admin',
            'email' => 'audit-admin-switch@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
            'tenant_role' => 'tenant_admin',
        ]);
        $main = Site::query()->where('is_default', true)->firstOrFail();
        $second = Site::create([
            'name' => 'Warehouse',
            'code' => 'WH',
            'address' => null,
            'is_active' => true,
            'is_default' => false,
        ]);

        session(['current_site_id' => $main->id]);

        $this->actingAs($user)->post(route('sites.switch'), [
            'site_id' => $second->id,
        ])->assertRedirect();

        $log = AuditLog::query()->where('action', 'site.switch')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame((int) $main->id, $log->old_values['current_site_id']);
        $this->assertSame((int) $second->id, $log->new_values['current_site_id']);
    }
}
