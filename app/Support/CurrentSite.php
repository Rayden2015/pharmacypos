<?php

namespace App\Support;

use App\Models\Site;

class CurrentSite
{
    /**
     * Active branch for POS/inventory/dashboard context.
     *
     * Uses the session switcher when set. Otherwise, tenant users resolve to their
     * home branch via {@see Site::homeSiteIdForUser()} — not the platform default
     * site, which would show another organization's metrics after login.
     */
    public static function id(): int
    {
        $s = session('current_site_id');
        if ($s) {
            return (int) $s;
        }

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $homeId = Site::homeSiteIdForUser($user);
            if ($homeId !== null) {
                return $homeId;
            }
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
     * Tenant staff: dashboard aggregates every branch in their company (not other tenants).
     */
    public static function dashboardAllBranches(): bool
    {
        return auth()->check()
            && ! auth()->user()->isSuperAdmin()
            && session('dashboard_all_branches', false);
    }

    /**
     * When set, dashboard queries with a null site id must still be limited to this tenant's branches.
     */
    public static function dashboardTenantCompanyScopeId(): ?int
    {
        $u = auth()->user();
        if (! $u || $u->isSuperAdmin()) {
            return null;
        }

        return $u->company_id ? (int) $u->company_id : null;
    }

    /**
     * Site filter for dashboard metrics. Null = aggregate multiple sites (super "all sites",
     * or tenant "all branches"); combined with {@see dashboardTenantCompanyScopeId()} for tenants.
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

        if (self::dashboardAllBranches()) {
            return null;
        }

        return self::id();
    }
}
