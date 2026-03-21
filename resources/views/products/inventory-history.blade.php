@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Inventory history</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ url('/home') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ $product->product_name }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                            <div>
                                <h5 class="card-title mb-1">{{ $product->product_name }}</h5>
                                <p class="mb-0 text-muted">
                                    Current on-hand: <strong>{{ $product->quantity }}</strong>
                                    @if ($product->alias)
                                        · Alias: {{ $product->alias }}
                                    @endif
                                </p>
                                <p class="small text-muted mb-0 mt-2">
                                    Each line is one change. <em>Before</em> and <em>after</em> are balances; <em>change</em> is the delta (+ restock, − reduction).
                                    Use <strong>Receive stock</strong> for inbound deliveries with batch and supplier details.
                                </p>
                            </div>
                            <a href="{{ route('inventory.receive.create', ['product_id' => $product->id]) }}" class="btn btn-primary btn-sm shrink-0">
                                <i class="bx bx-package"></i> Receive stock
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>Type</th>
                                        <th class="text-end">Before</th>
                                        <th class="text-end">Change</th>
                                        <th class="text-end">After</th>
                                        <th>Note</th>
                                        <th>Receipt</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($movements as $movement)
                                        <tr>
                                            <td class="text-nowrap">{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                            <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $movement->change_type) }}</span></td>
                                            <td class="text-end">{{ $movement->quantity_before ?? '—' }}</td>
                                            <td class="text-end @if ($movement->quantity_delta > 0) text-success @elseif ($movement->quantity_delta < 0) text-danger @endif">
                                                @if ($movement->quantity_delta > 0)+@endif{{ $movement->quantity_delta }}
                                            </td>
                                            <td class="text-end fw-bold">{{ $movement->quantity_after }}</td>
                                            <td>{{ $movement->note ?? '—' }}</td>
                                            <td class="text-nowrap">
                                                @if ($movement->stock_receipt_id)
                                                    <a href="{{ route('inventory.receipts.show', $movement->stock_receipt_id) }}">#{{ $movement->stock_receipt_id }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $movement->user->name ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No movements recorded yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $movements->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
