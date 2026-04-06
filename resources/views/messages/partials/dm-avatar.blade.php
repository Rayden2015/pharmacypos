@php
    /** @var \App\Models\User $user */
    $size = $size ?? 40;
    $fontPx = max(14, (int) round($size * 0.5));
@endphp
<div class="dm-avatar flex-shrink-0 rounded-circle overflow-hidden d-flex align-items-center justify-content-center {{ $user->hasProfilePhoto() ? '' : 'dm-avatar--silhouette' }}" style="width: {{ $size }}px; height: {{ $size }}px;" aria-hidden="true">
    @if ($user->hasProfilePhoto())
        <img src="{{ $user->profilePhotoUrl() }}" alt="" class="w-100 h-100" style="object-fit: cover;">
    @else
        <i class="bx bx-user" style="font-size: {{ $fontPx }}px;"></i>
    @endif
</div>
