@extends('errors._layout')

@section('error_body')
    <p class="error-code mb-1">429</p>
    <h1 class="h4 mb-3">{{ __('Too many requests') }}</h1>
    <p class="text-muted mb-0">{{ __('Please wait a moment before trying again.') }}</p>
@endsection
