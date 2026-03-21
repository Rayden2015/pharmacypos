<?php

namespace App\Support;

use App\Models\Site;

class CurrentSite
{
    public static function id(): int
    {
        $s = session('current_site_id');
        if ($s) {
            return (int) $s;
        }

        return Site::defaultId();
    }

    /**
     * Super admin: dashboard shows metrics across all sites when session flag is set.
     */
    public static function dashboardAllSites(): bool
    {
        return auth()->check()
            && auth()->user()->isSuperAdmin()
            && session('dashboard_all_sites', false);
    }

    /**
     * Site filter for dashboard metrics. Null = aggregate all sites (super + "All sites" mode).
     * When no user (e.g. tests calling dashboard data), defaults to default site id.
     *
     * @return int|null
     */
    public static function dashboardSiteId(): ?int
    {
        if (! auth()->check()) {
            return Site::defaultId();
        }

        if (self::dashboardAllSites()) {
            return null;
        }

        return self::id();
    }
}
