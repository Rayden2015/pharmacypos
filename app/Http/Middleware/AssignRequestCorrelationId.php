<?php

namespace App\Http\Middleware;

use App\Support\RequestCorrelation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestCorrelationId
{
    /**
     * Accept a client/proxy id when it looks sane; otherwise generate a UUID.
     */
    private function resolveId(Request $request): string
    {
        $header = $request->headers->get('X-Request-ID')
            ?? $request->headers->get('Request-ID');

        if (is_string($header)) {
            $trim = trim($header);
            if ($trim !== '' && preg_match('/^[a-zA-Z0-9\-_.]{8,128}$/', $trim)) {
                return $trim;
            }
        }

        return (string) Str::uuid();
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $id = $this->resolveId($request);
        $request->attributes->set(RequestCorrelation::ATTRIBUTE_KEY, $id);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(RequestCorrelation::REQUEST_HEADER, $id);

        return $response;
    }
}
