<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists(__DIR__.'/../storage/framework/maintenance.php')) {
    require __DIR__.'/../storage/framework/maintenance.php';
}

/*
|--------------------------------------------------------------------------
| Early error handling (before Composer / Dotenv)
|--------------------------------------------------------------------------
|
| PHP 8.1+ deprecations in vendor code during .env parsing must never be sent
| to the browser on shared hosting (display_errors is often On in php.ini).
| After .env loads: only APP_ENV=local together with APP_DEBUG may show errors
| in the UI; everywhere else we log (log_errors) with display_errors off.
|
*/

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so that we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Load .env before the application container
|--------------------------------------------------------------------------
*/

if (is_file(dirname(__DIR__).'/.env')) {
    try {
        Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    } catch (Throwable $e) {
        // Laravel will report environment problems during bootstrap.
    }
}

$debugEnv = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
$appEnv = strtolower((string) ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production'));
$allowBrowserErrors = $appEnv === 'local' && filter_var($debugEnv, FILTER_VALIDATE_BOOLEAN);

if ($allowBrowserErrors) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy this application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = tap($kernel->handle(
    $request = Request::capture()
))->send();

$kernel->terminate($request, $response);
