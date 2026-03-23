<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * POS / sales checkout is for branch staff (cashiers, supervisors, branch managers).
 * Platform super admins manage tenants and billing, not day-to-day register sales.
 */
class DenyPlatformSuperAdminFromPos
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->isSuperAdmin()) {
            return redirect()
                ->route('super-admin.dashboard')
                ->with('error', 'POS is for branch staff (cashiers, managers). Platform administration uses Tenants & billing under Super Admin.');
        }

        return $next($request);
    }
}
