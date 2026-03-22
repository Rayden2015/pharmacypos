@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Super Admin</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active">Platform</li>
                    </ol>
                </nav>
            </div>
        </div>

        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        <p class="text-muted small mb-3">Multi-tenant control: subscriptions, packages, and tenant (company) records. Staff hierarchy lives under each tenant (tenant admin → branches → branch manager → supervisor → cashier → officer).</p>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-primary h-100">
                    <div class="card-body py-3">
                        <p class="mb-0 text-secondary small">Tenants</p>
                        <h4 class="my-1 text-primary">{{ number_format($stats['tenants_total']) }}</h4>
                        <p class="mb-0 font-13 text-muted">{{ $stats['tenants_active'] }} active</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-success h-100">
                    <div class="card-body py-3">
                        <p class="mb-0 text-secondary small">Packages</p>
                        <h4 class="my-1 text-success">{{ number_format($stats['packages_active']) }} / {{ number_format($stats['packages_total']) }}</h4>
                        <p class="mb-0 font-13 text-muted">active / total</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-info h-100">
                    <div class="card-body py-3">
                        <p class="mb-0 text-secondary small">Subscriptions</p>
                        <h4 class="my-1 text-info">{{ number_format($stats['subscriptions_active']) }}</h4>
                        <p class="mb-0 font-13 text-muted">{{ $stats['subscriptions_expired'] }} expired</p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card radius-10 border-start border-0 border-3 border-warning h-100">
                    <div class="card-body py-3">
                        <p class="mb-0 text-secondary small">Payments (paid)</p>
                        <h4 class="my-1 text-warning">${{ number_format($stats['payments_sum'], 2) }}</h4>
                        <p class="mb-0 font-13 text-muted">{{ $stats['payments_unpaid'] }} unpaid invoices</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-body">
                <h6 class="mb-2">Quick actions</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('super-admin.companies.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add tenant</a>
                    <a href="{{ route('super-admin.packages.create') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-package me-1"></i>Add package</a>
                    <a href="{{ route('super-admin.subscriptions.create') }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-receipt me-1"></i>New subscription</a>
                    <a href="{{ route('super-admin.payments.create') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-credit-card me-1"></i>Record payment</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
