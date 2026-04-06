@extends('layouts.dash')
@section('content')
    @php
        $u = $user ?? auth()->user();
        $avatar = $u->user_img ?? 'user.png';
        $prefs = $u->notification_preferences ?? [];
        $tfSms = (bool) ($prefs['two_factor_sms'] ?? false);
        $tfEmail = (bool) ($prefs['two_factor_email'] ?? false);
    @endphp
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-2">
                <div class="breadcrumb-title pe-3">{{ __('Settings') }}</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('Profile') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>

            {{-- DreamsPOS-style horizontal settings tabs --}}
            <div class="d-flex flex-wrap align-items-center gap-2 mb-4 pb-2 border-bottom">
                <a href="{{ route('settings.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('Overview') }}</a>
                <span class="btn btn-sm btn-primary rounded-pill px-3 disabled" tabindex="-1" aria-current="page">{{ __('Profile') }}</span>
                <a href="{{ route('settings.localization') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('Localization') }}</a>
                <a href="{{ route('sites.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('Branches') }}</a>
                <a href="{{ route('settings.notifications') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">{{ __('Notifications') }}</a>
            </div>

            @include('inc.msg')

            <div class="row">
                <div class="col-12 col-lg-4 col-xl-3 mb-4">
                    <div class="card radius-10">
                        <div class="card-body text-center">
                            <div class="position-relative d-inline-block mb-2">
                                <img src="{{ versioned_asset('storage/users/'.$avatar) }}" alt=""
                                    class="rounded-circle p-1 bg-primary" width="120" height="120" style="object-fit: cover;">
                            </div>
                            <h5 class="mb-1">{{ $u->name }}</h5>
                            <p class="text-muted small mb-2">{{ $u->hierarchyLabel() }}</p>
                            @if ($u->site)
                                <p class="text-muted small mb-0">
                                    <i class="bx bx-buildings"></i> {{ $u->site->name }}@if($u->site->code) · {{ $u->site->code }}@endif
                                </p>
                            @endif
                            @if ($u->company && ! $u->is_super_admin)
                                <p class="text-muted small mb-0">{{ $u->company->company_name }}</p>
                            @endif
                            <span class="badge rounded-pill mt-2 {{ $u->status == '1' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $u->status == '1' ? __('Active') : __('Inactive') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8 col-xl-9">
                    {{-- Basic information + image (PDF: Upload Store Image, names, email, phone) --}}
                    <form action="{{ route('profile.update') }}" method="post" enctype="multipart/form-data" class="mb-4">
                        @csrf
                        @method('put')
                        <input type="hidden" name="_section" value="profile">

                        <div class="card radius-10 mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-1">{{ __('Basic Information') }}</h5>
                                <p class="text-muted small mb-4">{{ __('Upload profile image. Image should be below 2MB.') }}</p>

                                <div class="row g-3 mb-4">
                                    <div class="col-12">
                                        <label for="user_img" class="form-label">{{ __('Upload image') }}</label>
                                        <input type="file" name="user_img" id="user_img" accept="image/*"
                                            class="form-control @error('user_img') is-invalid @enderror">
                                        @error('user_img')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">{{ __('First name') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="first_name" required maxlength="100"
                                            class="form-control @error('first_name') is-invalid @enderror"
                                            value="{{ old('first_name', $firstName) }}" autocomplete="given-name">
                                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">{{ __('Last name') }}</label>
                                        <input type="text" name="last_name" id="last_name" maxlength="100"
                                            class="form-control @error('last_name') is-invalid @enderror"
                                            value="{{ old('last_name', $lastName) }}" autocomplete="family-name">
                                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                                        <input type="email" name="email" id="email" required maxlength="255"
                                            class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', $u->email) }}" autocomplete="email">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="mobile" class="form-label">{{ __('Phone number') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="mobile" id="mobile" required minlength="10" maxlength="10"
                                            class="form-control @error('mobile') is-invalid @enderror"
                                            value="{{ old('mobile', $u->mobile) }}" inputmode="numeric" autocomplete="tel">
                                        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card radius-10 mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-1">{{ __('Address information') }}</h5>
                                <p class="text-muted small mb-4">{{ __('Used for records and future invoicing features.') }}</p>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="address_line1" class="form-label">{{ __('Address line 1') }}</label>
                                        <input type="text" name="address_line1" id="address_line1" maxlength="255"
                                            class="form-control @error('address_line1') is-invalid @enderror"
                                            value="{{ old('address_line1', $u->address_line1) }}" autocomplete="address-line1">
                                        @error('address_line1')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="address_line2" class="form-label">{{ __('Address line 2') }}</label>
                                        <input type="text" name="address_line2" id="address_line2" maxlength="255"
                                            class="form-control @error('address_line2') is-invalid @enderror"
                                            value="{{ old('address_line2', $u->address_line2) }}">
                                        @error('address_line2')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="country" class="form-label">{{ __('Country') }}</label>
                                        <input type="text" name="country" id="country" maxlength="120"
                                            class="form-control @error('country') is-invalid @enderror"
                                            value="{{ old('country', $u->country) }}" autocomplete="country-name">
                                        @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">{{ __('City') }}</label>
                                        <input type="text" name="city" id="city" maxlength="120"
                                            class="form-control @error('city') is-invalid @enderror"
                                            value="{{ old('city', $u->city) }}" autocomplete="address-level2">
                                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="state_region" class="form-label">{{ __('State / province') }}</label>
                                        <input type="text" name="state_region" id="state_region" maxlength="120"
                                            class="form-control @error('state_region') is-invalid @enderror"
                                            value="{{ old('state_region', $u->state_region) }}" autocomplete="address-level1">
                                        @error('state_region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="postal_code" class="form-label">{{ __('Pin code') }}</label>
                                        <input type="text" name="postal_code" id="postal_code" maxlength="32"
                                            class="form-control @error('postal_code') is-invalid @enderror"
                                            value="{{ old('postal_code', $u->postal_code) }}" autocomplete="postal-code">
                                        @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bx bx-save me-1"></i>{{ __('Save changes') }}
                        </button>
                    </form>

                    {{-- Change password (separate save, PDF) --}}
                    <form action="{{ route('profile.update') }}" method="post" class="mb-4">
                        @csrf
                        @method('put')
                        <input type="hidden" name="_section" value="password">

                        <div class="card radius-10 mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-1">{{ __('Change password') }}</h5>
                                <p class="text-muted small mb-4">{{ __('Use a strong password you do not use elsewhere.') }}</p>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="current_password" class="form-label">{{ __('Current password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="current_password" id="current_password" required
                                            class="form-control @error('current_password') is-invalid @enderror"
                                            autocomplete="current-password">
                                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="password" class="form-label">{{ __('New password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password" id="password" required
                                            class="form-control @error('password') is-invalid @enderror"
                                            autocomplete="new-password">
                                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="password_confirmation" class="form-label">{{ __('Confirm password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password_confirmation" id="password_confirmation" required
                                            class="form-control" autocomplete="new-password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bx bx-save me-1"></i>{{ __('Save changes') }}
                        </button>
                    </form>

                    {{-- 2-step verification (preferences only; SMS/email delivery when org enables it) --}}
                    <form action="{{ route('profile.update') }}" method="post">
                        @csrf
                        @method('put')
                        <input type="hidden" name="_section" value="security">

                        <div class="card radius-10 mb-3">
                            <div class="card-body">
                                <h5 class="card-title mb-1">{{ __('Two step verification') }}</h5>
                                <p class="text-muted small mb-4">{{ __('Add an extra layer of security to your account. Delivery of codes depends on your organization enabling SMS or mail.') }}</p>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="two_factor_sms" id="two_factor_sms" value="1"
                                        {{ old('two_factor_sms', $tfSms) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="two_factor_sms">
                                        <strong>{{ __('Phone') }}</strong>
                                        <span class="d-block text-muted small">{{ __('Receive a one-time code via SMS when signing in') }}</span>
                                    </label>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="two_factor_email" id="two_factor_email" value="1"
                                        {{ old('two_factor_email', $tfEmail) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="two_factor_email">
                                        <strong>{{ __('Email') }}</strong>
                                        <span class="d-block text-muted small">{{ __('Receive a verification code at your registered email address') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bx bx-save me-1"></i>{{ __('Save changes') }}
                        </button>
                    </form>

                    <div class="mt-4">
                        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary">{{ __('Back to settings') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
