@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Brands</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Brands</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-4">
                        <p class="mb-2">Manufacturer / brand is stored on each product as <strong>Manufacturer</strong> (same field used in POS and reports).</p>
                        <p class="text-muted small mb-0">A dedicated brand directory can be added later for filtering and analytics.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm mt-3">Go to products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
