@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Units</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Units</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-4">
                        <p class="mb-2">Units of measure and pack size are set per product as <strong>Unit of measure</strong> and <strong>Volume / pack size</strong>.</p>
                        <p class="text-muted small mb-0">These drive POS line labels and receipts. A global units list can be added later.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm mt-3">Go to products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
