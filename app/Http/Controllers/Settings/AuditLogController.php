<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit;
use App\Support\AuditSubjectLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /** Maximum rows per CSV export (safety cap). */
    private const EXPORT_LIMIT = 10000;

    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);

        $logs = $this->filteredQuery($request->user(), $filters)
            ->with(['user:id,name,email,company_id'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $teamUsers = $this->filterableUsers($request->user());

        return view('settings.audit-log.index', compact('logs', 'filters', 'teamUsers'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filtersFromRequest($request);
        $viewer = $request->user();

        $base = $this->filteredQuery($viewer, $filters);
        $totalAvailable = (clone $base)->count();
        $rowsExported = (int) min($totalAvailable, self::EXPORT_LIMIT);

        Log::channel('audit')->info('audit_log.export', [
            'user_id' => $viewer->id,
            'filters' => Audit::sanitize([
                'q' => $filters['q'],
                'user_id' => $filters['user_id'],
                'from' => $filters['from'],
                'to' => $filters['to'],
            ]),
            'row_cap' => self::EXPORT_LIMIT,
            'rows_exported' => $rowsExported,
        ]);

        Audit::record(
            'audit_log.export',
            null,
            Audit::sanitize([
                'filters' => [
                    'q' => $filters['q'] !== '' ? $filters['q'] : null,
                    'user_id' => $filters['user_id'],
                    'from' => $filters['from'],
                    'to' => $filters['to'],
                ],
                'rows' => $rowsExported,
            ]),
            null,
            null,
            $viewer->id,
            ['audit_channel' => 'reports']
        );

        $filename = 'audit-log-'.now()->timezone(config('app.timezone'))->format('Y-m-d-His').'.csv';

        $self = $this;

        return response()->streamDownload(function () use ($self, $viewer, $filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'id',
                'created_at',
                'user_email',
                'user_name',
                'action',
                'subject_type',
                'subject_id',
                'subject_label',
                'subject_url',
            ]);

            $rows = $self->filteredQuery($viewer, $filters)
                ->with(['user:id,name,email,company_id'])
                ->orderByDesc('id')
                ->limit(self::EXPORT_LIMIT)
                ->get();

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                    $row->user?->email,
                    $row->user?->name,
                    $row->action,
                    $row->subject_type,
                    $row->subject_id,
                    AuditSubjectLink::label($row->subject_type, $row->subject_id ? (int) $row->subject_id : null),
                    AuditSubjectLink::url($row->subject_type, $row->subject_id ? (int) $row->subject_id : null, $viewer),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Request $request, AuditLog $auditLog): View
    {
        $this->authorize('view', $auditLog);

        $auditLog->load(['user:id,name,email,company_id']);
        $subjectUrl = AuditSubjectLink::url(
            $auditLog->subject_type,
            $auditLog->subject_id ? (int) $auditLog->subject_id : null,
            $request->user()
        );

        return view('settings.audit-log.show', compact('auditLog', 'subjectUrl'));
    }

    /**
     * @return array{q: string, user_id: string|null, from: string|null, to: string|null}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'user_id' => $request->query('user_id'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }

    /**
     * @param  array{q: string, user_id: string|null, from: string|null, to: string|null}  $filters
     */
    private function filteredQuery(User $viewer, array $filters): Builder
    {
        $query = $this->scopedQuery($viewer);

        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('action', 'like', $term)
                    ->orWhere('subject_type', 'like', $term);
            });
        }

        if ($filters['user_id'] !== null && $filters['user_id'] !== '') {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }

    private function scopedQuery(User $viewer)
    {
        $q = AuditLog::query();

        if ($viewer->isSuperAdmin()) {
            return $q;
        }

        $companyId = $viewer->company_id;
        if (! $companyId) {
            abort(403, 'Your account is not linked to an organization.');
        }

        return $q->where(function ($sub) use ($companyId) {
            $sub->where('company_id', $companyId)
                ->orWhere(function ($legacy) use ($companyId) {
                    $legacy->whereNull('company_id')
                        ->whereHas('user', fn ($uq) => $uq->where('company_id', $companyId));
                });
        });
    }

    private function filterableUsers(User $viewer)
    {
        if ($viewer->isSuperAdmin()) {
            return User::query()
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'email', 'company_id']);
        }

        return User::query()
            ->where('company_id', $viewer->company_id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
