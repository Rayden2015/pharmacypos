@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Purchase transactions</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="text-muted small mb-0">Platform <strong>subscription billing</strong> (tenants paying for SaaS packages). Tenant <strong>vendor payments</strong> to medicine suppliers live under Medicines → Vendor payments.</p>
            <a href="{{ route('super-admin.payments.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Record payment</a>
        </div>

        <form method="get" class="row g-2 mb-3 align-items-end">
            <div class="col-md-4">
                <input type="search" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Invoice, company, email">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="unpaid" @selected(request('status') === 'unpaid')>Unpaid</option>
                    <option value="refunded" @selected(request('status') === 'refunded')>Refunded</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            </div>
        </form>

        <div class="card radius-10">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $pay)
                                <tr>
                                    <td><code>{{ $pay->invoice_reference ?: '—' }}</code></td>
                                    <td>{{ $pay->company->company_name ?? '—' }}</td>
                                    <td>{{ $pay->company->company_email ?? '—' }}</td>
                                    <td>{{ $pay->paid_at ? $pay->paid_at->format('M j, Y') : $pay->created_at->format('M j, Y') }}</td>
                                    <td>${{ number_format($pay->amount, 2) }}</td>
                                    <td>{{ $pay->payment_method ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $pay->status === 'paid' ? 'success' : ($pay->status === 'unpaid' ? 'warning' : 'secondary') }}">{{ ucfirst($pay->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No purchase transactions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($payments->hasPages())
                <div class="card-footer">{{ $payments->withQueryString()->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
