@extends('layouts.dash')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Dashboard</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Overview</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @include('inc.msg')

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="mb-0">Welcome, {{ $welcome_name ?? 'Admin' }}</h5>
                    <p class="text-muted small mb-0">{{ now()->format('l, F j, Y') }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('orders.index') }}" class="btn btn-primary btn-sm"><i class="bx bx-cart me-1"></i>POS</a>
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-success btn-sm"><i class="bx bx-package me-1"></i>Receive stock</a>
                </div>
            </div>

            @if ($first_low_stock)
                <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bx bx-error-circle fs-4"></i>
                        <span>
                            <strong>Low stock:</strong> {{ $first_low_stock->product_name }} is at or below alert
                            ({{ $first_low_stock->quantity }} / alert {{ $first_low_stock->stock_alert }}).
                        </span>
                    </div>
                    <a href="{{ route('inventory.receive.create', ['product_id' => $first_low_stock->id]) }}" class="btn btn-sm btn-dark shrink-0">Add stock</a>
                </div>
            @endif

            {{-- DreamsPOS-style KPI row: sales vs purchase --}}
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-3">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total sales (MTD)</p>
                                    <h4 class="my-1 text-primary">{{ $currencySymbol }}{{ number_format($month_sales, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">POS line totals, {{ now()->format('F Y') }}</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-cosmic text-white ms-auto"><i class="bx bx-line-chart"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-secondary h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total sales return</p>
                                    <h4 class="my-1 text-secondary">{{ $currencySymbol }}{{ number_format($total_sales_return, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Returns module not configured</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-moonlit text-white ms-auto"><i class="bx bx-revision"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total purchase (MTD)</p>
                                    <h4 class="my-1 text-success">{{ $currencySymbol }}{{ number_format($purchase_mtd, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Receipts × supplier cost</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class="bx bx-down-arrow-alt"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-warning h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Total purchase return</p>
                                    <h4 class="my-1 text-warning">{{ $currencySymbol }}{{ number_format($total_purchase_return, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Vendor returns not configured</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="bx bx-up-arrow-alt"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Today's sales</p>
                                    <h4 class="my-1 text-info">{{ $currencySymbol }}{{ number_format($today_sales, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Line totals posted today</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-scooter text-white ms-auto"><i class="bx bxs-cart"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">POS orders today</p>
                                    <h4 class="my-1 text-primary">{{ number_format($orders_today) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Checkouts</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-cosmic text-white ms-auto"><i class="bx bx-receipt"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Payments today</p>
                                    <h4 class="my-1 text-info">{{ $currencySymbol }}{{ number_format($payments_today, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Transactions</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-scooter text-white ms-auto"><i class="bx bx-money"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <a href="{{ route('inventory.low-stock') }}" class="text-decoration-none text-reset d-block h-100">
                        <div class="card radius-10 border-start border-0 border-3 border-danger h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-0 text-secondary">Low stock SKUs</p>
                                        <h4 class="my-1 text-danger">{{ number_format($low_stock_count) }}</h4>
                                        <p class="mb-0 font-13 text-muted">At or below alert →</p>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="bx bx-error-circle"></i></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100 radius-10">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Sales vs purchase (last 7 days)</h6>
                            <p class="small text-muted mb-0">POS sales value vs inbound purchase value (supplier price × qty received)</p>
                        </div>
                        <div class="card-body">
                            <canvas id="dashSalesPurchaseChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card radius-10 border-start border-0 border-3 border-dark h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Est. inventory value</p>
                            <h4 class="my-1 text-dark">{{ $currencySymbol }}{{ number_format($inventory_retail_value, 2) }}</h4>
                            <p class="mb-2 font-13 text-muted">Qty × retail price</p>
                            <p class="mb-0 text-secondary small">Avg. sale today</p>
                            <h6 class="text-secondary">{{ $currencySymbol }}{{ number_format($avg_order_value_today, 2) }}</h6>
                        </div>
                    </div>
                    <div class="card radius-10 border-start border-0 border-3 border-warning mt-3">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bx bx-calendar-event text-warning fs-4"></i>
                                <div>
                                    <h6 class="mb-0 small">Expiry watch (90 days)</h6>
                                    <p class="mb-0 text-muted small">{{ number_format($expiring_soon_count) }} product(s) expiring soon.</p>
                                </div>
                            </div>
                            <a href="{{ route('products.index') }}" class="btn btn-outline-warning btn-sm mt-2 w-100">Review products</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Low stock products</h6>
                            <a href="{{ route('inventory.low-stock') }}" class="small">View all</a>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Alert</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($low_stock_table as $row)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('products.inventory-history', $row) }}" class="text-decoration-none">{{ \Illuminate\Support\Str::limit($row->product_name, 36) }}</a>
                                                </td>
                                                <td class="text-end"><span class="badge bg-danger">{{ $row->quantity }}</span></td>
                                                <td class="text-end">{{ $row->stock_alert }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted small">No low-stock items.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Recent activity</h6>
                        </div>
                        <div class="card-body pt-2">
                            <ul class="nav nav-tabs nav-primary mb-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dashTabSales" type="button" role="tab">Sales</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dashTabPurchases" type="button" role="tab">Purchases</button>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="dashTabSales" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>When</th>
                                                    <th>Customer</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($recent_orders as $ord)
                                                    <tr>
                                                        <td class="text-nowrap small">{{ $ord->created_at->format('M j H:i') }}</td>
                                                        <td class="small">{{ $ord->name ?? 'Walk-in' }}</td>
                                                        <td class="text-end small">{{ $currencySymbol }}{{ number_format($ord->order_total ?? 0, 2) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="3" class="text-muted small text-center">No orders yet.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="{{ route('orders.index') }}" class="btn btn-link btn-sm px-0 mt-2">Open POS</a>
                                </div>
                                <div class="tab-pane fade" id="dashTabPurchases" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Received</th>
                                                    <th>Product</th>
                                                    <th class="text-end">Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($recent_receipts as $rec)
                                                    <tr>
                                                        <td class="text-nowrap small">{{ $rec->received_at->format('M j') }}</td>
                                                        <td class="small">{{ \Illuminate\Support\Str::limit($rec->product->product_name ?? '—', 28) }}</td>
                                                        <td class="text-end small">{{ $currencySymbol }}{{ number_format($rec->line_value ?? 0, 2) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="3" class="text-muted small text-center">No receipts yet.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="{{ route('inventory.receipts.index') }}" class="btn btn-link btn-sm px-0 mt-2">Receipt history</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card radius-10">
                <div class="card-body">
                    <h6 class="mb-3">Quick actions</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('orders.index') }}" class="btn btn-primary"><i class="bx bx-cart me-1"></i>POS / New sale</a>
                        <a href="{{ route('inventory.receive.create') }}" class="btn btn-success"><i class="bx bx-package me-1"></i>Receive stock</a>
                        <a href="{{ route('inventory.manage-stock') }}" class="btn btn-outline-primary"><i class="bx bx-cube me-1"></i>Manage stock</a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary"><i class="bx bx-list-ul me-1"></i>Product list</a>
                        <a href="{{ url('addproduct') }}" class="btn btn-outline-secondary"><i class="bx bx-plus-circle me-1"></i>Add product</a>
                        <a href="{{ route('reports.periodic') }}" class="btn btn-outline-secondary"><i class="bx bx-bar-chart-alt me-1"></i>Periodic report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('dashSalesPurchaseChart');
            if (!ctx || typeof Chart === 'undefined') return;
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($chart_labels ?? []),
                    datasets: [
                        {
                            label: 'Sales',
                            backgroundColor: 'rgba(13, 110, 253, 0.65)',
                            data: @json($chart_sales ?? []),
                        },
                        {
                            label: 'Purchase (cost)',
                            backgroundColor: 'rgba(25, 135, 84, 0.55)',
                            data: @json($chart_purchases ?? []),
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        yAxes: [{
                            ticks: { beginAtZero: true }
                        }]
                    },
                    legend: { position: 'bottom' },
                },
            });
        });
    </script>
@endsection
