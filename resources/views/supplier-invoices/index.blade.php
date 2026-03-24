@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Vendor payments</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Vendor payments</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <p class="text-muted small mb-3">
                    Accounts payable to your <strong>suppliers</strong> (per-invoice paid vs outstanding). This is separate from
                    <strong>platform subscription</strong> billing in Super Admin → Purchase transactions.
                </p>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <span class="small text-muted">Filter and search supplier invoices.</span>
                    <a href="{{ route('supplier-invoices.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Record invoice</a>
                </div>

                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Search</label>
                        <input type="search" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Reference, invoice #, supplier">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                            <option value="partially_paid" @selected(request('status') === 'partially_paid')>Partially paid</option>
                            <option value="overdue" @selected(request('status') === 'overdue')>Overdue</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Payment method</label>
                        <select name="payment_method" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach ($paymentMethods as $pm)
                                <option value="{{ $pm }}" @selected(request('payment_method') === $pm)>{{ $pm }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Apply</button>
                    </div>
                </form>

                <div class="card radius-10">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Supplier</th>
                                        <th>Invoice no.</th>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Outstanding</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($invoices as $inv)
                                        @php
                                            $st = $inv->computedStatus();
                                            $out = (float) $inv->total_amount - (float) $inv->paid_amount;
                                            $badge = match ($st) {
                                                'paid' => 'success',
                                                'partially_paid' => 'warning',
                                                'overdue' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="text-nowrap small"><span class="badge bg-light text-dark">{{ $inv->reference }}</span></td>
                                            <td class="fw-semibold">{{ $inv->supplier->supplier_name ?? '—' }}</td>
                                            <td>{{ $inv->invoice_number }}</td>
                                            <td class="text-nowrap small">{{ $inv->invoice_date->format('d M Y') }}</td>
                                            <td class="small">{{ $inv->payment_method ?? '—' }}</td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format((float) $inv->paid_amount, 2) }}</td>
                                            <td class="text-end">{{ $out > 0 ? $currencySymbol . number_format($out, 2) : '—' }}</td>
                                            <td><span class="badge bg-{{ $badge }}">{{ str_replace('_', ' ', ucfirst($st)) }}</span></td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('supplier-invoices.edit', $inv) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form action="{{ route('supplier-invoices.destroy', $inv) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this record?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No supplier invoices yet. Record one to track payables.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">{{ $invoices->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
