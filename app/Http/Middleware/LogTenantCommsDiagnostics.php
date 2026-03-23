<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs tenant communications URLs early so 404s (no route / failed binding) still leave a trace.
 */
class LogTenantCommsDiagnostics
{
    private function isTenantCommsPath(Request $request): bool
    {
        $path = ltrim($request->getPathInfo(), '/');

        return str_starts_with($path, 'messages') || str_starts_with($path, 'notifications');
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->isTenantCommsPath($request)) {
            Log::info('tenant_comms.request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'path_info' => $request->getPathInfo(),
                'full_url' => $request->fullUrl(),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->isTenantCommsPath($request)) {
            return;
        }

        Log::info('tenant_comms.response', [
            'status' => $response->getStatusCode(),
            'path' => $request->path(),
        ]);
    }
}
