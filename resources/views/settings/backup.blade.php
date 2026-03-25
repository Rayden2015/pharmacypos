@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Backup settings</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Backup</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            @php
                $pendingJobs = $generationRequests->whereIn('status', ['queued', 'running'])->isNotEmpty();
            @endphp

            <div class="card radius-10 mb-3" id="backup-jobs-card" @if($pendingJobs) data-backup-poll="1" @endif>
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0">Backup jobs</h6>
                    <p class="small text-muted mb-0">Generations run in the background so the browser does not time out. Refresh the file lists when status is <span class="text-success">completed</span>.</p>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Started</th>
                                    <th>Finished</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody id="backup-jobs-tbody">
                                @forelse ($generationRequests as $job)
                                    <tr id="backup-job-row-{{ $job->id }}">
                                        <td class="fw-semibold">{{ $job->label() }}</td>
                                        <td>
                                            <span class="badge rounded-pill backup-job-status bg-{{ $job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : ($job->status === 'running' ? 'primary' : 'secondary')) }}">{{ ucfirst($job->status) }}</span>
                                        </td>
                                        <td class="text-muted small">{{ $job->started_at?->format('d M Y H:i') ?? '—' }}</td>
                                        <td class="text-muted small">{{ $job->completed_at?->format('d M Y H:i') ?? '—' }}</td>
                                        <td class="small text-break">
                                            @if ($job->status === 'failed' && $job->error_message)
                                                <span class="text-danger">{{ \Illuminate\Support\Str::limit($job->error_message, 120) }}</span>
                                            @elseif ($job->output_path)
                                                <code class="small">{{ basename($job->output_path) }}</code>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">No backup jobs yet. Use Generate backup above.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($platformScope)
                <p class="text-muted small mb-3">
                    <strong>Platform scope:</strong> full database dump (SQLite copy or MySQL <code>mysqldump</code>) and a system manifest listing table row counts.
                    Store files securely; they may contain all tenants’ data.
                </p>
                <div class="alert alert-light border mb-3 small" role="note">
                    <strong>On-demand only:</strong> the tables below list backups you generate from this page (<code>storage/app/backups/platform/…</code>).
                    <strong>Automated nightly backups</strong> (midnight, app timezone) are written to <code>storage/app/backups/scheduled/platform/</code> and are not shown here—retrieve them directly from the server.
                    Ensure the system cron runs <code>php artisan schedule:run</code> every minute (e.g. <code>* * * * *</code>).
                </div>
            @else
                <p class="text-muted small mb-3">
                    <strong>Tenant scope:</strong> exports include only your organization’s data. Database backup is a structured JSON export (not a raw SQL dump).
                </p>
                <div class="alert alert-light border mb-3 small" role="note">
                    Platform-wide nightly backups (if enabled on the server) live under <code>scheduled/platform/</code> and are not listed here.
                </div>
            @endif

            <div class="row g-3">
                <div class="col-12">
                    <div class="card radius-10">
                        <div class="card-header bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h6 class="mb-0">System backup</h6>
                                <p class="small text-muted mb-0">{{ $platformScope ? 'Manifest and environment summary' : 'Business summary (counts and company info)' }}</p>
                            </div>
                            <form method="post" action="{{ route('settings.backup.system') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bx bx-download me-1"></i>Generate backup</button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Created on</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($systemFiles as $f)
                                            <tr>
                                                <td class="fw-semibold">{{ $f['name'] }}</td>
                                                <td class="text-muted small">{{ $f['created']->format('d M Y') }}</td>
                                                <td class="text-end text-nowrap">
                                                    <a href="{{ route('settings.backup.download', ['category' => 'system', 'filename' => $f['name']]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                                    <form action="{{ route('settings.backup.destroy', ['category' => 'system', 'filename' => $f['name']]) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this file?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-4">No system backups yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card radius-10">
                        <div class="card-header bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h6 class="mb-0">Database backup</h6>
                                <p class="small text-muted mb-0">
                                    @if ($platformScope)
                                        Full database file (SQLite) or SQL dump (MySQL).
                                    @else
                                        JSON export of your tenant’s tables (orders, products, customers, etc.).
                                    @endif
                                </p>
                            </div>
                            <form method="post" action="{{ route('settings.backup.database') }}" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bx bx-download me-1"></i>Generate backup</button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Created on</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($databaseFiles as $f)
                                            <tr>
                                                <td class="fw-semibold">{{ $f['name'] }}</td>
                                                <td class="text-muted small">{{ $f['created']->format('d M Y') }}</td>
                                                <td class="text-end text-nowrap">
                                                    <a href="{{ route('settings.backup.download', ['category' => 'database', 'filename' => $f['name']]) }}" class="btn btn-sm btn-outline-secondary">Download</a>
                                                    <form action="{{ route('settings.backup.destroy', ['category' => 'database', 'filename' => $f['name']]) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this file?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-4">No database backups yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="small text-muted mt-3 mb-0">
                Files are stored under <code>storage/app/backups</code> on the server. For production, copy backups off-site and restrict server access.
            </p>
        </div>
    </div>
@endsection

@section('script')
    @if($pendingJobs)
        <script>
            (function () {
                var pollUrl = @json(route('settings.backup.generation-status'));
                var card = document.getElementById('backup-jobs-card');
                if (!card || !card.getAttribute('data-backup-poll')) return;

                function statusBadgeClass(status) {
                    if (status === 'completed') return 'success';
                    if (status === 'failed') return 'danger';
                    if (status === 'running') return 'primary';
                    return 'secondary';
                }

                function formatCell(iso) {
                    if (!iso) return '—';
                    try {
                        var d = new Date(iso);
                        return d.toLocaleString(undefined, { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                    } catch (e) { return '—'; }
                }

                function escHtml(s) {
                    return String(s == null ? '' : s)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/"/g, '&quot;');
                }

                function tick() {
                    fetch(pollUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        var requests = data.requests || [];
                        var pending = requests.some(function (x) { return x.status === 'queued' || x.status === 'running'; });
                        var tbody = document.getElementById('backup-jobs-tbody');
                        if (!tbody) return;

                        tbody.innerHTML = '';
                        if (requests.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No backup jobs yet.</td></tr>';
                        } else {
                            requests.forEach(function (job) {
                                var tr = document.createElement('tr');
                                tr.id = 'backup-job-row-' + job.id;
                                var detail = '—';
                                if (job.status === 'failed' && job.error_message) {
                                    var em = job.error_message.length > 120 ? job.error_message.slice(0, 117) + '…' : job.error_message;
                                    detail = '<span class="text-danger">' + escHtml(em) + '</span>';
                                } else if (job.output_path) {
                                    var base = job.output_path.split('/').pop();
                                    detail = '<code class="small">' + escHtml(base) + '</code>';
                                }
                                var kindLabel = escHtml(job.kind_label || job.kind || '');
                                var badgeClass = statusBadgeClass(job.status);
                                tr.innerHTML =
                                    '<td class="fw-semibold">' + kindLabel + '</td>' +
                                    '<td><span class="badge rounded-pill bg-' + badgeClass + '">' + escHtml(job.status.charAt(0).toUpperCase() + job.status.slice(1)) + '</span></td>' +
                                    '<td class="text-muted small">' + formatCell(job.started_at) + '</td>' +
                                    '<td class="text-muted small">' + formatCell(job.completed_at) + '</td>' +
                                    '<td class="small text-break">' + detail + '</td>';
                                tbody.appendChild(tr);
                            });
                        }

                        if (!pending) {
                            clearInterval(timer);
                            card.removeAttribute('data-backup-poll');
                            window.location.reload();
                        }
                    }).catch(function () { /* ignore */ });
                }

                var timer = setInterval(tick, 3000);
            })();
        </script>
    @endif
@endsection
