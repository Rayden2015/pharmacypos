<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

/**
 * Applies tenant-wide locale & timezone from settings (key/value table).
 */
class ApplyLocalizationSettings
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $next($request);
            }

            $tz = Setting::get('app_timezone');
            if ($tz && in_array($tz, timezone_identifiers_list(), true)) {
                config(['app.timezone' => $tz]);
                date_default_timezone_set($tz);
            }

            $locale = Setting::get('app_locale');
            if ($locale && is_string($locale) && preg_match('/^[a-z]{2}(-[A-Za-z]{2,4})?$/', $locale)) {
                App::setLocale($locale);
            }
        } catch (\Throwable $e) {
            // DB unavailable during deploy / package discovery
        }

        return $next($request);
    }
}
