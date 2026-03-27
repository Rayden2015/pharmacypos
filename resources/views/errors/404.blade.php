@extends('errors._layout')

@section('error_body')
    <p class="error-code mb-1">404</p>
    <h1 class="h4 mb-3">{{ __('Page not found') }}</h1>
    <p class="text-muted mb-0">{{ __("We couldn't find what you're looking for. Check the address or use the menu to continue.") }}</p>
@endsection

@section('error_footer')
    <a href="{{ route('home') }}" class="text-decoration-none">{{ __('Home') }}</a>
@endsection
