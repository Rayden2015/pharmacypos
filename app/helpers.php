<?php

use App\Support\VersionedAsset;

if (! function_exists('versioned_asset')) {
    /**
     * Public static asset URL with optional deploy cache-buster (ASSET_VERSION).
     */
    function versioned_asset(string $path): string
    {
        return VersionedAsset::url($path);
    }
}
