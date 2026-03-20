<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $symbol = '#';
        try {
            if (Schema::hasTable('settings')) {
                $symbol = Setting::get('currency_symbol', '#') ?: '#';
            }
        } catch (\Throwable $e) {
            // Database may be unavailable during package discovery, etc.
        }

        View::share('currencySymbol', $symbol);
    }
}
