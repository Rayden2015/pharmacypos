<?php

namespace App\Http\Middleware;

use App\Support\Audit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMutatingRequests
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('audit.log_http_requests')) {
            return;
        }

        if (! auth()->check()) {
            return;
        }

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if ($this->shouldSkipHttpAudit($request)) {
            return;
        }

        $route = $request->route();
        $name = $route ? $route->getName() : null;
        if ($name && in_array($name, config('audit.http_exclude_route_names', []), true)) {
            return;
        }

        Audit::recordHttpRequest(
            $response->getStatusCode(),
            Audit::sanitizeRequestInput($request->all())
        );
    }

    /**
     * Avoid duplicating auth/password flows (listeners + model audits handle those).
     */
    private function shouldSkipHttpAudit(Request $request): bool
    {
        if ($request->is('login') && $request->isMethod('POST')) {
            return true;
        }
        if ($request->is('register') && $request->isMethod('POST')) {
            return true;
        }
        if ($request->is('password/*')) {
            return true;
        }

        return false;
    }
}
