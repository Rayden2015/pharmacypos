@extends('errors._layout')

@section('error_body')
    <p class="error-code mb-1">419</p>
    <h1 class="h4 mb-3">{{ __('Session expired') }}</h1>
    <p class="text-muted mb-4">{{ __('This page was open too long or the security token expired. Refresh the page or sign in again and try once more.') }}</p>
    <a href="{{ route('login') }}" class="btn btn-primary">{{ __('Sign in') }}</a>
@endsection
