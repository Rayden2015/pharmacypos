<?php

namespace Tests\Browser;

use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Site;
use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\InteractsWithDuskLogin;
use Tests\DuskTestCase;

/**
 * Browser tests for Create product (/addproduct).
 *
 * Requires:
 * - App server: `php artisan serve` and APP_URL including that port (e.g. http://127.0.0.1:8000).
 * - ChromeDriver matching Chrome: `bash scripts/dusk-chromedriver.sh` (legacy `dusk:chrome-driver` fails on Chrome 115+).
 * - `.env.dusk.local` must include APP_KEY and DB settings (see project file; `php artisan dusk` swaps `.env` to this file).
 * - DB: at least one manufacturer and active site (e.g. migrations + seeders).
 * - Login: DUSK_LOGIN_EMAIL / DUSK_LOGIN_PASSWORD in `.env.dusk.local` (defaults: admin@gmail.com / secret from AdminSeeder).
 *
 * Run: `php artisan dusk`
 */
class CreateProductBrowserTest extends DuskTestCase
{
    use InteractsWithDuskLogin;

    /**
     * PHPUnit only reliably picks up @beforeClass on the concrete test class (not always on abstract parents).
     *
     * @beforeClass
     */
    public static function prepare()
    {
        parent::prepare();
    }

    public function test_guest_visiting_add_product_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/addproduct')
                ->waitForLocation('/login', 5)
                ->assertPathIs('/login');
        });
    }

    public function test_login_page_loads(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->assertSee('Login', false)
                ->assertPresent('#email');
        });
    }

    public function test_authenticated_user_can_create_product_via_form(): void
    {
        $manufacturerId = Manufacturer::query()->orderBy('id')->value('id');
        if (! $manufacturerId) {
            $this->markTestSkipped('No manufacturer row; run DB seeders so the create-product form has a manufacturer.');
        }

        if (! Site::query()->where('is_active', true)->exists()) {
            $this->markTestSkipped('No active site; run migrations/seeders.');
        }

        $productName = 'Dusk Medicine '.uniqid();

        $this->browse(function (Browser $browser) use ($manufacturerId, $productName) {
            $this->loginAsDuskAdmin($browser, '/login');

            $browser->visit('/addproduct')
                ->waitForText('Create product', 5)
                ->type('product_name', $productName)
                ->select('manufacturer_id', (string) $manufacturerId);

            $browser->driver->executeScript(
                'document.querySelector(\'button[data-bs-target="#collapsePrice"]\').scrollIntoView({block: "center"});'
            );
            $browser->driver->executeScript(
                'document.querySelector(\'button[data-bs-target="#collapsePrice"]\').click();'
            );

            $browser->waitFor('#collapsePrice.show', 10)
                ->type('price', '9.99')
                ->type('supplierprice', '0')
                ->type('quantity', '3')
                ->type('stock_alert', '1')
                ->select('form', 'Tablet');

            $browser->driver->executeScript(
                'document.querySelector(\'button[data-bs-target="#collapseCustom"]\').scrollIntoView({block: "center"});'
            );
            $browser->driver->executeScript(
                'document.querySelector(\'button[data-bs-target="#collapseCustom"]\').click();'
            );

            $browser->waitFor('#collapseCustom.show', 10)
                ->type('expiredate', '2031-06-15');

            $browser->press('Add product')
                ->waitForLocation('/products', 20)
                ->assertPathIs('/products')
                ->assertSee('Product Added Successfully', false);
        });

        $this->assertTrue(
            Product::query()->where('product_name', $productName)->exists(),
            'Product should be stored after submitting the create-product form.'
        );
    }
}
