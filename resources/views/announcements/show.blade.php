@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">
                    <a href="{{ route('notifications.index') }}" class="text-secondary text-decoration-none">Announcements</a>
                </div>
            </div>
            @include('inc.msg')
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-2">{{ $announcement->title }}</h4>
                    <p class="text-muted small mb-4">
                        @if ($announcement->site_id === null)
                            <span class="badge bg-secondary">Whole organization</span>
                        @else
                            <span class="badge bg-info text-dark">Branch: {{ $announcement->site?->name ?? '—' }}</span>
                        @endif
                        · {{ $announcement->author?->name ?? '—' }}
                        · {{ $announcement->created_at->format('M j, Y g:i a') }}
                    </p>
                    <div class="announcement-body" style="white-space: pre-wrap;">{{ $announcement->body }}</div>
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm mt-4">Back to list</a>
                </div>
            </div>
        </div>
    </div>
@endsection
