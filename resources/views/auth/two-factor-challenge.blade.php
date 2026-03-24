<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Verification code') }} — {{ config('app.name', 'Pharmacy POS') }}</title>
    <link href="{{ asset('dash/css/bootstrap.min.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3">{{ __('Enter verification code') }}</h1>
                    <p class="text-muted small mb-4">{{ __('We sent a 6-digit code to your email address. Enter it below to complete sign-in.') }}</p>

                    @include('inc.flash')

                    <form method="post" action="{{ route('two-factor.verify') }}" class="mb-3">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">{{ __('Code') }}</label>
                            <input type="text" name="code" id="code" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                                   class="form-control form-control-lg text-center @error('code') is-invalid @enderror"
                                   value="{{ old('code') }}" autocomplete="one-time-code" autofocus required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">{{ __('Verify and continue') }}</button>
                    </form>

                    <form method="post" action="{{ route('two-factor.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm w-100">{{ __('Resend code') }}</button>
                    </form>

                    <p class="text-center mt-3 mb-0">
                        <a href="{{ route('login') }}">{{ __('Back to login') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
