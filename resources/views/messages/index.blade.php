@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Messages</div>
            </div>
            @include('inc.msg')
            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header py-3">
                            <h6 class="mb-0">New message</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="{{ route('messages.store') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">To</label>
                                    <select name="recipient_id" class="form-select single-select" data-placeholder="Select colleague" required>
                                        <option value=""></option>
                                        @foreach ($colleagues as $c)
                                            <option value="{{ $c->id }}" @selected(old('recipient_id') == $c->id)>{{ $c->name }} — {{ $c->email }}</option>
                                        @endforeach
                                    </select>
                                    @error('recipient_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea name="body" class="form-control" rows="4" required maxlength="10000" placeholder="Write your message…">{{ old('body') }}</textarea>
                                    @error('body')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">Send</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header py-3">
                            <h6 class="mb-0">Conversations</h6>
                        </div>
                        <div class="card-body p-0">
                            @if (count($threads) === 0)
                                <p class="text-muted px-3 py-4 mb-0">No conversations yet. Send a message to a colleague.</p>
                            @else
                                <div class="list-group list-group-flush">
                                    @foreach ($threads as $row)
                                        @php($u = $row['user'])
                                        <a href="{{ route('messages.show', $u) }}" class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div class="min-w-0">
                                                    <div class="fw-semibold">{{ $u->name }}</div>
                                                    <div class="text-muted small text-truncate">{{ \Illuminate\Support\Str::limit($row['lastMessage']->body, 80) }}</div>
                                                </div>
                                                <div class="text-end flex-shrink-0">
                                                    <span class="text-muted small">{{ $row['lastMessage']->created_at->diffForHumans() }}</span>
                                                    @if ($row['unread'] > 0)
                                                        <span class="badge bg-primary ms-1">{{ $row['unread'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
