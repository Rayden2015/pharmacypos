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

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-2">
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
                                    <p class="mb-0 font-13 text-muted">Completed checkouts</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-cosmic text-white ms-auto"><i class="bx bx-receipt"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-danger h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Products in catalog</p>
                                    <h4 class="my-1 text-danger">{{ number_format($total_products) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Active SKUs</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-bloody text-white ms-auto"><i class="bx bx-package"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <a href="{{ route('products.index') }}" class="text-decoration-none text-reset d-block h-100">
                        <div class="card radius-10 border-start border-0 border-3 border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <p class="mb-0 text-secondary">Low stock alerts</p>
                                        <h4 class="my-1 text-warning">{{ number_format($low_stock_count) }}</h4>
                                        <p class="mb-0 font-13 text-muted">Qty at or below alert level →</p>
                                    </div>
                                    <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white ms-auto"><i class="bx bx-error-circle"></i></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Month-to-date sales</p>
                                    <h4 class="my-1 text-success">{{ $currencySymbol }}{{ number_format($month_sales, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">{{ now()->format('F Y') }}</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-ohhappiness text-white ms-auto"><i class="bx bx-trending-up"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Payments collected today</p>
                                    <h4 class="my-1 text-info">{{ $currencySymbol }}{{ number_format($payments_today, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">From transaction records</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-scooter text-white ms-auto"><i class="bx bx-money"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-secondary h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Avg. sale today</p>
                                    <h4 class="my-1 text-secondary">{{ $currencySymbol }}{{ number_format($avg_order_value_today, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Per POS order today</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-moonlit text-white ms-auto"><i class="bx bx-bar-chart-alt-2"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-dark h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-0 text-secondary">Est. inventory value</p>
                                    <h4 class="my-1 text-dark">{{ $currencySymbol }}{{ number_format($inventory_retail_value, 2) }}</h4>
                                    <p class="mb-0 font-13 text-muted">Qty × retail price</p>
                                </div>
                                <div class="widgets-icons-2 rounded-circle bg-gradient-deepblue text-white ms-auto"><i class="bx bx-cube-alt"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <div class="card radius-10 border-start border-0 border-3 border-warning">
                        <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="widgets-icons-2 rounded-circle bg-gradient-blooker text-white"><i class="bx bx-calendar-event"></i></div>
                                <div>
                                    <h6 class="mb-0">Expiry watch (90 days)</h6>
                                    <p class="mb-0 text-muted small">{{ number_format($expiring_soon_count) }} product(s) with batch expiry in the next 90 days — review stocking and promos.</p>
                                </div>
                            </div>
                            <a href="{{ route('products.index') }}" class="btn btn-outline-warning btn-sm">Review products</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card radius-10 mt-3">
                <div class="card-body">
                    <h6 class="mb-3">Quick actions</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('orders.index') }}" class="btn btn-primary"><i class="bx bx-cart me-1"></i>POS / New sale</a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary"><i class="bx bx-list-ul me-1"></i>Product list</a>
                        <a href="{{ url('addproduct') }}" class="btn btn-outline-primary"><i class="bx bx-plus-circle me-1"></i>Add product</a>
                        <a href="{{ url('grid') }}" class="btn btn-outline-secondary"><i class="bx bx-grid-alt me-1"></i>Grid view</a>
                        <a href="{{ route('reports.periodic') }}" class="btn btn-outline-secondary"><i class="bx bx-bar-chart-alt me-1"></i>Periodic report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
