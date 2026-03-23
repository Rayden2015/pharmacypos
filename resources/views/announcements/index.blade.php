@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Announcements</div>
            </div>
            @include('inc.msg')
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="text-muted mb-0">Organization-wide or branch-only posts for your team.</p>
                @if (auth()->user()->canPublishAnnouncements())
                    <a href="{{ route('notifications.create') }}" class="btn btn-primary btn-sm">New announcement</a>
                @endif
            </div>
            <div class="card">
                <div class="card-body p-0">
                    @if ($announcements->isEmpty())
                        <p class="text-muted p-4 mb-0">No announcements yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th class="d-none d-md-table-cell">Audience</th>
                                        <th class="d-none d-md-table-cell">From</th>
                                        <th class="d-none d-lg-table-cell">When</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($announcements as $a)
                                        @php($isRead = in_array($a->id, $readIds, true))
                                        <tr class="{{ $isRead ? '' : 'table-primary' }}">
                                            <td>
                                                <span class="fw-semibold">{{ $a->title }}</span>
                                                @if (! $isRead)
                                                    <span class="badge bg-primary ms-1">New</span>
                                                @endif
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                @if ($a->site_id === null)
                                                    <span class="text-muted">Whole organization</span>
                                                @else
                                                    {{ $a->site?->name ?? 'Branch' }}
                                                @endif
                                            </td>
                                            <td class="d-none d-md-table-cell">{{ $a->author?->name ?? '—' }}</td>
                                            <td class="d-none d-lg-table-cell text-muted small">{{ $a->created_at->diffForHumans() }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('notifications.show', $a) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">{{ $announcements->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
