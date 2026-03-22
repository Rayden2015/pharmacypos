@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Custom domain</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')
        <div class="card radius-10">
            <div class="card-body">
                <h5 class="mb-2">Domain mapping</h5>
                <p class="text-muted mb-0">Per-tenant hostnames (e.g. <code>tenant.yourapp.com</code>) and DNS verification will be wired here — similar to the Dreams POS “Domain” screen under Super Admin.</p>
            </div>
        </div>
    </div>
</div>
@endsection
