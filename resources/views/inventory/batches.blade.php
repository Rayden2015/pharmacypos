@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Batch management</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Batches</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                @include('inc.msg')
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-success btn-sm"><i class="bx bx-plus"></i> Receive stock</a>
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
                                        <th>Batch expiry</th>
                                        <th>Supplier</th>
                                        <th>Ref</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($batches as $b)
                                        <tr>
                                            <td class="text-nowrap">{{ $b->received_at->format('Y-m-d') }}</td>
                                            <td>{{ $b->product->product_name ?? '—' }}</td>
                                            <td class="text-end">{{ $b->quantity }}</td>
                                            <td>{{ $b->batch_number ?? '—' }}</td>
                                            <td class="text-nowrap">{{ $b->expiry_date ? $b->expiry_date->format('Y-m-d') : '—' }}</td>
                                            <td>{{ $b->supplier->supplier_name ?? '—' }}</td>
                                            <td><a href="{{ route('inventory.receipts.show', $b) }}">#{{ $b->id }}</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="text-center text-muted">No receipts yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $batches->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
