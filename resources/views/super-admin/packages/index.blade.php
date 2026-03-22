@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Packages</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Subscription packages</h5>
            <a href="{{ route('super-admin.packages.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add package</a>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
            @foreach (['total' => 'Total', 'active' => 'Active', 'inactive' => 'Inactive', 'cycles' => 'Cycle types'] as $k => $label)
                <div class="col">
                    <div class="card radius-10 border-0 bg-light">
                        <div class="card-body py-3">
                            <p class="small text-secondary mb-0">{{ $label }}</p>
                            <h5 class="mb-0">{{ number_format($stats[$k]) }}</h5>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <form method="get" class="row g-2 mb-3">
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
        </form>

        <div class="card radius-10">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Plan</th>
                                <th>Cycle</th>
                                <th>Price</th>
                                <th>Billing days</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($packages as $p)
                                <tr>
                                    <td>{{ $p->name }}</td>
                                    <td>{{ ucfirst($p->billing_cycle) }}</td>
                                    <td>${{ number_format($p->price, 2) }}</td>
                                    <td>{{ $p->billing_days }}</td>
                                    <td>
                                        @if ($p->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('super-admin.packages.edit', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form action="{{ route('super-admin.packages.destroy', $p) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this package?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No packages — seed or create one.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($packages->hasPages())
                <div class="card-footer">{{ $packages->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
