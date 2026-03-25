@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Inventory logs</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('inventory.manage-stock') }}">Inventory</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Logs</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="text-muted small mb-0">
                    Ledger of stock movements: purchases (receive), POS sales, <strong>sales returns</strong>, adjustments, transfers, and opening balances.
                    Batch and storage/rack are optional—when not set in the catalog, those columns show an em dash.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-success btn-sm"><i class="bx bx-down-arrow-circle"></i> Receive stock</a>
                    <a href="{{ route('inventory.logs.export', request()->except('page')) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-download"></i> Export CSV</a>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="get" action="{{ route('inventory.logs') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-12 col-md-3">
                            <label class="form-label small mb-0">Search</label>
                            <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="SKU, name, rack, alias…" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-0">Branch</label>
                            <select name="site_id" class="form-select">
                                <option value="">All branches</option>
                                @foreach ($sites as $s)
                                    <option value="{{ $s->id }}" {{ (int) request('site_id', 0) === (int) $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}@if ($s->code) · {{ $s->code }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0">From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0">To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-0">Transaction type</label>
                            <select name="type" class="form-select">
                                <option value="all" {{ request('type', 'all') === 'all' ? 'selected' : '' }}>All types</option>
                                <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                                <option value="sales" {{ request('type') === 'sales' ? 'selected' : '' }}>Sales</option>
                                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                                <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                                <option value="opening" {{ request('type') === 'opening' ? 'selected' : '' }}>Opening balance</option>
                                <option value="return" {{ request('type') === 'return' ? 'selected' : '' }}>Sales return</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-0">Sort by</label>
                            <select name="sort" class="form-select">
                                <option value="date_desc" {{ request('sort', 'date_desc') === 'date_desc' ? 'selected' : '' }}>Newest first</option>
                                <option value="date_asc" {{ request('sort') === 'date_asc' ? 'selected' : '' }}>Oldest first</option>
                                <option value="product_asc" {{ request('sort') === 'product_asc' ? 'selected' : '' }}>Item A–Z</option>
                                <option value="sku_asc" {{ request('sort') === 'sku_asc' ? 'selected' : '' }}>SKU A–Z</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary mt-3 mt-md-0"><i class="bx bx-filter-alt"></i> Apply</button>
                            <a href="{{ route('inventory.logs') }}" class="btn btn-light mt-3 mt-md-0">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Item</th>
                                    <th>Batch no.</th>
                                    <th>Storage / rack</th>
                                    <th>Transaction type</th>
                                    <th class="text-end">Qty in</th>
                                    <th class="text-end">Qty out</th>
                                    <th class="text-end">Balance stock</th>
                                    <th>Reference ID</th>
                                    <th>Date &amp; time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($movements as $movement)
                                    <tr>
                                        <td class="text-nowrap">{{ $movement->product->sku ?: ($movement->product->item_code ?: '—') }}</td>
                                        <td>
                                            <a href="{{ route('products.inventory-history', $movement->product) }}" class="text-decoration-none">
                                                {{ $movement->product->product_name }}
                                            </a>
                                        </td>
                                        <td class="text-nowrap">{{ $movement->batchDisplay() }}</td>
                                        <td class="text-muted small">{{ $movement->product->rack_location ?: '—' }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $movement->transactionTypeLabel() }}</span>
                                        </td>
                                        <td class="text-end">{{ $movement->quantityInDisplay() ?: '—' }}</td>
                                        <td class="text-end">{{ $movement->quantityOutDisplay() ?: '—' }}</td>
                                        <td class="text-end fw-semibold">{{ $movement->quantity_after }}</td>
                                        <td class="text-nowrap font-monospace small">{{ $movement->referenceDisplay() }}</td>
                                        <td class="text-nowrap">{{ $movement->created_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">No movements match these filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                        <p class="text-muted small mb-0">
                            @if ($movements->total() > 0)
                                Showing {{ $movements->firstItem() }}–{{ $movements->lastItem() }} of {{ number_format($movements->total()) }} lines
                            @endif
                        </p>
                        {{ $movements->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
