<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Extra context when /messages or /notifications 404 (often no route match or bad deploy).
     */
    public function report(Throwable $e)
    {
        if ($e instanceof NotFoundHttpException) {
            $request = request();
            if ($request) {
                $path = ltrim($request->getPathInfo(), '/');
                if (str_starts_with($path, 'messages') || str_starts_with($path, 'notifications')) {
                    Log::warning('tenant_comms.404_not_found', [
                        'path' => $request->path(),
                        'path_info' => $request->getPathInfo(),
                        'method' => $request->method(),
                        'full_url' => $request->fullUrl(),
                        'app_url' => config('app.url'),
                        'user_id' => $request->user()?->id,
                        'hint' => 'If path_info is wrong, check web server docroot/subfolder and APP_URL. Run php artisan route:list --path=messages.',
                    ]);
                }
            }
        }

        parent::report($e);
    }
}
