<?php

namespace Tests\Feature\Integration;

use App\Models\Announcement;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

/**
 * Covers the last three feature areas together: localization settings, tenant notifications
 * (inbox + per-user notification preferences), and inventory batch management.
 */
class LocalizationNotificationsBatchSmokeTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeTenantStaff(): User
    {
        $this->seedPermissionsCatalog();

        $user = User::create([
            'name' => 'Smoke Tester',
            'email' => uniqid('smoke', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222099',
            'status' => '1',
        ]);

        return $this->grantPermissions($user, [
            'inventory.view',
            'settings.manage',
        ]);
    }

    public function test_localization_notifications_and_batch_pages_work_with_dummy_data(): void
    {
        $user = $this->makeTenantStaff();

        Setting::set('currency_symbol', '₵');
        Setting::set('currency_code', 'GHS');
        Setting::set('app_locale', 'en');
        Setting::set('app_timezone', 'Africa/Accra');
        Setting::set('date_format', 'd M Y');
        Setting::set('time_format', 'H:i');
        Setting::clearRuntimeCache();

        $this->actingAs($user)
            ->get(route('settings.localization'))
            ->assertOk()
            ->assertSee('Regional settings', false);

        $this->actingAs($user)
            ->put(route('settings.localization.update'), [
                'currency_symbol' => '₵',
                'currency_code' => 'GHS',
                'app_locale' => 'en',
                'app_timezone' => 'Africa/Accra',
                'date_format' => 'd M Y',
                'time_format' => 'H:i',
            ])
            ->assertRedirect(route('settings.localization'));

        $this->actingAs($user)
            ->get(route('settings.notifications'))
            ->assertOk()
            ->assertSee('Notification preferences', false);

        $this->actingAs($user)
            ->put(route('settings.notifications.update'), [
                'announcements_enabled' => true,
                'direct_messages_enabled' => true,
                'email_notifications_enabled' => false,
                'email_low_stock' => false,
                'email_expiry_alerts' => false,
                'email_sales_digest' => false,
            ])
            ->assertRedirect(route('settings.notifications'));

        $author = User::create([
            'name' => 'Announcer',
            'email' => uniqid('ann', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222088',
            'status' => '1',
        ]);

        $ann = Announcement::create([
            'company_id' => $user->company_id,
            'site_id' => null,
            'author_id' => $author->id,
            'title' => 'Smoke announcement',
            'body' => 'Please read this demo post.',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Smoke announcement', false);

        $this->actingAs($user)
            ->get(route('notifications.show', $ann))
            ->assertOk()
            ->assertSee('Please read this demo post.', false);

        $m = Manufacturer::firstOrCreate(['name' => 'SmokeMfg'], ['name' => 'SmokeMfg']);
        $product = Product::create([
            'product_name' => 'Smoke Batch Product',
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
            'quantity' => 20,
            'batch_number' => 'SMOKE-LOT-1',
            'expiry_date' => Carbon::today()->addDays(60)->toDateString(),
            'supplier_id' => null,
            'document_reference' => 'SMOKE-GRN-1',
            'received_at' => Carbon::today()->toDateString(),
            'notes' => null,
        ]);

        $this->actingAs($user)
            ->get(route('inventory.batches'))
            ->assertOk()
            ->assertSee('Smoke Batch Product', false)
            ->assertSee('SMOKE-LOT-1', false)
            ->assertSee('Lines (filtered)', false);
    }
}
