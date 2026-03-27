<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
            Integration::captureUnhandledException($e);
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

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return $this->renderJsonException($request, $e);
        }

        if (config('app.debug')) {
            return parent::render($request, $e);
        }

        if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
            return parent::render($request, $e);
        }

        if ($e instanceof HttpExceptionInterface) {
            $user = $request->user();
            if ($user && $user->isSuperAdmin()) {
                return parent::render($request, $e);
            }

            return $this->renderFriendlyHttpExceptionWeb($e);
        }

        $incidentId = (string) Str::uuid();
        Log::error('incident.unhandled', array_merge($this->context(), [
            'incident_id' => $incidentId,
            'exception_class' => get_class($e),
            'exception_message' => $e->getMessage(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
        ]));

        $super = $request->user() && $request->user()->isSuperAdmin();

        return response()->view('errors.500', [
            'incidentId' => $incidentId,
            'showDetail' => (bool) $super,
            'detailMessage' => $super ? $e->getMessage() : null,
        ], 500);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function renderFriendlyHttpExceptionWeb(HttpExceptionInterface $e)
    {
        $status = $e->getStatusCode();
        $templates = [
            403 => 'errors.403',
            404 => 'errors.404',
            419 => 'errors.419',
            429 => 'errors.429',
            503 => 'errors.503',
        ];
        if (isset($templates[$status]) && view()->exists($templates[$status])) {
            $response = response()->view($templates[$status], [], $status);
        } elseif ($status >= 400 && $status < 500) {
            $response = response()->view('errors.generic', [
                'code' => $status,
                'title' => __('Request could not be completed'),
                'message' => __('Check the address or your permissions, then try again.'),
            ], $status);
        } else {
            $response = response()->view('errors.generic', [
                'code' => $status,
                'title' => __('Something went wrong'),
                'message' => __("We're having trouble with that request. Try again shortly."),
            ], $status);
        }

        foreach ($e->getHeaders() as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function renderJsonException(Request $request, Throwable $e)
    {
        if (config('app.debug')) {
            return parent::render($request, $e);
        }

        if ($e instanceof ValidationException) {
            return parent::render($request, $e);
        }

        if ($e instanceof AuthenticationException) {
            return parent::render($request, $e);
        }

        $super = $request->user() && $request->user()->isSuperAdmin();

        if ($e instanceof HttpExceptionInterface) {
            if ($super) {
                return parent::render($request, $e);
            }

            $status = $e->getStatusCode();
            $messages = [
                403 => 'You do not have permission to perform this action.',
                404 => 'The requested resource was not found.',
                419 => 'Session expired. Please refresh and try again.',
                429 => 'Too many requests. Please wait and try again.',
            ];

            return response()->json([
                'message' => $messages[$status] ?? 'Request could not be completed.',
            ], $status);
        }

        $incidentId = (string) Str::uuid();
        Log::error('incident.unhandled', array_merge($this->context(), [
            'incident_id' => $incidentId,
            'exception_class' => get_class($e),
            'exception_message' => $e->getMessage(),
            'exception_file' => $e->getFile(),
            'exception_line' => $e->getLine(),
        ]));

        $payload = [
            'message' => $super
                ? $e->getMessage()
                : 'Something went wrong. Please try again later.',
            'incident_id' => $incidentId,
        ];

        return response()->json($payload, 500);
    }
}
