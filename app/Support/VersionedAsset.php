<?php

namespace App\Support;

/**
 * Append a deploy-specific query string so browsers fetch new copies of static files after releases.
 * User uploads under storage/ stay on plain {@see asset()} so avatars are not tied to deploy ids.
 */
class VersionedAsset
{
    public static function url(string $path): string
    {
        $normalized = ltrim($path, '/');
        if (strpos($normalized, 'storage/') === 0) {
            return asset($path);
        }

        $version = config('app.asset_version');
        if ($version === null || $version === '') {
            return asset($path);
        }

        $base = asset($path);
        $separator = strpos($base, '?') !== false ? '&' : '?';

        return $base.$separator.'v='.rawurlencode((string) $version);
    }
}
