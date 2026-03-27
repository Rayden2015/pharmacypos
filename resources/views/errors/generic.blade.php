@extends('errors._layout')

@section('error_body')
    <p class="error-code mb-1">{{ $code ?? '—' }}</p>
    <h1 class="h4 mb-3">{{ $title ?? __('Something went wrong') }}</h1>
    <p class="text-muted mb-0">{{ $message ?? __("This request couldn't be completed. Try again or use the menu to continue.") }}</p>
@endsection

@section('error_footer')
    <a href="{{ route('home') }}" class="text-decoration-none">{{ __('Home') }}</a>
@endsection
