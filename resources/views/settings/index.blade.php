@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Settings</div>
            </div>
            @include('inc.msg')
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <div class="card radius-10 h-100">
                        <div class="card-body">
                            <h5 class="card-title">Localization</h5>
                            <p class="text-muted small mb-3">Currency, language, timezone, and how dates and times appear across the app.</p>
                            <a href="{{ route('settings.localization') }}" class="btn btn-primary btn-sm">Open</a>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card radius-10 h-100">
                        <div class="card-body">
                            <h5 class="card-title">Notifications</h5>
                            <p class="text-muted small mb-3">In-app alerts and email opt-ins for future mailers.</p>
                            <a href="{{ route('settings.notifications') }}" class="btn btn-outline-primary btn-sm">Open</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
