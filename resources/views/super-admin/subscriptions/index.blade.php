@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Subscriptions</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="mb-0">Tenant subscriptions</h5>
                <p class="text-muted small mb-0">Aligned with Dreams POS subscriptions list (subscriber, plan, billing, amount, dates).</p>
            </div>
            <a href="{{ route('super-admin.subscriptions.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add subscription</a>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-3 mb-3">
            <div class="col">
                <div class="card radius-10 border-0 bg-light">
                    <div class="card-body py-3">
                        <p class="small text-secondary mb-0">Total transaction</p>
                        <h5 class="mb-0">${{ number_format($stats['total_tx'], 2) }}</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-0 bg-light">
                    <div class="card-body py-3">
                        <p class="small text-secondary mb-0">Subscribers</p>
                        <h5 class="mb-0">{{ number_format($stats['subscribers']) }}</h5>
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
                        <p class="small text-secondary mb-0">Expired</p>
                        <h5 class="mb-0 text-warning">{{ number_format($stats['expired']) }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <form method="get" class="row g-2 mb-3 align-items-end">
            <div class="col-md-4">
                <input type="search" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Company name or email">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
            </div>
        </form>

        <div class="card radius-10">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>Subscriber</th>
                                <th>Plan</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Starts</th>
                                <th>Ends</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subscriptions as $sub)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $sub->company->company_name ?? '—' }}</div>
                                        <div class="text-muted">{{ $sub->company->company_email ?? '' }}</div>
                                    </td>
                                    <td>{{ optional($sub->subscriptionPackage)->displayLabel() ?? '—' }}</td>
                                    <td>${{ number_format($sub->amount, 2) }}</td>
                                    <td>{{ $sub->payment_method ?? '—' }}</td>
                                    <td>{{ $sub->starts_at ? $sub->starts_at->format('M j, Y') : '—' }}</td>
                                    <td>{{ $sub->ends_at ? $sub->ends_at->format('M j, Y') : '—' }}</td>
                                    <td><span class="badge bg-{{ $sub->status === 'active' ? 'success' : ($sub->status === 'expired' ? 'warning' : 'secondary') }}">{{ $sub->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No subscriptions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($subscriptions->hasPages())
                <div class="card-footer">{{ $subscriptions->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
