<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Site;
use App\Models\User;
use App\Support\CurrentSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class SaleReturnTest extends TestCase
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
            'name' => 'POS Refund User',
            'email' => 'pos-refund-'.uniqid('', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
            'company_id' => Company::defaultId(),
        ]);

        return $this->grantPermissions($user, [
            'pos.access',
            'pos.refund',
            'products.view',
        ]);
    }

    public function test_sales_return_restores_stock_and_writes_movement(): void
    {
        $user = $this->makePosUser();
        $siteId = Site::defaultId();
        Site::query()->whereKey($siteId)->update(['company_id' => Company::defaultId()]);

        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $product = Product::create([
            'company_id' => Company::defaultId(),
            'product_name' => 'Returnable '.uniqid('', true),
            'description' => 'd',
            'manufacturer_id' => $mId,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 100,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $this->actingAs($user);
        session(['current_site_id' => $siteId]);
        $this->assertSame($siteId, CurrentSite::id());

        $this->post(route('orders.store'), [
            'customerName' => 'Walk-in Customer',
            'customerMobile' => '0244999111',
            'paymentMethod' => 'Cash',
            'paidAmount' => 25,
            'balance' => 0,
            'product_id' => [(string) $product->id],
            'quantity' => ['3'],
            'discount' => ['0'],
        ]);

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame($siteId, (int) $order->site_id);

        $product->refresh();
        $this->assertSame(97, (int) $product->quantity);

        $detail = $order->orderdetail()->first();
        $this->assertNotNull($detail);

        $this->post(route('sales.returns.store', $order), [
            'note' => 'Customer brought unused pack',
            'lines' => [
                [
                    'order_detail_id' => $detail->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertRedirect(route('sales.returns.index'));

        $product->refresh();
        $this->assertSame(99, (int) $product->quantity);

        $ret = InventoryMovement::query()
            ->where('product_id', $product->id)
            ->where('change_type', 'sale_return')
            ->sole();
        $this->assertSame(2, $ret->quantity_delta);
        $this->assertStringContainsString('Sales return #', (string) $ret->note);
        $this->assertStringContainsString('Original POS order #'.$order->id, (string) $ret->note);
        $this->assertMatchesRegularExpression('/^#RTN\d+$/', $ret->referenceDisplay());
    }

    public function test_cannot_return_more_than_line_remaining(): void
    {
        $user = $this->makePosUser();
        $siteId = Site::defaultId();
        Site::query()->whereKey($siteId)->update(['company_id' => Company::defaultId()]);

        $mId = Manufacturer::firstOrCreate(['name' => 'Co'], ['name' => 'Co'])->id;
        $product = Product::create([
            'company_id' => Company::defaultId(),
            'product_name' => 'Cap '.uniqid('', true),
            'description' => 'd',
            'manufacturer_id' => $mId,
            'price' => 10,
            'supplierprice' => 5,
            'quantity' => 50,
            'stock_alert' => 5,
            'form' => 'Tablet',
            'expiredate' => '2030-01-01',
            'product_img' => 'product.png',
        ]);

        $this->actingAs($user);
        session(['current_site_id' => $siteId]);

        $this->post(route('orders.store'), [
            'customerName' => 'Walk-in',
            'customerMobile' => '0244999222',
            'paymentMethod' => 'Cash',
            'paidAmount' => 20,
            'balance' => 0,
            'product_id' => [(string) $product->id],
            'quantity' => ['2'],
            'discount' => ['0'],
        ]);

        $order = Order::query()->latest('id')->firstOrFail();
        $detail = $order->orderdetail()->firstOrFail();

        $this->post(route('sales.returns.store', $order), [
            'lines' => [
                ['order_detail_id' => $detail->id, 'quantity' => 2],
            ],
        ])->assertSessionHasNoErrors();

        $this->post(route('sales.returns.store', $order), [
            'lines' => [
                ['order_detail_id' => $detail->id, 'quantity' => 1],
            ],
        ])->assertSessionHasErrors();

        $this->assertSame(1, $order->saleReturns()->count());
    }

    public function test_inventory_logs_filter_return_type(): void
    {
        $user = $this->makePosUser();
        Site::query()->whereKey(Site::defaultId())->update(['company_id' => Company::defaultId()]);

        $this->actingAs($user)->get(route('inventory.logs', ['type' => 'return']))->assertOk();
    }
}
