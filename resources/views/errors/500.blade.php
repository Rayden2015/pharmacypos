@extends('errors._layout')

@section('error_body')
    <p class="error-code mb-1">500</p>
    <h1 class="h4 mb-3">{{ __('Something went wrong') }}</h1>
    <p class="text-muted mb-3">{{ __("We couldn't complete that action. Our team has been notified. You can try again in a moment.") }}</p>
    @if (!empty($incidentId))
        <p class="small mb-2"><strong>{{ __('Reference') }}:</strong> <code>{{ $incidentId }}</code></p>
        <p class="small text-muted mb-0">{{ __('Share this reference with support if the problem continues.') }}</p>
    @endif
    @if (!empty($showDetail) && !empty($detailMessage))
        <hr class="my-3">
        <p class="small font-monospace text-break text-danger mb-0">{{ $detailMessage }}</p>
    @endif
@endsection

@section('error_footer')
    <a href="{{ route('home') }}" class="text-decoration-none">{{ __('Home') }}</a>
@endsection
