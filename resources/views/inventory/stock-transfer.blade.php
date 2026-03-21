@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Stock transfer</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Stock transfer</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-5 text-center">
                        <div class="mb-3">
                            <i class="bx bx-git-compare text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="fw-semibold">Multi-location transfers</h5>
                        <p class="text-muted mx-auto mb-4" style="max-width: 32rem;">
                            This pharmacy app currently tracks a single store stock level. Stock transfers between warehouses or branches
                            (move quantity from Location A to B) can be added when you run multiple locations.
                        </p>
                        <a href="{{ route('inventory.manage-stock') }}" class="btn btn-primary">Back to manage stock</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
