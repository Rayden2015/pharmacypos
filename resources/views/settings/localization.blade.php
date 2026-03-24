@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Localization</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Localization</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            @php
                $localeLabels = [
                    'en' => 'English',
                    'fr' => 'Français',
                    'es' => 'Español',
                    'ar' => 'العربية',
                ];
                $dateFormatChoices = [
                    'd M Y' => '24 Mar 2026',
                    'd/m/Y' => '24/03/2026',
                    'm/d/Y' => '03/24/2026',
                    'Y-m-d' => '2026-03-24',
                    'd-m-Y' => '24-03-2026',
                ];
                $timeFormatChoices = [
                    'H:i' => '14:30 (24h)',
                    'g:i A' => '2:30 PM (12h)',
                ];
                $preview = \Illuminate\Support\Carbon::now();
                $df = old('date_format', $dateFormat);
                $tf = old('time_format', $timeFormat);
            @endphp

            <div class="row">
                <div class="col-12 col-xl-8">
                    <div class="card radius-10">
                        <div class="card-body">
                            <h5 class="card-title mb-1">Regional settings</h5>
                            <p class="text-muted small mb-4">Currency display, language, timezone, and how dates and times appear across the app.</p>

                            <form action="{{ route('settings.localization.update') }}" method="post" class="row g-3">
                                @csrf
                                @method('put')

                                <div class="col-12 col-md-6">
                                    <label for="currency_symbol" class="form-label">Currency symbol</label>
                                    <input type="text" name="currency_symbol" id="currency_symbol" class="form-control @error('currency_symbol') is-invalid @enderror"
                                        value="{{ old('currency_symbol', $currencySymbol) }}" maxlength="16" required
                                        placeholder="e.g. GH₵, $, ₦">
                                    @error('currency_symbol')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="currency_code" class="form-label">Currency code <span class="text-muted">(optional)</span></label>
                                    <input type="text" name="currency_code" id="currency_code" class="form-control @error('currency_code') is-invalid @enderror"
                                        value="{{ old('currency_code', $currencyCode) }}" maxlength="8"
                                        placeholder="e.g. GHS, USD">
                                    @error('currency_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="app_locale" class="form-label">Language</label>
                                    <select name="app_locale" id="app_locale" class="form-select @error('app_locale') is-invalid @enderror" required>
                                        @foreach ($localeLabels as $code => $label)
                                            <option value="{{ $code }}" @selected(old('app_locale', $appLocale) === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('app_locale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="app_timezone" class="form-label">Timezone</label>
                                    <select name="app_timezone" id="app_timezone" class="form-select single-select @error('app_timezone') is-invalid @enderror" required>
                                        @foreach ($timezones as $tz)
                                            <option value="{{ $tz }}" @selected(old('app_timezone', $appTimezone) === $tz)>{{ $tz }}</option>
                                        @endforeach
                                    </select>
                                    @error('app_timezone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="date_format" class="form-label">Date format</label>
                                    <select name="date_format" id="date_format" class="form-select @error('date_format') is-invalid @enderror" required>
                                        @foreach ($dateFormatChoices as $value => $example)
                                            <option value="{{ $value }}" @selected(old('date_format', $dateFormat) === $value)>{{ $example }} ({{ $value }})</option>
                                        @endforeach
                                    </select>
                                    @error('date_format')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="time_format" class="form-label">Time format</label>
                                    <select name="time_format" id="time_format" class="form-select @error('time_format') is-invalid @enderror" required>
                                        @foreach ($timeFormatChoices as $value => $label)
                                            <option value="{{ $value }}" @selected(old('time_format', $timeFormat) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('time_format')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="border rounded p-3 bg-light">
                                        <span class="text-uppercase text-muted small d-block mb-1">Preview</span>
                                        <span class="text-body">{{ $preview->format($df) }} · {{ $preview->format($tf) }}</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary px-4">Save</button>
                                    <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary ms-2">Back to settings</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
