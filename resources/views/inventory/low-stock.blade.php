@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Low stock</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Low stocks</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                @include('inc.msg')
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="text-muted small mb-0">Products where on-hand quantity is at or below the alert threshold.</p>
                    <a href="{{ route('inventory.receive.create') }}" class="btn btn-success btn-sm"><i class="bx bx-package"></i> Receive stock</a>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Alias</th>
                                        <th class="text-end">On-hand</th>
                                        <th class="text-end">Alert at</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($products as $product)
                                        <tr>
                                            <td>{{ $product->product_name }}</td>
                                            <td>{{ $product->alias ?? '—' }}</td>
                                            <td class="text-end"><span class="badge bg-danger">{{ $product->quantity }}</span></td>
                                            <td class="text-end">{{ $product->stock_alert }}</td>
                                            <td class="text-nowrap">
                                                <a href="{{ route('inventory.receive.create', ['product_id' => $product->id]) }}" class="btn btn-sm btn-outline-success">Add stock</a>
                                                <a href="{{ route('products.inventory-history', $product) }}" class="btn btn-sm btn-outline-secondary">Stock log</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No low-stock items right now.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $products->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
