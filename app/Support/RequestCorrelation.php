<?php

namespace App\Support;

/**
 * Per-request id for log/trace correlation (set by AssignRequestCorrelationId middleware).
 */
final class RequestCorrelation
{
    public const ATTRIBUTE_KEY = 'request_id';

    public const REQUEST_HEADER = 'X-Request-ID';

    public static function id(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = request();

        if (! $request) {
            return null;
        }

        $id = $request->attributes->get(self::ATTRIBUTE_KEY);

        return is_string($id) && $id !== '' ? $id : null;
    }
}
