<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie "teams" use company_id — set before permission/role resolution for tenant users.
 */
class SetPermissionTeamFromAuth
{
    public function handle(Request $request, Closure $next)
    {
        $registrar = app(PermissionRegistrar::class);

        if (! $request->user()) {
            $registrar->setPermissionsTeamId(null);

            return $next($request);
        }

        if ($request->user()->isSuperAdmin()) {
            $registrar->setPermissionsTeamId(null);

            return $next($request);
        }

        $companyId = $request->user()->company_id;
        $registrar->setPermissionsTeamId($companyId);

        return $next($request);
    }
}
