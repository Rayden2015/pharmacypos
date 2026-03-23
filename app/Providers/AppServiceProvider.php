<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\Site;
use App\Models\UnitOfMeasure;
use App\Support\CurrentSite;
use App\View\Composers\HeaderCommunicationsComposer;
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

        View::composer('inc.header', function ($view) {
            try {
                if (! Schema::hasTable('sites')) {
                    $view->with([
                        'sitesForSwitcher' => collect(),
                        'currentSiteId' => null,
                        'dashboardAllSites' => false,
                        'showDashboardAllSitesOption' => false,
                    ]);

                    return;
                }
                $user = auth()->user();
                $view->with([
                    'sitesForSwitcher' => $user ? Site::forSessionSwitcher($user) : collect(),
                    'currentSiteId' => CurrentSite::id(),
                    'dashboardAllSites' => $user ? CurrentSite::dashboardAllSites() : false,
                    'showDashboardAllSitesOption' => $user && $user->isSuperAdmin(),
                ]);
            } catch (\Throwable $e) {
                $view->with([
                    'sitesForSwitcher' => collect(),
                    'currentSiteId' => null,
                    'dashboardAllSites' => false,
                    'showDashboardAllSitesOption' => false,
                ]);
            }
        });

        View::composer('inc.header-notifications-messages', HeaderCommunicationsComposer::class);

        View::composer('products.partials.unit-of-measure-select', function ($view) {
            try {
                if (! Schema::hasTable('unit_of_measures')) {
                    $view->with('unitsCatalog', collect());

                    return;
                }
                $view->with(
                    'unitsCatalog',
                    UnitOfMeasure::query()->active()->ordered()->get()
                );
            } catch (\Throwable $e) {
                $view->with('unitsCatalog', collect());
            }
        });
    }
}
