<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        if ($e instanceof AuthorizationException) {
            $request = request();
            if ($request && $request->user()) {
                Log::channel('audit')->warning('auth.policy.denied', [
                    'message' => $e->getMessage(),
                    'user_id' => $request->user()->id,
                    'path' => $request->path(),
                    'method' => $request->method(),
                ]);
            }
        }

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

        /*
         * Symfony HttpException (abort(), etc.) is in Laravel's internal "do not report" list,
         * so nothing would be written to the default log. Record status + message + route for troubleshooting.
         */
        if ($e instanceof HttpException && ! $e instanceof NotFoundHttpException) {
            $request = request();
            Log::warning('http.exception', [
                'exception' => get_class($e),
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage() !== '' ? $e->getMessage() : '(empty)',
                'route_action' => Route::currentRouteAction(),
                'path' => $request?->path(),
                'http_method' => $request?->method(),
                'user_id' => $request?->user()?->id,
            ]);
        }

        parent::report($e);
    }

    /**
     * Extra keys merged into Laravel's default exception log line for reported throwables.
     */
    protected function context()
    {
        try {
            $request = request();

            return array_merge(parent::context(), array_filter([
                'route_action' => Route::currentRouteAction(),
                'path' => $request?->path(),
                'http_method' => $request?->method(),
            ], static function ($v) {
                return $v !== null && $v !== '';
            }));
        } catch (Throwable $e) {
            return parent::context();
        }
    }
}
