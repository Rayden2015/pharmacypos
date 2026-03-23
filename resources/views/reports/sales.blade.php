@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Sales report</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active">Reports</li>
                            <li class="breadcrumb-item active">Sales</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <p class="text-muted small mb-0">Invoice-style list of POS orders by date range and branch (same data as checkout; totals from line items).</p>
                        <a href="{{ route('reports.periodic') }}" class="btn btn-outline-secondary btn-sm">Line items (today)</a>
                    </div>
                    <form method="get" action="{{ route('reports.sales') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-12 col-md-3">
                            <label class="form-label small mb-0">From</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small mb-0">To</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small mb-0">Branch</label>
                            <select name="site_id" class="form-select">
                                <option value="">All branches</option>
                                @foreach ($sites as $s)
                                    <option value="{{ $s->id }}" {{ (int) ($siteFilter ?? 0) === (int) $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}@if($s->code) · {{ $s->code }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Branch</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Mobile</th>
                                    <th scope="col" class="text-end">Gross</th>
                                    <th scope="col" class="text-end">Disc %</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col">Payment</th>
                                    <th scope="col" class="text-end">Paid</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="text-nowrap fw-semibold">#ORD-{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</td>
                                        <td class="text-nowrap small">{{ $order->created_at->format('d M Y H:i') }}</td>
                                        <td class="small">{{ $order->site?->name ?? '—' }}</td>
                                        <td>{{ $order->name ?: '—' }}</td>
                                        <td class="small">{{ $order->mobile ?: '—' }}</td>
                                        <td class="text-end">{{ $currencySymbol }}{{ number_format((float) $order->sales_gross, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $order->sales_disc_pct, 1) }}%</td>
                                        <td class="text-end fw-semibold">{{ $currencySymbol }}{{ number_format((float) $order->sales_net, 2) }}</td>
                                        <td class="small">{{ $order->transaction?->payment_method ?? '—' }}</td>
                                        <td class="text-end">{{ $currencySymbol }}{{ number_format((float) ($order->transaction?->paid_amount ?? 0), 2) }}</td>
                                        <td>
                                            @if ($order->sales_payment_status === 'paid')
                                                <span class="badge rounded-pill bg-success">Paid</span>
                                            @elseif ($order->sales_payment_status === 'pending')
                                                <span class="badge rounded-pill bg-warning text-dark">Pending</span>
                                            @elseif ($order->sales_payment_status === 'partial')
                                                <span class="badge rounded-pill bg-info text-dark">Partial</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-muted py-4">No orders in this range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $orders->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
