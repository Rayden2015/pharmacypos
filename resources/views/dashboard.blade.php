@extends('layouts.dash')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Pharmacy dashboard</div>
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
                    <p class="text-muted small mb-0">{{ now()->format('l, F j, Y') }} · KPIs use POS sales lines &amp; purchase receipts (supplier cost × qty).</p>
                    @if (!empty($dashboard_all_sites))
                        <p class="small text-primary mb-0 mt-1"><i class="bx bx-globe me-1"></i>Dashboard metrics: <strong>{{ $dashboard_site_label ?? 'All sites' }}</strong> — POS and stock still use the branch selected in the header.</p>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    @if (!auth()->user()->isSuperAdmin())
                    <a href="{{ route('orders.index') }}" class="btn btn-primary btn-sm"><i class="bx bx-cart me-1"></i>POS</a>
                    @else
                    <a href="{{ route('super-admin.dashboard') }}" class="btn btn-primary btn-sm"><i class="bx bx-shield-quarter me-1"></i>Platform</a>
                    @endif
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-success btn-sm"><i class="bx bx-package me-1"></i>Receive stock</a>
                    @can('reports.export')
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Export</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('dashboard.export') }}"><i class="bx bx-download me-1"></i>Summary CSV</a></li>
                        </ul>
                    </div>
                    @endcan
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

            {{-- Top KPI row (pharmacy / DreamsPOS style) --}}
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3 mb-3">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">Sales (30 days)</p>
                            <h4 class="my-1 text-primary">{{ $currencySymbol }}{{ number_format($sales_last_30 ?? 0, 2) }}</h4>
                            <p class="mb-0 font-13">
                                @if (($sales_30d_pct ?? null) !== null)
                                    <span class="@if(($sales_30d_pct ?? 0) >= 0) text-success @else text-danger @endif">{{ ($sales_30d_pct ?? 0) >= 0 ? '+' : '' }}{{ number_format($sales_30d_pct, 1) }}%</span>
                                    <span class="text-muted">vs prior 30 days</span>
                                @else
                                    <span class="text-muted">— vs prior 30 days</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">Purchases (30 days)</p>
                            <h4 class="my-1 text-success">{{ $currencySymbol }}{{ number_format($purchase_last_30 ?? 0, 2) }}</h4>
                            <p class="mb-0 font-13">
                                @if (($purchase_30d_pct ?? null) !== null)
                                    <span class="@if(($purchase_30d_pct ?? 0) >= 0) text-success @else text-danger @endif">{{ ($purchase_30d_pct ?? 0) >= 0 ? '+' : '' }}{{ number_format($purchase_30d_pct, 1) }}%</span>
                                    <span class="text-muted">vs prior 30 days</span>
                                @else
                                    <span class="text-muted">— vs prior 30 days</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">Medicines (SKUs)</p>
                            <h4 class="my-1 text-info">{{ number_format($total_products) }}</h4>
                            <p class="mb-0 font-13 text-muted">Catalog products</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <a href="{{ route('pharmacy.prescriptions') }}" class="text-decoration-none text-reset d-block h-100">
                        <div class="card radius-10 border-start border-0 border-3 border-secondary h-100">
                            <div class="card-body py-3">
                                <p class="mb-0 text-secondary small">Prescriptions</p>
                                <h4 class="my-1 text-secondary">{{ number_format($prescriptions_last_30 ?? 0) }}</h4>
                                <p class="mb-0 font-13 text-muted">New Rx logged (last 30 days) →</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('inventory.low-stock') }}" class="text-decoration-none text-reset d-block h-100">
                        <div class="card radius-10 border-start border-0 border-3 border-danger h-100">
                            <div class="card-body py-3">
                                <p class="mb-0 text-secondary small">Low stock alerts</p>
                                <h4 class="my-1 text-danger">{{ number_format($low_stock_count) }}</h4>
                                <p class="mb-0 font-13 text-muted">At or below threshold →</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Total sales (MTD)</p>
                            <h4 class="my-1 text-primary">{{ $currencySymbol }}{{ number_format($month_sales, 2) }}</h4>
                            @if (($sales_mom_pct ?? null) !== null)
                                <p class="mb-0 font-13"><span class="@if($sales_mom_pct >= 0) text-success @else text-danger @endif">{{ $sales_mom_pct >= 0 ? '+' : '' }}{{ number_format($sales_mom_pct, 1) }}%</span> <span class="text-muted">vs last month total</span></p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Total purchase (MTD)</p>
                            <h4 class="my-1 text-success">{{ $currencySymbol }}{{ number_format($purchase_mtd, 2) }}</h4>
                            @if (($purchase_mom_pct ?? null) !== null)
                                <p class="mb-0 font-13"><span class="@if($purchase_mom_pct >= 0) text-success @else text-danger @endif">{{ $purchase_mom_pct >= 0 ? '+' : '' }}{{ number_format($purchase_mom_pct, 1) }}%</span> <span class="text-muted">vs last month total</span></p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-secondary h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Sales / purchase returns</p>
                            <h4 class="my-1 text-secondary">{{ $currencySymbol }}{{ number_format($total_sales_return + $total_purchase_return, 2) }}</h4>
                            <p class="mb-0 font-13 text-muted">Return workflows not configured</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-warning h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Expiry watch (90 days)</p>
                            <h4 class="my-1 text-warning">{{ number_format($expiring_soon_count) }}</h4>
                            <a href="{{ route('inventory.expiry-tracking') }}" class="small">Expiry tracking →</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-8">
                    <div class="card h-100 radius-10">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Sales vs purchase</h6>
                            <p class="small text-muted mb-0">Last 7 days — POS revenue vs inbound cost</p>
                        </div>
                        <div class="card-body">
                            <canvas id="dashSalesPurchaseChart" height="110"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Medicine stock status</h6>
                            <p class="small text-muted mb-0">On-hand vs alert level</p>
                        </div>
                        <div class="card-body">
                            <canvas id="dashStockStatusChart" height="200"></canvas>
                            <ul class="list-unstyled small mb-0 mt-2">
                                <li><span class="badge bg-danger me-1">&nbsp;</span> Out of stock: <strong>{{ number_format($stock_out_count) }}</strong></li>
                                <li><span class="badge bg-warning text-dark me-1">&nbsp;</span> Low: <strong>{{ number_format($stock_low_only_count ?? $stock_low_count) }}</strong></li>
                                <li><span class="badge bg-success me-1">&nbsp;</span> Available: <strong>{{ number_format($stock_available_count) }}</strong></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Weekly sales</h6>
                            <p class="small text-muted mb-0">Last 7 days ({{ $currencySymbol }})</p>
                        </div>
                        <div class="card-body">
                            <canvas id="dashWeeklySalesChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Inventory by form (dosage form)</h6>
                            <p class="small text-muted mb-0">Top categories in catalog</p>
                        </div>
                        <div class="card-body">
                            <canvas id="dashFormChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-5">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-danger">Expired (catalog date)</h6>
                            <a href="{{ route('inventory.expiry-tracking') }}" class="small">View all</a>
                        </div>
                        <div class="card-body pt-0">
                            @forelse ($expired_products as $ex)
                                <div class="border border-danger rounded p-2 mb-2 bg-light">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="small fw-semibold">{{ \Illuminate\Support\Str::limit($ex->product_name, 42) }}</span>
                                        <span class="small text-nowrap text-danger">{{ $ex->expiredate }}</span>
                                    </div>
                                    <span class="small text-muted">Qty {{ $ex->quantity }}</span>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No expired dates in catalog.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Near expiry (by month)</h6>
                            <p class="small text-muted mb-0">Count of products expiring in each month window</p>
                        </div>
                        <div class="card-body">
                            <canvas id="dashNearExpiryChart" height="140"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Invoice summary (MTD)</h6>
                            <p class="small text-muted mb-0">From POS lines vs payments recorded</p>
                        </div>
                        <div class="card-body">
                            <p class="mb-1 small text-secondary">Invoiced (POS lines, MTD)</p>
                            <h5 class="text-primary">{{ $currencySymbol }}{{ number_format($month_sales, 2) }}</h5>
                            <p class="mb-0 small text-muted">{{ number_format($orders_mtd_count ?? 0) }} orders</p>
                            <p class="mb-1 small text-secondary mt-3">Collected (payments recorded, MTD)</p>
                            <h5 class="text-success">{{ $currencySymbol }}{{ number_format($month_tx_paid, 2) }}</h5>
                            <p class="mb-0 small text-muted">{{ number_format($transactions_paid_mtd_count ?? 0) }} payment rows</p>
                            <p class="mb-1 small text-secondary mt-3">Outstanding (MTD balances)</p>
                            <h5 class="text-warning">{{ $currencySymbol }}{{ number_format($invoice_due, 2) }}</h5>
                            <p class="mb-0 small text-muted">{{ number_format($transactions_with_balance_mtd ?? 0) }} sale(s) with balance</p>
                            @if (($ar_open_total ?? 0) > 0)
                                <p class="mb-0 small text-secondary mt-3">All open AR</p>
                                <p class="mb-0 small"><strong>{{ $currencySymbol }}{{ number_format($ar_open_total, 2) }}</strong> <span class="text-muted">(any month)</span></p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Payment mix (this month)</h6>
                        </div>
                        <div class="card-body">
                            @forelse ($payment_methods_pct as $pm)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small">{{ $pm->payment_method ?? '—' }}</span>
                                    <span class="small">{{ $currencySymbol }}{{ number_format((float) $pm->total, 2) }} <span class="text-muted">({{ number_format($pm->pct, 1) }}%)</span></span>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No payments this month.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card radius-10 h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="mb-0">Prescription status</h6>
                            <p class="small text-muted mb-0">All logged prescriptions</p>
                        </div>
                        <div class="card-body text-center">
                            <canvas id="dashRxChart" height="160"></canvas>
                            <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-outline-secondary btn-sm mt-2">Manage prescriptions</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Today's sales</p>
                            <h4 class="my-1 text-info">{{ $currencySymbol }}{{ number_format($today_sales, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">POS orders today</p>
                            <h4 class="my-1 text-primary">{{ number_format($orders_today) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Payments today</p>
                            <h4 class="my-1 text-info">{{ $currencySymbol }}{{ number_format($payments_today, 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-dark h-100">
                        <div class="card-body">
                            <p class="mb-0 text-secondary">Est. inventory value</p>
                            <h4 class="my-1 text-dark">{{ $currencySymbol }}{{ number_format($inventory_retail_value, 2) }}</h4>
                            <p class="mb-0 small text-muted">Avg sale today: {{ $currencySymbol }}{{ number_format($avg_order_value_today, 2) }}</p>
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
                                                    <th>Pay</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($recent_orders as $ord)
                                                    <tr>
                                                        <td class="text-nowrap small">{{ $ord->created_at->format('M j H:i') }}</td>
                                                        <td class="small">{{ $ord->name ?? 'Walk-in' }}</td>
                                                        <td class="text-end small">{{ $currencySymbol }}{{ number_format($ord->order_total ?? 0, 2) }}</td>
                                                        <td class="small"><span class="badge bg-light text-dark">{{ $ord->payment_label ?? '—' }}</span></td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-muted small text-center">No orders yet.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    @if (!auth()->user()->isSuperAdmin())
                                    <a href="{{ route('orders.index') }}" class="btn btn-link btn-sm px-0 mt-2">Open POS</a>
                                    @endif
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
                        @if (!auth()->user()->isSuperAdmin())
                            @can('pos.access')
                            <a href="{{ route('orders.index') }}" class="btn btn-primary"><i class="bx bx-cart me-1"></i>POS / New sale</a>
                            @endcan
                        @else
                        <a href="{{ route('super-admin.dashboard') }}" class="btn btn-primary"><i class="bx bx-shield-quarter me-1"></i>Platform dashboard</a>
                        @endif
                        <a href="{{ route('inventory.receive.create') }}" class="btn btn-success"><i class="bx bx-package me-1"></i>Receive stock</a>
                        <a href="{{ route('inventory.manage-stock') }}" class="btn btn-outline-primary"><i class="bx bx-cube me-1"></i>Manage stock</a>
                        <a href="{{ route('inventory.batches') }}" class="btn btn-outline-primary"><i class="bx bx-layer me-1"></i>Batch management</a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary"><i class="bx bx-list-ul me-1"></i>Product list</a>
                        <a href="{{ url('addproduct') }}" class="btn btn-outline-secondary"><i class="bx bx-plus-circle me-1"></i>Add product</a>
                        @can('reports.view')
                        <a href="{{ route('reports.periodic') }}" class="btn btn-outline-secondary"><i class="bx bx-bar-chart-alt me-1"></i>Periodic report</a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            var labels7 = @json($chart_labels ?? []);
            var sales7 = @json($chart_sales ?? []);
            var purch7 = @json($chart_purchases ?? []);

            var ctx1 = document.getElementById('dashSalesPurchaseChart');
            if (ctx1) {
                new Chart(ctx1.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels7,
                        datasets: [
                            {
                                label: 'Sales',
                                borderColor: 'rgba(13, 110, 253, 1)',
                                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                                data: sales7,
                                fill: true,
                                lineTension: 0.2,
                            },
                            {
                                label: 'Purchase (cost)',
                                borderColor: 'rgba(255, 140, 0, 1)',
                                backgroundColor: 'rgba(255, 140, 0, 0.08)',
                                data: purch7,
                                fill: true,
                                lineTension: 0.2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        scales: { yAxes: [{ ticks: { beginAtZero: true } }] },
                        legend: { position: 'bottom' },
                    },
                });
            }

            var ctxStock = document.getElementById('dashStockStatusChart');
            if (ctxStock) {
                new Chart(ctxStock.getContext('2d'), {
                    type: 'horizontalBar',
                    data: {
                        labels: ['Out of stock', 'Low', 'Available'],
                        datasets: [{
                            label: 'SKUs',
                            backgroundColor: ['rgba(220, 53, 69, 0.75)', 'rgba(255, 193, 7, 0.85)', 'rgba(25, 135, 84, 0.75)'],
                            data: [
                                {{ (int) $stock_out_count }},
                                {{ (int) ($stock_low_only_count ?? $stock_low_count) }},
                                {{ (int) $stock_available_count }},
                            ],
                        }],
                    },
                    options: {
                        responsive: true,
                        legend: { display: false },
                        scales: { xAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] },
                    },
                });
            }

            var ctxW = document.getElementById('dashWeeklySalesChart');
            if (ctxW) {
                new Chart(ctxW.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($weekly_sales_labels ?? []),
                        datasets: [{
                            label: 'Sales',
                            backgroundColor: 'rgba(13, 110, 253, 0.65)',
                            data: @json($weekly_sales_values ?? []),
                        }],
                    },
                    options: {
                        responsive: true,
                        legend: { display: false },
                        scales: { yAxes: [{ ticks: { beginAtZero: true } }] },
                    },
                });
            }

            var formLabels = @json($inventory_by_form->pluck('form'));
            var formCounts = @json($inventory_by_form->pluck('c'));
            var ctxF = document.getElementById('dashFormChart');
            if (ctxF && formLabels.length) {
                new Chart(ctxF.getContext('2d'), {
                    type: 'horizontalBar',
                    data: {
                        labels: formLabels,
                        datasets: [{
                            label: 'Count',
                            backgroundColor: 'rgba(111, 66, 193, 0.65)',
                            data: formCounts,
                        }],
                    },
                    options: {
                        responsive: true,
                        legend: { display: false },
                        scales: { xAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] },
                    },
                });
            }

            var ctxN = document.getElementById('dashNearExpiryChart');
            if (ctxN) {
                new Chart(ctxN.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @json($near_expiry_labels ?? []),
                        datasets: [{
                            label: 'Products expiring',
                            backgroundColor: 'rgba(255, 193, 7, 0.7)',
                            data: @json($near_expiry_counts ?? []),
                        }],
                    },
                    options: {
                        responsive: true,
                        legend: { display: false },
                        scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] },
                    },
                });
            }

            var ctxRx = document.getElementById('dashRxChart');
            if (ctxRx) {
                var rxDone = {{ (int) ($rx_completed ?? 0) }};
                var rxWait = {{ (int) ($rx_pending ?? 0) }};
                var rxX = {{ (int) ($rx_cancelled ?? 0) }};
                new Chart(ctxRx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'Pending', 'Cancelled'],
                        datasets: [{
                            data: [rxDone, rxWait, rxX],
                            backgroundColor: ['#198754', '#0dcaf0', '#dc3545'],
                        }],
                    },
                    options: {
                        responsive: true,
                        legend: { position: 'bottom' },
                    },
                });
            }
        });
    </script>
@endsection
