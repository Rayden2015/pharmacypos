<?php

namespace Tests\Browser\Concerns;

use Laravel\Dusk\Browser;

trait InteractsWithDuskLogin
{
    protected function duskAdminEmail(): string
    {
        return env('DUSK_LOGIN_EMAIL', 'admin@gmail.com');
    }

    protected function duskAdminPassword(): string
    {
        return env('DUSK_LOGIN_PASSWORD', 'secret');
    }

    /**
     * Log in as the seeded admin (AdminSeeder) and land on /home.
     *
     * Uses #loginForm so it matches both / and /login views.
     */
    protected function loginAsDuskAdmin(Browser $browser, string $path = '/'): void
    {
        $browser->visit($path)
            ->waitFor('#email', 15)
            ->type('#email', $this->duskAdminEmail())
            ->type('#password', $this->duskAdminPassword())
            ->press('#loginForm button[type="submit"]')
            ->waitForLocation('/home', 20)
            ->assertPathIs('/home');
    }
}
