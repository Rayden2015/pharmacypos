@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Audit entry #{{ $auditLog->id }}</div>
            </div>
            @include('inc.msg')

            <div class="mb-3">
                <a href="{{ route('settings.audit-log.index', request()->query()) }}" class="btn btn-outline-secondary btn-sm">← Back to list</a>
            </div>

            <div class="card radius-10 mb-3">
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-sm-3">When</dt>
                        <dd class="col-sm-9">{{ $auditLog->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</dd>
                        <dt class="col-sm-3">User</dt>
                        <dd class="col-sm-9">
                            @if ($auditLog->user)
                                {{ $auditLog->user->name }} &lt;{{ $auditLog->user->email }}&gt;
                            @else
                                —
                            @endif
                        </dd>
                        <dt class="col-sm-3">Action</dt>
                        <dd class="col-sm-9"><code>{{ $auditLog->action }}</code></dd>
                        <dt class="col-sm-3">Subject</dt>
                        <dd class="col-sm-9">
                            @if ($auditLog->subject_type && $auditLog->subject_id)
                                @if (!empty($subjectUrl))
                                    <a href="{{ $subjectUrl }}">{{ \App\Support\AuditSubjectLink::label($auditLog->subject_type, (int) $auditLog->subject_id) }}</a>
                                    <span class="text-muted small d-block">{{ $auditLog->subject_type }} #{{ $auditLog->subject_id }}</span>
                                @else
                                    <span class="text-muted">{{ \App\Support\AuditSubjectLink::label($auditLog->subject_type, (int) $auditLog->subject_id) }}</span>
                                    <span class="text-muted small d-block">No direct link for this record type.</span>
                                @endif
                            @else
                                —
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            @foreach (['old_values' => 'Before', 'new_values' => 'After', 'context' => 'Context'] as $field => $label)
                @if ($auditLog->{$field})
                    <div class="card radius-10 mb-3">
                        <div class="card-header py-2"><strong>{{ $label }}</strong></div>
                        <div class="card-body p-0">
                            <pre class="small mb-0 p-3 bg-light border-0" style="max-height: 24rem; overflow:auto;">{{ json_encode($auditLog->{$field}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endsection
