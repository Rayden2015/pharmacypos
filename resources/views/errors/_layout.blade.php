<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('Error') }} — {{ config('app.name', 'PMS') }}</title>
    <link rel="icon" href="{{ asset('dash/img/icon.jpg') }}" type="image/png" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}">
    <style>
        body { min-height: 100vh; background: #f4f5f7; }
        .error-card { max-width: 32rem; border-radius: .75rem; box-shadow: 0 0.25rem 1rem rgba(0,0,0,.06); }
        .error-code { font-weight: 600; color: #6c757d; letter-spacing: .05em; }
    </style>
</head>
<body class="d-flex align-items-center py-5">
    <div class="container px-3">
        <div class="card error-card mx-auto border-0">
            <div class="card-body p-4 p-md-5">
                @yield('error_body')
            </div>
        </div>
        @hasSection('error_footer')
            <div class="text-center mt-4 small text-muted">
                @yield('error_footer')
            </div>
        @endif
    </div>
</body>
</html>
