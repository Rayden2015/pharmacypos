@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Batch management</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('inventory.manage-stock') }}">Inventory</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Batches</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="text-muted small mb-0">Lot-level inbound receipts (from <strong>Receive stock</strong>). Filter by branch, received dates, product name, and expiry status.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-success btn-sm"><i class="bx bx-plus"></i> Receive stock</a>
                    <a href="{{ route('inventory.receipts.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-list-ul"></i> All receipts</a>
                    <a href="{{ route('inventory.batches.export', request()->except('page')) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-download"></i> Export CSV</a>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-3 mb-4">
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">Lines (filtered)</p>
                            <h4 class="my-1 text-primary">{{ number_format($stats['total'] ?? 0) }}</h4>
                            <p class="mb-0 font-13 text-muted">Matching search &amp; dates</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-danger h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">Expired lots</p>
                            <h4 class="my-1 text-danger">{{ number_format($stats['expired'] ?? 0) }}</h4>
                            <p class="mb-0 font-13 text-muted">Batch expiry &lt; today</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-warning h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">Expiring ≤90 days</p>
                            <h4 class="my-1 text-warning">{{ number_format($stats['expiring_90'] ?? 0) }}</h4>
                            <p class="mb-0 font-13 text-muted">Plan rotation / FEFO</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-success h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">OK (&gt;90 days)</p>
                            <h4 class="my-1 text-success">{{ number_format($stats['ok'] ?? 0) }}</h4>
                            <p class="mb-0 font-13 text-muted">Lot expiry dated</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 border-start border-0 border-3 border-secondary h-100">
                        <div class="card-body py-3">
                            <p class="mb-0 text-secondary small">No lot expiry</p>
                            <h4 class="my-1 text-secondary">{{ number_format($stats['no_expiry'] ?? 0) }}</h4>
                            <p class="mb-0 font-13 text-muted">Optional on receipt</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="get" action="{{ route('inventory.batches') }}" class="row g-2 align-items-end mb-4">
                        <div class="col-12 col-md-3">
                            <label class="form-label small mb-0">Product</label>
                            <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Search name…" autocomplete="off">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-0">Branch</label>
                            <select name="site_id" class="form-select">
                                <option value="">All branches</option>
                                @foreach ($sites as $s)
                                    <option value="{{ $s->id }}" {{ (int) request('site_id', 0) === (int) $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}@if($s->code) · {{ $s->code }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0">Received from</label>
                            <input type="date" name="received_from" class="form-control" value="{{ request('received_from') }}">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0">Received to</label>
                            <input type="date" name="received_to" class="form-control" value="{{ request('received_to') }}">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label small mb-0">Lot expiry</label>
                            <select name="expiry" class="form-select">
                                <option value="all" {{ ($expiry ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="expired" {{ ($expiry ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="expiring_90" {{ ($expiry ?? '') === 'expiring_90' ? 'selected' : '' }}>Expiring ≤90d</option>
                                <option value="ok" {{ ($expiry ?? '') === 'ok' ? 'selected' : '' }}>OK (&gt;90d)</option>
                                <option value="no_expiry" {{ ($expiry ?? '') === 'no_expiry' ? 'selected' : '' }}>No date</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Received</th>
                                    <th>Branch</th>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th>Lot</th>
                                    <th>Batch expiry</th>
                                    <th>Status</th>
                                    <th>Supplier</th>
                                    <th>Received by</th>
                                    <th>Ref</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($batches as $b)
                                    @php
                                        $ls = $b->lot_status ?? ['label' => '—', 'badge' => 'bg-secondary'];
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap small">{{ $b->received_at->format('d M Y') }}</td>
                                        <td class="small">{{ $b->site ? $b->site->name : '—' }}</td>
                                        <td>{{ $b->product->product_name ?? '—' }}</td>
                                        <td class="text-end">{{ number_format((int) $b->quantity) }}</td>
                                        <td class="text-nowrap">{{ $b->batch_number ?: '—' }}</td>
                                        <td class="text-nowrap small">{{ $b->expiry_date ? $b->expiry_date->format('d M Y') : '—' }}</td>
                                        <td><span class="badge rounded-pill {{ $ls['badge'] }}">{{ $ls['label'] }}</span></td>
                                        <td class="small">{{ $b->supplier->supplier_name ?? '—' }}</td>
                                        <td class="small">{{ $b->user->name ?? '—' }}</td>
                                        <td class="small">
                                            <a href="{{ route('inventory.receipts.show', $b) }}">#{{ $b->id }}</a>
                                            @if ($b->document_reference)
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">{{ $b->document_reference }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center text-muted py-4">No batch lines match these filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $batches->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
