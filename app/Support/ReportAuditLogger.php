<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Structured logging + optional DB audit rows for report access and exports.
 */
final class ReportAuditLogger
{
    public static function log(Request $request, string $action, array $filters = []): void
    {
        $user = $request->user();
        $sanitized = Audit::sanitize($filters) ?? [];

        $payload = [
            'action' => $action,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'filters' => $sanitized,
        ];

        Log::channel('audit')->info('report.'.$action, $payload);

        $writeDbAudit = in_array($action, [
            'sales.export',
            'dashboard.export',
            'sales.print',
            'periodic.print',
        ], true);

        if ($writeDbAudit || (config('audit.log_report_views', false) && in_array($action, [
            'sales.view',
            'periodic.index',
        ], true))) {
            Audit::record(
                'report.'.$action,
                null,
                $sanitized,
                null,
                null,
                $user ? $user->id : null,
                ['audit_channel' => 'reports']
            );
        }
    }
}
