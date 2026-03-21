@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Categories</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Categories</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-4">
                        <p class="mb-2">Product <strong>form</strong> (e.g. Tablet, Capsule, Syrup) is set on each product when you add or edit it.</p>
                        <p class="text-muted small mb-0">A dedicated category master list can be added later if you need reporting by product class.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm mt-3">Go to products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
