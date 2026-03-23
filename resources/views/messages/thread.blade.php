@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">
                    <a href="{{ route('messages.index') }}" class="text-secondary text-decoration-none">Messages</a>
                    <span class="text-muted"> / </span>
                    {{ $other->name }}
                </div>
            </div>
            @include('inc.msg')
            <div class="card">
                <div class="card-body">
                    <div class="mb-4" style="max-height: 420px; overflow-y: auto;">
                        @forelse ($messages as $m)
                            @php($mine = $m->sender_id === auth()->id())
                            <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : 'justify-content-start' }}">
                                <div class="rounded-3 px-3 py-2 {{ $mine ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 85%;">
                                    <div class="small {{ $mine ? 'text-white-50' : 'text-muted' }} mb-1">{{ $m->created_at->format('M j, g:i a') }}</div>
                                    <div class="mb-0" style="white-space: pre-wrap;">{{ $m->body }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No messages yet. Say hello below.</p>
                        @endforelse
                    </div>
                    <form method="post" action="{{ route('messages.store') }}">
                        @csrf
                        <input type="hidden" name="recipient_id" value="{{ $other->id }}">
                        <div class="mb-2">
                            <label class="form-label visually-hidden">Reply</label>
                            <textarea name="body" class="form-control" rows="3" required maxlength="10000" placeholder="Reply…"></textarea>
                            @error('body')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
