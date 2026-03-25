<head>
    <meta charset="utf-8">
    <title>Sales report</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<style>
    @media print {
        .no-print { display: none !important; }
    }
    .report-meta { font-size: 0.9rem; color: #555; }
    .kpi-row .card { border: 1px solid #dee2e6; }
</style>
@php
    $pctFmt = function (?float $p): string {
        if ($p === null) {
            return '—';
        }
        $sign = $p > 0 ? '+' : '';
        return $sign.number_format($p, 1).'%';
    };
@endphp
<div class="wrapper p-3">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mb-1">Sales report</h2>
            <p class="report-meta mb-0">
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                &mdash;
                {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                @if (!empty($branchLabel))
                    <br><span>Branch: {{ $branchLabel }}</span>
                @endif
                @if (request()->filled('q'))
                    <br><span>Search: {{ request('q') }}</span>
                @endif
            </p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary no-print" onclick="window.print()">Print</button>
    </div>

    <div class="row kpi-row mb-3 small">
        <div class="col-md-3 mb-2">
            <div class="card p-2 h-100">
                <div class="text-muted">Total sales amount</div>
                <div class="font-weight-bold">{{ $currencySymbol }}{{ number_format($salesKpis['gross'], 2) }}</div>
                <div class="text-muted">vs prior: {{ $pctFmt($salesKpis['pct_gross']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card p-2 h-100">
                <div class="text-muted">Line discounts</div>
                <div class="font-weight-bold">{{ $currencySymbol }}{{ number_format($salesKpis['deductions'], 2) }}</div>
                <div class="text-muted">vs prior: {{ $pctFmt($salesKpis['pct_deductions']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card p-2 h-100">
                <div class="text-muted">Net revenue</div>
                <div class="font-weight-bold">{{ $currencySymbol }}{{ number_format($salesKpis['net'], 2) }}</div>
                <div class="text-muted">vs prior: {{ $pctFmt($salesKpis['pct_net']) }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="card p-2 h-100">
                <div class="text-muted">Invoices</div>
                <div class="font-weight-bold">{{ number_format($salesKpis['invoice_count']) }}</div>
                <div class="text-muted">vs prior: {{ $pctFmt($salesKpis['pct_invoices']) }}</div>
            </div>
        </div>
    </div>

    <hr>
    <table class="table table-bordered table-sm">
        <thead class="thead-light">
            <tr>
                <th>Invoice no.</th>
                <th>Date</th>
                <th>Branch</th>
                <th>Customer</th>
                <th>Mobile</th>
                <th class="text-right">Sales amount</th>
                <th class="text-right">Disc. %</th>
                <th class="text-right">Net revenue</th>
                <th>Payment</th>
                <th class="text-right">Paid</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td class="text-nowrap font-weight-bold">#ORD-{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td class="text-nowrap small">{{ $order->created_at->format('d M Y H:i') }}</td>
                    <td class="small">{{ $order->site ? $order->site->name : '—' }}</td>
                    <td>{{ $order->name ?: '—' }}</td>
                    <td class="small">{{ $order->mobile ?: '—' }}</td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format((float) $order->sales_gross, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $order->sales_disc_pct, 1) }}%</td>
                    <td class="text-right font-weight-bold">{{ $currencySymbol }}{{ number_format((float) $order->sales_net, 2) }}</td>
                    <td class="small">{{ $order->transaction ? $order->transaction->payment_method : '—' }}</td>
                    <td class="text-right">{{ $currencySymbol }}{{ number_format((float) ($order->transaction ? $order->transaction->paid_amount : 0), 2) }}</td>
                    <td class="small">
                        @if ($order->sales_payment_status === 'paid')
                            Paid
                        @elseif ($order->sales_payment_status === 'pending')
                            Pending
                        @elseif ($order->sales_payment_status === 'partial')
                            Partial
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-muted text-center py-4">No orders in this range.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($orders->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="7" class="text-right font-weight-bold">Range total (net revenue)</td>
                    <td class="text-right font-weight-bold">{{ $currencySymbol }}{{ number_format($totalNet, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
