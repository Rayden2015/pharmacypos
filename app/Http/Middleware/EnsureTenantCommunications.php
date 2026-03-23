<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsureTenantCommunications
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! $user->canUseTenantCommunications()) {
            Log::notice('tenant_comms.forbidden', [
                'path' => $request->path(),
                'reason' => ! $user ? 'guest' : 'not_tenant_account',
                'user_id' => $user?->id,
                'company_id' => $user?->company_id,
                'is_super_admin' => $user?->is_super_admin,
            ]);
            abort(403, 'Messaging is only available for organization accounts.');
        }

        Log::debug('tenant_comms.middleware_ok', [
            'path' => $request->path(),
            'user_id' => $user->id,
            'company_id' => $user->company_id,
        ]);

        return $next($request);
    }
}
