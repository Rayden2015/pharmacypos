<?php

use App\Logging\ApplyRequestCorrelationTap;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'daily_env')),
            'ignore_exceptions' => false,
        ],

        /*
        | Daily log file per environment: storage/logs/2026-03-20-local.log
        | Path is resolved on each bootstrap so the file switches at calendar day.
        */
        'daily_env' => [
            'driver' => 'single',
            'path' => storage_path('logs/' . date('Y-m-d') . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '-', env('APP_ENV', 'local')) . '.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'tap' => [ApplyRequestCorrelationTap::class],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'tap' => [ApplyRequestCorrelationTap::class],
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'tap' => [ApplyRequestCorrelationTap::class],
        ],

        /*
        | Security / compliance: report access, exports, permission denials (optional).
        */
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 90,
            'tap' => [ApplyRequestCorrelationTap::class],
        ],

        /*
        | Tenant accounts payable: supplier invoices (vendor payments). INFO only;
        | create/update/delete with company_id, invoice_id, reference for support.
        */
        'vendor_payments' => [
            'driver' => 'daily',
            'path' => storage_path('logs/vendor-payments.log'),
            'level' => 'info',
            'days' => 30,
            'tap' => [ApplyRequestCorrelationTap::class],
        ],

        /*
        | Optional: add `sentry` to LOG_STACK when SENTRY_LARAVEL_DSN is set (registered by sentry/sentry-laravel).
        */
        'sentry' => [
            'driver' => 'sentry',
            'level' => env('SENTRY_LOG_LEVEL', env('LOG_LEVEL', 'error')),
            'bubble' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Request correlation (X-Request-ID)
    |--------------------------------------------------------------------------
    |
    | AssignRequestCorrelationId middleware sets a per-request id; ApplyRequestCorrelationTap
    | appends it to Monolog "extra" on tapped channels so log lines can be joined across services.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Controller action success logs (INFO: controller.action.success)
    |--------------------------------------------------------------------------
    |
    | When true, every HTTP controller action logs once after a normal return.
    | Set LOG_CONTROLLER_ACTIONS=false in production if log volume is too high.
    |
    */
    'log_controller_actions' => filter_var(env('LOG_CONTROLLER_ACTIONS', true), FILTER_VALIDATE_BOOLEAN),

];
