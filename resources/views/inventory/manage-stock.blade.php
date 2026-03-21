@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Manage stock</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manage stock</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                @include('inc.msg')
                <p class="text-muted small mb-3">Overview of on-hand quantities. Use <strong>Receive stock</strong> for inbound deliveries; <strong>Stock adjustment</strong> for counts and corrections.</p>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">On-hand</th>
                                        <th class="text-end">Alert</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($products as $product)
                                        <tr>
                                            <td>{{ $product->product_name }}</td>
                                            <td class="text-end fw-semibold">{{ $product->quantity }}</td>
                                            <td class="text-end">{{ $product->stock_alert }}</td>
                                            <td>
                                                @if ($product->stock_alert >= $product->quantity)
                                                    <span class="badge bg-danger">Low</span>
                                                @else
                                                    <span class="badge bg-success">OK</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('inventory.receive.create', ['product_id' => $product->id]) }}" class="btn btn-sm btn-outline-success">Receive</a>
                                                <a href="{{ route('products.inventory-history', $product) }}" class="btn btn-sm btn-outline-secondary">Log</a>
                                            </td>
                                        </tr>
                                    @endforeach
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
