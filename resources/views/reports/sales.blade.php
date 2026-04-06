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
                        <p class="text-muted small mb-0">
                            Invoice-style POS sales for the selected range. <strong>Sales amount</strong> is pre-discount line totals; <strong>net revenue</strong> matches checkout line totals.
                            Tax is not stored per order yet—use <strong>Disc. %</strong> / deductions for POS line discounts. KPI % vs. the previous window of the same length (same filters).
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            @can('reports.export')
                            <a href="{{ route('reports.sales.export', request()->except('page')) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
                            @endcan
                            @can('reports.view')
                            <a href="{{ route('reports.sales.print', request()->except('page')) }}" class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener">Print</a>
                            <a href="{{ route('reports.periodic') }}" class="btn btn-outline-secondary btn-sm">Line items report</a>
                            @endcan
                        </div>
                    </div>
                    <form method="get" action="{{ route('reports.sales') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-12 col-md-3">
                            <label class="form-label small mb-0">Search</label>
                            <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Invoice #, customer, mobile…" autocomplete="off">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0">From</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0">To</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>
                        <div class="col-12 col-md-3">
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

                    @php
                        $pctFmt = function (?float $p): string {
                            if ($p === null) {
                                return '—';
                            }
                            $sign = $p > 0 ? '+' : '';
                            return $sign.number_format($p, 1).'%';
                        };
                    @endphp

                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
                        <div class="col">
                            <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                                <div class="card-body py-3">
                                    <p class="mb-0 text-secondary small">Total sales amount</p>
                                    <h5 class="my-1 text-primary">{{ $currencySymbol }}{{ number_format($salesKpis['gross'], 2) }}</h5>
                                    <p class="mb-0 font-13 text-muted">vs prior window: {{ $pctFmt($salesKpis['pct_gross']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card radius-10 border-start border-0 border-3 border-warning h-100">
                                <div class="card-body py-3">
                                    <p class="mb-0 text-secondary small">Line discounts (gross − net)</p>
                                    <h5 class="my-1 text-warning text-dark">{{ $currencySymbol }}{{ number_format($salesKpis['deductions'], 2) }}</h5>
                                    <p class="mb-0 font-13 text-muted">vs prior window: {{ $pctFmt($salesKpis['pct_deductions']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card radius-10 border-start border-0 border-3 border-success h-100">
                                <div class="card-body py-3">
                                    <p class="mb-0 text-secondary small">Net revenue</p>
                                    <h5 class="my-1 text-success">{{ $currencySymbol }}{{ number_format($salesKpis['net'], 2) }}</h5>
                                    <p class="mb-0 font-13 text-muted">vs prior window: {{ $pctFmt($salesKpis['pct_net']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card radius-10 border-start border-0 border-3 border-secondary h-100">
                                <div class="card-body py-3">
                                    <p class="mb-0 text-secondary small">Invoice count</p>
                                    <h5 class="my-1 text-secondary">{{ number_format($salesKpis['invoice_count']) }}</h5>
                                    <p class="mb-0 font-13 text-muted">vs prior window: {{ $pctFmt($salesKpis['pct_invoices']) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Invoice no.</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Branch</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Mobile</th>
                                    <th scope="col" class="text-end">Sales amount</th>
                                    <th scope="col" class="text-end">Disc. %</th>
                                    <th scope="col" class="text-end">Net revenue</th>
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
