@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Notifications</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Notifications</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            <div class="row">
                <div class="col-12 col-xl-8">
                    <div class="card radius-10">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Notification preferences</h5>
                            <p class="text-muted small mb-4">Choose how you want to be notified. In-app options apply to this account only. Email options are stored for when your organization enables automated mail (low stock, expiry, digests).</p>

                            <form action="{{ route('settings.notifications.update') }}" method="post" class="notification-settings-form">
                                @csrf
                                @method('put')

                                @if (auth()->user()->canUseTenantCommunications())
                                    <h6 class="text-uppercase text-muted small mb-3">In-app</h6>
                                    <div class="mb-4">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" role="switch" id="announcements_enabled" name="announcements_enabled" value="1"
                                                {{ old('announcements_enabled', $prefs['announcements_enabled'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="announcements_enabled">
                                                <strong>Announcements</strong>
                                                <span class="d-block text-muted small">Show the bell in the header and include org / branch announcements in your feed.</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="direct_messages_enabled" name="direct_messages_enabled" value="1"
                                                {{ old('direct_messages_enabled', $prefs['direct_messages_enabled'] ?? true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="direct_messages_enabled">
                                                <strong>Direct messages</strong>
                                                <span class="d-block text-muted small">Show the messages icon and alerts for staff chat in your organization.</span>
                                            </label>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-light border mb-4">
                                        <p class="mb-0 small"><strong>Platform account.</strong> Org announcements and staff messaging apply to tenant (branch) users. You can still set email preferences below for future alerts.</p>
                                    </div>
                                @endif

                                <h6 class="text-uppercase text-muted small mb-3">Email <span class="badge bg-secondary">Coming soon</span></h6>
                                <p class="text-muted small mb-3">These choices are saved on your profile. They will apply when your administrator configures outbound mail for alerts.</p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="email_notifications_enabled" name="email_notifications_enabled" value="1"
                                        {{ old('email_notifications_enabled', $prefs['email_notifications_enabled'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="email_notifications_enabled">
                                        <strong>Enable email notifications</strong>
                                        <span class="d-block text-muted small">Master switch for all email alerts below.</span>
                                    </label>
                                </div>
                                <div class="ps-2 ps-md-4 border-start ms-1 mb-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="email_low_stock" name="email_low_stock" value="1"
                                            {{ old('email_low_stock', $prefs['email_low_stock'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_low_stock">Low stock alerts</label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="email_expiry_alerts" name="email_expiry_alerts" value="1"
                                            {{ old('email_expiry_alerts', $prefs['email_expiry_alerts'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_expiry_alerts">Expiry &amp; batch reminders</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="email_sales_digest" name="email_sales_digest" value="1"
                                            {{ old('email_sales_digest', $prefs['email_sales_digest'] ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="email_sales_digest">Sales summary digest</label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary px-4">Save preferences</button>
                                <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary ms-2">Back to settings</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
