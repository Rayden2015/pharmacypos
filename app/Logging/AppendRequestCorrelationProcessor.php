<?php

namespace App\Logging;

use App\Support\RequestCorrelation;

/**
 * Adds request_id to Monolog "extra" so every log line includes correlation context (Laravel 8).
 */
class AppendRequestCorrelationProcessor
{
    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function __invoke(array $record): array
    {
        if (app()->runningInConsole()) {
            return $record;
        }

        try {
            if (! app()->bound('request') || ! request()) {
                return $record;
            }

            $id = request()->attributes->get(RequestCorrelation::ATTRIBUTE_KEY);
            if (is_string($id) && $id !== '') {
                $record['extra']['request_id'] = $id;
            }
        } catch (\Throwable $e) {
            // Avoid breaking logging if request stack is odd.
        }

        return $record;
    }
}
