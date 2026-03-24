<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenantAdminOrSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || (! $user->isSuperAdmin() && ! $user->isTenantAdmin())) {
            abort(403, 'Tenant admin or platform super admin access only.');
        }

        return $next($request);
    }
}
