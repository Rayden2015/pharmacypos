@extends('layouts.dash')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Cross-site dashboard</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Compare branches</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @include('inc.msg')

            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <h5 class="mb-1">Performance by branch</h5>
                    <p class="text-muted small mb-0">
                        Compare POS sales, orders, and recorded payments across active sites.
                        <span class="d-block mt-1"><strong>{{ $period_30_label }}</strong> vs <strong>{{ $period_7_label }}</strong> · “Strong / slow” uses each site’s 30-day sales vs the <em>median</em> across branches.</span>
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-left-arrow-alt me-1"></i>Main dashboard</a>
                </div>
            </div>

            @if (empty($site_rows))
                <div class="alert alert-info border-0 shadow-sm">
                    No active sites found. Add branches under <a href="{{ route('sites.index') }}">Settings → Branches</a>.
                </div>
            @else
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                            <div class="card-body py-3">
                                <p class="mb-0 text-secondary small">Network sales (30d)</p>
                                <h4 class="my-1 text-primary">{{ $currencySymbol }}{{ number_format($network_sales_30d, 2) }}</h4>
                                <p class="mb-0 small text-muted">All active branches combined</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card radius-10 border-start border-0 border-3 border-secondary h-100">
                            <div class="card-body py-3">
                                <p class="mb-0 text-secondary small">Median branch (30d sales)</p>
                                <h4 class="my-1">{{ $currencySymbol }}{{ number_format($median_sales_30d, 2) }}</h4>
                                <p class="mb-0 small text-muted">Benchmark for strong / slow</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                            <div class="card-body py-3">
                                <p class="mb-0 text-secondary small">Avg per branch (30d)</p>
                                <h4 class="my-1 text-info">{{ $currencySymbol }}{{ number_format($network_avg_sales_30d, 2) }}</h4>
                                <p class="mb-0 small text-muted">Mean — use median to ignore outliers</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card radius-10 mb-4">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h6 class="mb-0">Sales vs payments (30 days)</h6>
                        <p class="small text-muted mb-0">POS line totals vs payment rows recorded (per branch)</p>
                    </div>
                    <div class="card-body">
                        <canvas id="crossSiteSalesPaymentsChart" height="120"></canvas>
                    </div>
                </div>

                <div class="card radius-10">
                    <div class="card-header bg-transparent border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="mb-0">Branch comparison</h6>
                            <p class="small text-muted mb-0">Sorted by 30-day sales (highest first)</p>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width:3rem;">#</th>
                                        <th>Site</th>
                                        <th class="text-center">Signal</th>
                                        <th class="text-end">Sales 30d</th>
                                        <th class="text-end">Share</th>
                                        <th class="text-end">vs median</th>
                                        <th class="text-end">Sales 7d</th>
                                        <th class="text-end">Today</th>
                                        <th class="text-end">Orders 30d</th>
                                        <th class="text-end">Avg order 30d</th>
                                        <th class="text-end">Payments 30d</th>
                                        <th class="text-end">Tx rows 30d</th>
                                        <th class="text-end">Rx 30d</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($site_rows as $row)
                                        @php
                                            $perf = $row['perf'] ?? 'typical';
                                            $badge = match ($perf) {
                                                'strong' => ['class' => 'bg-success', 'label' => 'Strong'],
                                                'slow' => ['class' => 'bg-danger', 'label' => 'Slow'],
                                                default => ['class' => 'bg-secondary', 'label' => 'Typical'],
                                            };
                                        @endphp
                                        <tr>
                                            <td class="text-center fw-semibold">{{ $row['rank'] }}</td>
                                            <td>
                                                <span class="fw-semibold">{{ $row['site']->name }}</span>
                                                @if ($row['site']->code)
                                                    <span class="text-muted small">· {{ $row['site']->code }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                            </td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format($row['sales_30d'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['share_pct'], 1) }}%</td>
                                            <td class="text-end @if(($row['vs_median_pct'] ?? 0) > 0) text-success @elseif(($row['vs_median_pct'] ?? 0) < 0) text-danger @endif">
                                                @if (($row['vs_median_pct'] ?? 0) > 0)+@endif{{ number_format($row['vs_median_pct'], 1) }}%
                                            </td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format($row['sales_7d'], 2) }}</td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format($row['sales_today'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['orders_30d']) }}</td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format($row['avg_order_30d'], 2) }}</td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format($row['payments_30d'], 2) }}</td>
                                            <td class="text-end">{{ number_format($row['transactions_30d']) }}</td>
                                            <td class="text-end">{{ number_format($row['rx_30d']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mt-3 mb-0">
                            <strong>Strong</strong> = 30-day sales ≥ 110% of the median; <strong>Slow</strong> = ≤ 90% of the median.
                            Share is each branch’s portion of total 30-day POS sales.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;
            var ctx = document.getElementById('crossSiteSalesPaymentsChart');
            if (!ctx) return;

            var labels = @json($chart_labels ?? []);
            var sales = @json($chart_sales_30d ?? []);
            var pays = @json($chart_payments_30d ?? []);

            new Chart(ctx.getContext('2d'), {
                type: 'horizontalBar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'POS sales (30d)',
                            backgroundColor: 'rgba(13, 110, 253, 0.75)',
                            data: sales,
                        },
                        {
                            label: 'Payments recorded (30d)',
                            backgroundColor: 'rgba(25, 135, 84, 0.7)',
                            data: pays,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    legend: { position: 'bottom' },
                    scales: {
                        xAxes: [{ stacked: false, ticks: { beginAtZero: true } }],
                        yAxes: [{ stacked: false }],
                    },
                },
            });
        });
    </script>
@endsection
