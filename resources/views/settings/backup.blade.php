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

            @if ($platformScope)
                <p class="text-muted small mb-3">
                    <strong>Platform scope:</strong> full database dump (SQLite copy or MySQL <code>mysqldump</code>) and a system manifest listing table row counts.
                    Store files securely; they may contain all tenants’ data.
                </p>
            @else
                <p class="text-muted small mb-3">
                    <strong>Tenant scope:</strong> exports include only your organization’s data. Database backup is a structured JSON export (not a raw SQL dump).
                </p>
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
