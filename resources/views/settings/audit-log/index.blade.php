@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Audit log</div>
            </div>
            @include('inc.msg')

            <p class="text-muted small mb-3">
                Activity your organization records in the system (who changed what). Sensitive fields are redacted in storage.
                Exports include deep links when the record type supports it (up to 10,000 rows per download).
                @if (auth()->user()->isSuperAdmin())
                    <span class="d-block mt-1">Platform view: all tenants.</span>
                @endif
            </p>

            <div class="card radius-10 mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('settings.audit-log.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Search</label>
                            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="Action or subject type">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">User</label>
                            <select name="user_id" class="form-select form-select-sm">
                                <option value="">Any</option>
                                @foreach ($teamUsers as $u)
                                    <option value="{{ $u->id }}" @selected((string) $filters['user_id'] === (string) $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">From</label>
                            <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-0">To</label>
                            <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <a href="{{ route('settings.audit-log.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                            <a href="{{ route('settings.audit-log.export', request()->query()) }}" class="btn btn-outline-success btn-sm">Export CSV</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card radius-10">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>When</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Subject</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="text-nowrap small">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}</td>
                                        <td class="small">
                                            @if ($log->user)
                                                {{ $log->user->name }}
                                                <span class="text-muted d-block">{{ $log->user->email }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="small"><code>{{ \Illuminate\Support\Str::limit($log->action, 48) }}</code></td>
                                        <td class="small">
                                            @if ($log->subject_type && $log->subject_id)
                                                @php
                                                    $subUrl = \App\Support\AuditSubjectLink::url($log->subject_type, (int) $log->subject_id, auth()->user());
                                                @endphp
                                                @if ($subUrl)
                                                    <a href="{{ $subUrl }}">{{ \App\Support\AuditSubjectLink::label($log->subject_type, (int) $log->subject_id) }}</a>
                                                @else
                                                    <span class="text-muted">{{ \App\Support\AuditSubjectLink::label($log->subject_type, (int) $log->subject_id) }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('settings.audit-log.show', $log) }}" class="btn btn-sm btn-outline-primary">Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No audit entries match these filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($logs->hasPages())
                    <div class="card-footer">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
