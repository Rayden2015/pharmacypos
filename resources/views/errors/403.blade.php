@extends('errors._layout')

@section('error_body')
    <p class="error-code mb-1">403</p>
    <h1 class="h4 mb-3">{{ __('Access denied') }}</h1>
    <p class="text-muted mb-0">{{ __("You don't have permission to view this page. If you think this is a mistake, contact your administrator.") }}</p>
@endsection

@section('error_footer')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="text-decoration-none">{{ __('Go back') }}</a>
@endsection
