@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Sales returns</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">POS</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Returns</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            <div class="card">
                <div class="card-body">
                    <p class="text-muted small">Choose an invoice to return items to stock. Quantities cannot exceed what was sold and not yet returned.</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Branch</th>
                                    <th>Customer</th>
                                    <th class="text-end">Prior returns</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($orders as $o)
                                    <tr>
                                        <td class="text-nowrap fw-semibold">#ORD-{{ str_pad((string) $o->id, 5, '0', STR_PAD_LEFT) }}</td>
                                        <td class="text-nowrap small">{{ $o->created_at->format('d M Y H:i') }}</td>
                                        <td class="small">{{ $o->site?->name ?? '—' }}</td>
                                        <td>{{ $o->name ?: '—' }} @if($o->mobile)<span class="text-muted small">{{ $o->mobile }}</span>@endif</td>
                                        <td class="text-end">{{ (int) $o->sale_returns_count }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('sales.returns.create', $o) }}" class="btn btn-sm btn-outline-primary">Return items</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-muted py-4 text-center">No orders found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $orders->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
