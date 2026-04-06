@extends('layouts.dash')

@section('document_title')
{{ $other->name }} · {{ __('Messages') }}
@endsection

@section('content')
    <link href="{{ versioned_asset('dash/css/messages-thread.css') }}" rel="stylesheet">
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">
                    <a href="{{ route('messages.index') }}" class="text-secondary text-decoration-none">{{ __('Messages') }}</a>
                    <span class="text-muted"> / </span>
                    <span class="fw-semibold">{{ $other->name }}</span>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="flex-shrink-0">
                        @include('messages.partials.dm-avatar', ['user' => $other, 'size' => 48])
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <h5 class="mb-0 text-truncate" title="{{ $other->name }}">{{ $other->name }}</h5>
                        <div class="text-muted small text-truncate" title="{{ $other->email }}">{{ $other->email }}</div>
                        <div class="text-muted small">{{ __('You are messaging this person') }}</div>
                    </div>
                </div>
            </div>

            @include('inc.msg')

            <div class="card">
                <div class="card-body">
                    <div
                        class="dm-thread-scroll mb-4 pt-1"
                        id="dm-thread-scroll"
                        aria-live="polite"
                        aria-relevant="additions"
                    >
                        @forelse ($messages as $m)
                            @php
                                $mine = (int) $m->sender_id === (int) auth()->id();
                            @endphp
                            <div
                                class="d-flex mb-3 align-items-end {{ $mine ? 'justify-content-end' : 'justify-content-start' }}"
                                data-msg-id="{{ $m->id }}"
                            >
                                @unless ($mine)
                                    <div class="me-2">
                                        @include('messages.partials.dm-avatar', ['user' => $m->sender, 'size' => 40])
                                    </div>
                                @endunless
                                <div class="dm-bubble {{ $mine ? 'dm-bubble--me' : 'dm-bubble--them' }}">
                                    <div class="dm-bubble-meta">{{ $m->created_at->format('M j, g:i a') }}</div>
                                    <div class="dm-bubble-body">{{ $m->body }}</div>
                                </div>
                                @if ($mine)
                                    <div class="ms-2">
                                        @include('messages.partials.dm-avatar', ['user' => $m->sender, 'size' => 40])
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted mb-0 dm-thread-empty">{{ __('No messages yet. Say hello below.') }}</p>
                        @endforelse
                    </div>

                    <form method="post" action="{{ route('messages.store') }}" class="dm-send-form">
                        @csrf
                        <input type="hidden" name="recipient_id" value="{{ $other->id }}">
                        <div class="mb-2">
                            <label class="form-label visually-hidden">{{ __('Reply') }}</label>
                            <textarea
                                name="body"
                                class="form-control"
                                rows="3"
                                required
                                maxlength="10000"
                                placeholder="{{ __('Write a message...') }}"
                            >{{ old('body') }}</textarea>
                            @error('body')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Send') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @php
        $lastMessageId = $messages->isEmpty() ? 0 : $messages->last()->id;
        $dmThreadConfig = [
            'pollUrl' => route('messages.poll', ['user' => $other->id]),
            'sendUrl' => route('messages.store'),
            'scrollSelector' => '#dm-thread-scroll',
            'formSelector' => '.dm-send-form',
            'lastMessageId' => (int) $lastMessageId,
            'meId' => (int) $me->id,
            'me' => [
                'id' => $me->id,
                'name' => $me->name,
                'has_photo' => $me->hasProfilePhoto(),
                'photo_url' => $me->profilePhotoUrl(),
            ],
            'other' => [
                'id' => $other->id,
                'name' => $other->name,
                'has_photo' => $other->hasProfilePhoto(),
                'photo_url' => $other->profilePhotoUrl(),
            ],
            'pollIntervalMs' => 3500,
        ];
    @endphp
    <script src="{{ versioned_asset('dash/js/messages-thread.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.initMessagesThread !== 'function') return;
            window.initMessagesThread(@json($dmThreadConfig));
        });
    </script>
@endsection
