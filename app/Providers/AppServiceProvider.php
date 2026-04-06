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
        if (! $this->app->environment('local')) {
            ini_set('display_errors', '0');
            ini_set('log_errors', '1');
        }

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

        View::composer('layouts.dash', function ($view) {
            $documentTitlePharmacyName = null;
            try {
                if (Schema::hasTable('sites')) {
                    $user = auth()->user();
                    if ($user && ! $user->isSuperAdmin() && $user->company_id) {
                        $user->loadMissing('company:id,company_name');
                        $documentTitlePharmacyName = $user->company?->company_name;
                    } elseif ($user && $user->isSuperAdmin()) {
                        $site = Site::query()->with('company:id,company_name')->find(CurrentSite::id());
                        $documentTitlePharmacyName = $site?->company?->company_name;
                    }
                }
            } catch (\Throwable $e) {
                $documentTitlePharmacyName = null;
            }
            $view->with('documentTitlePharmacyName', $documentTitlePharmacyName ? trim((string) $documentTitlePharmacyName) : null);
        });

        View::composer('inc.header', function ($view) {
            try {
                if (! Schema::hasTable('sites')) {
                    $view->with([
                        'sitesForSwitcher' => collect(),
                        'currentSiteId' => null,
                        'dashboardAllSites' => false,
                        'dashboardAllBranches' => false,
                        'showDashboardAllSitesOption' => false,
                        'showDashboardAllBranchesOption' => false,
                        'tenantCompanyName' => null,
                    ]);

                    return;
                }
                $user = auth()->user();
                if ($user && ! $user->isSuperAdmin()) {
                    $user->loadMissing('company:id,company_name');
                }
                $tenantCompanyName = ($user && ! $user->isSuperAdmin() && $user->company)
                    ? $user->company->company_name
                    : null;
                $branchCount = $user ? Site::forSessionSwitcher($user)->count() : 0;
                $view->with([
                    'sitesForSwitcher' => $user ? Site::forSessionSwitcher($user) : collect(),
                    'currentSiteId' => CurrentSite::id(),
                    'dashboardAllSites' => $user ? CurrentSite::dashboardAllSites() : false,
                    'dashboardAllBranches' => $user ? CurrentSite::dashboardAllBranches() : false,
                    'showDashboardAllSitesOption' => $user && $user->isSuperAdmin(),
                    'showDashboardAllBranchesOption' => $user && ! $user->isSuperAdmin() && $user->isTenantAdmin() && $branchCount > 1,
                    'tenantCompanyName' => $tenantCompanyName,
                ]);
            } catch (\Throwable $e) {
                $view->with([
                    'sitesForSwitcher' => collect(),
                    'currentSiteId' => null,
                    'dashboardAllSites' => false,
                    'dashboardAllBranches' => false,
                    'showDashboardAllSitesOption' => false,
                    'showDashboardAllBranchesOption' => false,
                    'tenantCompanyName' => null,
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
