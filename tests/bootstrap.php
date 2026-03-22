<?php

/**
 * PHPUnit bootstrap (runs after Composer autoload). Prefer running PHPUnit with
 * tests/phpunit-prepend.php via App\Console\Commands\TestCommand / composer test /
 * phpunit --prepend so deprecations are silenced before vendor files load.
 */
if (PHP_VERSION_ID >= 80400) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

require __DIR__.'/../vendor/autoload.php';
