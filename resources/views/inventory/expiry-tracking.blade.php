@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Expiry tracking</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Expiry</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                @include('inc.msg')
                <p class="text-muted small mb-3">Uses each product’s <strong>Expire date</strong> (catalog batch).@can('inventory.view') For lot-level expiry from receipts, see <a href="{{ route('inventory.batches') }}">Batch management</a>.@endcan</p>

                <h6 class="text-danger mb-2">Expired (remove from sale / quarantine)</h6>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Product</th><th class="text-end">Qty</th><th>Expired</th></tr></thead>
                                <tbody>
                                    @forelse ($expired as $p)
                                        <tr>
                                            <td><a href="{{ route('products.inventory-history', $p) }}">{{ $p->product_name }}</a></td>
                                            <td class="text-end">{{ $p->quantity }}</td>
                                            <td class="text-nowrap text-danger">{{ $p->expiredate }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-muted text-center">No expired catalog dates.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $expired->links() }}
                    </div>
                </div>

                <h6 class="text-warning mb-2">Near expiry (next 90 days)</h6>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead><tr><th>Product</th><th class="text-end">Qty</th><th>Expires</th></tr></thead>
                                <tbody>
                                    @forelse ($nearExpiry as $p)
                                        <tr>
                                            <td><a href="{{ route('products.inventory-history', $p) }}">{{ $p->product_name }}</a></td>
                                            <td class="text-end">{{ $p->quantity }}</td>
                                            <td class="text-nowrap">{{ $p->expiredate }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-muted text-center">None in this window.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        {{ $nearExpiry->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
