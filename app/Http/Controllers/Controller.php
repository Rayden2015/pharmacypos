<?php

namespace App\Http\Controllers;

use App\Support\RequestCorrelation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Execute a controller action and log successful completion once per request.
     * Uncaught exceptions are reported by {@see \App\Exceptions\Handler} with route context.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function callAction($method, $parameters)
    {
        $response = parent::callAction($method, $parameters);

        if ($this->shouldLogControllerSuccess()) {
            Log::info('controller.action.success', array_filter([
                'request_id' => RequestCorrelation::id(),
                'controller' => static::class,
                'action' => $method,
                'status' => $this->responseStatusCode($response),
                'path' => request()?->path(),
                'http_method' => request()?->method(),
                'user_id' => request()?->user()?->id,
            ], static function ($v) {
                return $v !== null && $v !== '';
            }));
        }

        return $response;
    }

    protected function shouldLogControllerSuccess(): bool
    {
        return (bool) config('logging.log_controller_actions', true);
    }

    /**
     * @param  mixed  $response
     */
    protected function responseStatusCode($response): int
    {
        if (is_object($response) && method_exists($response, 'getStatusCode')) {
            return (int) $response->getStatusCode();
        }

        return 200;
    }
}
