<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Prevents CDNs and browsers from serving stale Blade HTML that still references old ?v= asset URLs.
 */
class DisableCachingForHtmlResponses
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && strpos($contentType, 'text/html') === 0) {
            $response->headers->set('Cache-Control', 'private, no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
