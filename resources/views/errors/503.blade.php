@extends('errors._layout')

@section('error_body')
    <p class="error-code mb-1">503</p>
    <h1 class="h4 mb-3">{{ __('Service unavailable') }}</h1>
    <p class="text-muted mb-0">{{ __("We're doing a quick update or maintenance. Try again in a few minutes.") }}</p>
@endsection
