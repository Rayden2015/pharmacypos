<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Tenant admins may define roles and attach permissions for their company.
 * Others need the tenant.roles.manage permission (assigned via a role).
 */
class EnsureTenantRoleManager
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            abort(403, 'Tenant roles are managed per organization. Use a tenant admin account, or Super Admin for platform settings.');
        }

        if (! $user->company_id) {
            abort(403, 'No company context.');
        }

        if ($user->isTenantAdmin()) {
            return $next($request);
        }

        if ($user->can('tenant.roles.manage')) {
            return $next($request);
        }

        abort(403, 'Only tenant administrators can manage roles and permissions.');
    }
}
