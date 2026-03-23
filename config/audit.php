<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Log HTTP mutating requests (POST/PUT/PATCH/DELETE)
    |--------------------------------------------------------------------------
    |
    | When true, each authenticated mutating request records a row with the
    | sanitized request payload and response status. Model-level audits (see
    | Auditable trait) capture old/new field values; HTTP logs add route-level
    | coverage for actions that are not Eloquent-only. Set false to rely on
    | model + manual Audit::record() only.
    |
    */
    'log_http_requests' => env('AUDIT_LOG_HTTP', false),

    /*
    | Route names to skip for HTTP audit (e.g. noisy polling endpoints).
    */
    'http_exclude_route_names' => [
        'login',
        'logout',
        'register',
        'password.email',
        'password.update',
        'password.reset',
        'password.confirm',
        'sites.switch',
    ],

    /*
    |--------------------------------------------------------------------------
    | Report GET views (sales / periodic HTML)
    |--------------------------------------------------------------------------
    |
    | When true, successful report page views also write an audit_logs row (in
    | addition to the audit log file). Exports and print always write DB audit.
    |
    */
    'log_report_views' => env('AUDIT_LOG_REPORT_VIEWS', false),

];
