@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Receipt #{{ $stockReceipt->id }}</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ url('/home') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('inventory.receipts.index') }}">Receipts</a></li>
                                <li class="breadcrumb-item active" aria-current="page">#{{ $stockReceipt->id }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-primary btn-sm">New receipt</a>
                    <a href="{{ route('products.inventory-history', $stockReceipt->product_id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bx bx-history"></i> Stock log for this product
                    </a>
                </div>

                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">{{ $stockReceipt->product->product_name ?? 'Product' }}</h5>
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Quantity received</dt>
                            <dd class="col-sm-9">{{ $stockReceipt->quantity }}</dd>
                            <dt class="col-sm-3">Batch / lot</dt>
                            <dd class="col-sm-9">{{ $stockReceipt->batch_number ?? '—' }}</dd>
                            <dt class="col-sm-3">Batch expiry</dt>
                            <dd class="col-sm-9">{{ $stockReceipt->expiry_date ? $stockReceipt->expiry_date->format('Y-m-d') : '—' }}</dd>
                            <dt class="col-sm-3">Supplier</dt>
                            <dd class="col-sm-9">{{ $stockReceipt->supplier->supplier_name ?? '—' }}</dd>
                            <dt class="col-sm-3">Document reference</dt>
                            <dd class="col-sm-9">{{ $stockReceipt->document_reference ?? '—' }}</dd>
                            <dt class="col-sm-3">Received date</dt>
                            <dd class="col-sm-9">{{ $stockReceipt->received_at->format('Y-m-d') }}</dd>
                            <dt class="col-sm-3">Recorded by</dt>
                            <dd class="col-sm-9">{{ $stockReceipt->user->name ?? '—' }}</dd>
                            <dt class="col-sm-3">Ledger movement</dt>
                            <dd class="col-sm-9">
                                @if ($stockReceipt->inventoryMovement)
                                    Before {{ $stockReceipt->inventoryMovement->quantity_before }}
                                    → +{{ $stockReceipt->inventoryMovement->quantity_delta }}
                                    → after {{ $stockReceipt->inventoryMovement->quantity_after }}
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="col-sm-3">Notes</dt>
                            <dd class="col-sm-9">@if ($stockReceipt->notes){!! nl2br(e($stockReceipt->notes)) !!}@else — @endif</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
