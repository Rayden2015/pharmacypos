<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class Audit
{
    /**
     * Request meta (IP, route, method) merged into context for every row.
     */
    public static function requestContext(): array
    {
        $req = Request::instance();
        $route = $req->route();

        return [
            'ip' => $req->ip(),
            'user_agent' => $req->userAgent(),
            'route' => $route ? $route->getName() : null,
            'url' => $req->fullUrl(),
            'method' => $req->method(),
        ];
    }

    /**
     * Append-only audit row (login, site switch, or custom controller actions).
     */
    public static function record(
        string $action,
        ?array $oldValues,
        ?array $newValues,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?int $userId = null,
        array $extraContext = []
    ): void {
        AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => self::sanitize($oldValues),
            'new_values' => self::sanitize($newValues),
            'context' => array_merge(self::requestContext(), $extraContext),
            'created_at' => now(),
        ]);
    }

    /**
     * Authenticated mutating HTTP request (sanitized body + response status).
     */
    public static function recordHttpRequest(int $statusCode, array $sanitizedPayload): void
    {
        $req = Request::instance();
        $route = $req->route();
        $name = $route ? $route->getName() : null;
        $action = 'http.'.strtolower($req->method()).'.'.($name ?: 'unnamed');

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => null,
            'subject_id' => null,
            'old_values' => null,
            'new_values' => ['request' => $sanitizedPayload],
            'context' => array_merge(self::requestContext(), [
                'http_status' => $statusCode,
                'audit_channel' => 'http',
            ]),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array<string, mixed>|null
     */
    public static function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $out = [];
        foreach ($data as $key => $value) {
            $k = (string) $key;
            if (in_array($k, ['password', 'password_confirmation', 'current_password', 'confirm_password'], true)) {
                $out[$k] = '[redacted]';

                continue;
            }
            $out[$k] = self::sanitizeValue($value);
        }

        return $out;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    public static function sanitizeValue($value)
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }
        if ($value instanceof Model) {
            return $value->getKey();
        }
        if (is_array($value)) {
            return self::sanitize($value);
        }
        if (is_string($value)) {
            if (strlen($value) > 4000) {
                return substr($value, 0, 4000).'…[truncated]';
            }

            return $value;
        }

        return (string) $value;
    }

    /**
     * Remove sensitive keys from request input for HTTP audit.
     */
    public static function sanitizeRequestInput(array $input): array
    {
        $drop = ['_token', '_method', 'password', 'password_confirmation', 'current_password', 'confirm_password'];
        foreach ($drop as $k) {
            unset($input[$k]);
        }

        return self::sanitize($input) ?? [];
    }
}
