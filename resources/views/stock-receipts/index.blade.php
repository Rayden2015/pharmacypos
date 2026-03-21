@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Receipt history</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ url('/home') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Receipts</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus"></i> Receive stock
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Received</th>
                                        <th>Product</th>
                                        <th class="text-end">Qty</th>
                                        <th>Lot</th>
                                        <th>Expiry</th>
                                        <th>Supplier</th>
                                        <th>Document</th>
                                        <th>Recorded by</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($receipts as $receipt)
                                        <tr>
                                            <td class="text-nowrap">{{ $receipt->received_at->format('Y-m-d') }}</td>
                                            <td>{{ $receipt->product->product_name ?? '—' }}</td>
                                            <td class="text-end">{{ $receipt->quantity }}</td>
                                            <td>{{ $receipt->batch_number ?? '—' }}</td>
                                            <td class="text-nowrap">{{ $receipt->expiry_date ? $receipt->expiry_date->format('Y-m-d') : '—' }}</td>
                                            <td>{{ $receipt->supplier->supplier_name ?? '—' }}</td>
                                            <td>{{ $receipt->document_reference ?? '—' }}</td>
                                            <td>{{ $receipt->user->name ?? '—' }}</td>
                                            <td class="text-nowrap">
                                                <a href="{{ route('inventory.receipts.show', $receipt) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No receipts yet. Use <strong>Receive stock</strong> to record inbound inventory.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $receipts->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
