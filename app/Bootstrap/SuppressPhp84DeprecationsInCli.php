<?php

namespace App\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

/**
 * HandleExceptions sets error_reporting(-1), which on PHP 8.4 surfaces hundreds of vendor
 * deprecations to stderr. For CLI we keep real errors/warnings but drop deprecations.
 */
class SuppressPhp84DeprecationsInCli
{
    public function bootstrap(Application $app)
    {
        if (PHP_SAPI === 'cli' && PHP_VERSION_ID >= 80400) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }
    }
}
