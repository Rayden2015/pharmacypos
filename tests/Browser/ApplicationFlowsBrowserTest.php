<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\Browser\Concerns\InteractsWithDuskLogin;
use Tests\DuskTestCase;

/**
 * End-to-end UI flows (Dusk) for core navigation after login.
 *
 * Prerequisites match {@see CreateProductBrowserTest}: `php artisan serve`,
 * ChromeDriver via `bash scripts/dusk-chromedriver.sh`, `.env.dusk.local` with
 * APP_KEY + DB (seeded admin), optional DUSK_LOGIN_EMAIL / DUSK_LOGIN_PASSWORD.
 *
 * Run: `php artisan dusk --filter ApplicationFlowsBrowserTest`
 */
class ApplicationFlowsBrowserTest extends DuskTestCase
{
    use InteractsWithDuskLogin;

    /**
     * @beforeClass
     */
    public static function prepare()
    {
        parent::prepare();
    }

    public function test_guest_sees_login_form_on_root(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#email', 15)
                ->assertPresent('#loginForm')
                ->assertSee('Login', false);
        });
    }

    public function test_seeded_admin_sees_dashboard_after_login(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDuskAdmin($browser, '/');
            $browser->assertSee('Welcome', false);
        });
    }

    public function test_seeded_admin_can_open_sales_report(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDuskAdmin($browser);
            $browser->visit('/reports/sales')
                ->waitForText('Total sales amount', 15)
                ->assertPathIs('/reports/sales')
                ->assertSee('Apply', false)
                ->assertSee('Export CSV', false);
        });
    }

    public function test_sales_report_apply_keeps_user_on_report(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDuskAdmin($browser);
            $browser->visit('/reports/sales')
                ->waitForText('Total sales amount', 15)
                ->press('Apply')
                ->pause(500)
                ->assertPathIs('/reports/sales')
                ->assertSee('Total sales amount', false);
        });
    }

    public function test_seeded_admin_can_open_backup_settings(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAsDuskAdmin($browser);
            $browser->visit('/settings/backup')
                ->waitForText('Backup jobs', 15)
                ->assertPathIs('/settings/backup')
                ->assertSee('System backup', false)
                ->assertSee('Database backup', false);
        });
    }
}
