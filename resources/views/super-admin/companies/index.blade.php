@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Tenants (companies)</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-0">Manage tenants</h5>
                <p class="text-muted small mb-0">Each tenant maps to a subscribing organization; branches are sites under the tenant.</p>
            </div>
            <a href="{{ route('super-admin.companies.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add tenant</a>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
            <div class="col">
                <div class="card radius-10 border-0 bg-light">
                    <div class="card-body py-3">
                        <p class="small text-secondary mb-0">Total</p>
                        <h5 class="mb-0">{{ number_format($stats['total']) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 bg-light">
                    <div class="card-body py-3">
                        <p class="small text-secondary mb-0">Active</p>
                        <h5 class="mb-0 text-success">{{ number_format($stats['active']) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 bg-light">
                    <div class="card-body py-3">
                        <p class="small text-secondary mb-0">Inactive</p>
                        <h5 class="mb-0 text-danger">{{ number_format($stats['inactive']) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 bg-light">
                    <div class="card-body py-3">
                        <p class="small text-secondary mb-0">Branches (sites)</p>
                        <h5 class="mb-0">{{ number_format($stats['locations']) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <form method="get" action="{{ route('super-admin.companies.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label small mb-0">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Name, email, URL">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            </div>
        </form>

        <div class="card radius-10">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Company</th>
                                <th>Email</th>
                                <th>Account URL</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($companies as $c)
                                <tr>
                                    <td>{{ $c->company_name }}</td>
                                    <td>{{ $c->company_email }}</td>
                                    <td><code>{{ $c->slug }}.{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'app' }}</code></td>
                                    <td>
                                        @if ($c->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('super-admin.companies.edit', $c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="{{ route('super-admin.companies.destroy', $c) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this tenant?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No companies yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($companies->hasPages())
                <div class="card-footer">{{ $companies->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
