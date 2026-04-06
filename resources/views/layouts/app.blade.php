<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" href="{{versioned_asset('dash/img/icon.jpg')}}" type="image/png" />


    <!-- Scripts -->
    {{-- <script src="{{ versioned_asset('js/app.js') }}" defer></script> --}}

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    {{-- <link href="{{ versioned_asset('css/app.css') }}" rel="stylesheet"> --}}


    <link rel="stylesheet" type="text/css" href="{{versioned_asset('assets/vendor/bootstrap/css/bootstrap.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{versioned_asset('assets/vendor/font-awesome/css/all.min.css')}}">
    <link rel="stylesheet" type="text/css" href="{{versioned_asset('assets/css/stylesheet.css')}}">
    <!-- Colors Css -->
    <link id="color-switcher" type="text/css" rel="stylesheet" href="{{versioned_asset('assets/css/color-red.css')}}">
</head>
<body>
    <div id="app">
       @include('inc.navbar')

        <main>
            @yield('content')
        </main>
    </div>

    <script src="{{versioned_asset('assets/vendor/jquery/jquery.min.js')}}"></script> 
    <script src="{{versioned_asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script> 
    <!-- Style Switcher --> 
    <script src="{{versioned_asset('assets/js/switcher.min.js')}}"></script>
    <script src="{{versioned_asset('assets/js/theme.js')}}"></script>
</body>
</html>
