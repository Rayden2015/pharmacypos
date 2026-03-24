{{-- Live counts & previews for tenant users; platform super admins see no items. --}}
@php
    $nUnread = $headerUnreadAnnouncements ?? 0;
    $mUnread = $headerUnreadDms ?? 0;
    $annPreviews = $headerAnnouncementPreviews ?? collect();
    $dmPreviews = $headerDmPreviews ?? collect();
    $tenantComms = auth()->check() && auth()->user()->canUseTenantCommunications();
    $annOn = $tenantComms && auth()->user()->notificationPreference('announcements_enabled', true);
    $dmOn = $tenantComms && auth()->user()->notificationPreference('direct_messages_enabled', true);
@endphp
@if ($annOn)
<li class="nav-item dropdown dropdown-large">
    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" data-header-alerts="notifications" aria-expanded="false" title="Announcements">
        <span class="alert-count {{ $nUnread > 0 ? '' : 'd-none' }}">{{ $nUnread > 99 ? '99+' : $nUnread }}</span>
        <i class="bx bx-bell"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end p-0 shadow">
        <div class="msg-header">
            <p class="msg-header-title mb-0">Announcements</p>
            <button type="button" class="header-alerts-mark-read ms-auto js-mark-notifications-read"
                @if (auth()->check() && auth()->user()->canUseTenantCommunications())
                    data-mark-read-url="{{ route('notifications.mark-all-read') }}"
                @endif
            >Mark all as read</button>
        </div>
        <div class="header-notifications-list dropdown-alerts-item">
            @forelse ($annPreviews as $a)
                <a class="dropdown-item py-3" href="{{ route('notifications.show', $a) }}">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="notify-avatar-initials" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">A</span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h6 class="msg-name mb-0 text-truncate">{{ $a->title }}</h6>
                                <span class="msg-time flex-shrink-0">{{ $a->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="msg-info text-truncate mb-0 mt-1 text-muted small">
                                @if ($a->site_id === null)
                                    Whole organization
                                @else
                                    Branch: {{ $a->site ? $a->site->name : '—' }}
                                @endif
                            </p>
                        </div>
                        @if ($a->unread ?? false)
                            <span class="notify-unread-dot" aria-hidden="true"></span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="dropdown-item py-3 text-muted small">No announcements</div>
            @endforelse
        </div>
        <div class="dropdown-alerts-footer">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="dropdown">Close</button>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-primary">View all</a>
        </div>
    </div>
</li>
@endif
@if ($dmOn)
<li class="nav-item dropdown dropdown-large">
    <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" role="button" data-bs-toggle="dropdown" data-header-alerts="messages" aria-expanded="false" title="Direct messages">
        <span class="alert-count alert-count--messages {{ $mUnread > 0 ? '' : 'd-none' }}">{{ $mUnread > 99 ? '99+' : $mUnread }}</span>
        <i class="bx bx-envelope"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end p-0 shadow">
        <div class="msg-header">
            <p class="msg-header-title mb-0">Messages</p>
            <button type="button" class="header-alerts-mark-read ms-auto js-mark-messages-read"
                @if (auth()->check() && auth()->user()->canUseTenantCommunications())
                    data-mark-read-url="{{ route('messages.mark-all-read') }}"
                @endif
            >Mark all as read</button>
        </div>
        <div class="header-message-list dropdown-alerts-item">
            @forelse ($dmPreviews as $row)
                <a class="dropdown-item py-3" href="{{ route('messages.show', $row['user']) }}">
                    <div class="d-flex gap-2 align-items-start">
                        <span class="notify-avatar-initials" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">{{ strtoupper(substr($row['user']->name, 0, 2)) }}</span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h6 class="msg-name mb-0">{{ $row['user']->name }}</h6>
                                <span class="msg-time flex-shrink-0">{{ $row['lastMessage']->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="msg-info text-truncate mb-0 mt-1">{{ \Illuminate\Support\Str::limit($row['lastMessage']->body, 72) }}</p>
                        </div>
                        @if (($row['unread'] ?? 0) > 0)
                            <span class="notify-unread-dot" aria-hidden="true"></span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="dropdown-item py-3 text-muted small">No messages</div>
            @endforelse
        </div>
        <div class="dropdown-alerts-footer">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="dropdown">Close</button>
            <a href="{{ route('messages.index') }}" class="btn btn-sm btn-primary">View all</a>
        </div>
    </div>
</li>
@endif
