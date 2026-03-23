@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">New announcement</div>
            </div>
            @include('inc.msg')
            <div class="card">
                <div class="card-body">
                    <form method="post" action="{{ route('notifications.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Audience</label>
                            @if (auth()->user()->isTenantAdmin())
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="scope" id="scope_tenant" value="tenant" @checked(old('scope', 'tenant') === 'tenant')>
                                    <label class="form-check-label" for="scope_tenant">Whole organization (all branches)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="scope" id="scope_site" value="site" @checked(old('scope') === 'site')>
                                    <label class="form-check-label" for="scope_site">Single branch only</label>
                                </div>
                            @else
                                <input type="hidden" name="scope" value="site">
                                <input type="hidden" name="site_id" value="{{ old('site_id', $defaultSiteId) }}">
                                <p class="text-muted small mb-0">This announcement goes to <strong>your branch</strong> only
                                    @if ($sites->isNotEmpty())
                                        ({{ $sites->first()->name }}).
                                    @endif
                                </p>
                            @endif
                            @error('scope')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        @if (auth()->user()->isTenantAdmin())
                            <div class="mb-3" id="site-field" style="{{ old('scope', 'tenant') === 'site' ? '' : 'display:none' }}">
                                <label class="form-label">Branch</label>
                                <select name="site_id" class="form-select single-select" data-placeholder="Select branch">
                                    <option value=""></option>
                                    @foreach ($sites as $s)
                                        <option value="{{ $s->id }}" @selected(old('site_id') == $s->id)>{{ $s->name }} @if ($s->code)({{ $s->code }})@endif</option>
                                    @endforeach
                                </select>
                                @error('site_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required maxlength="255">
                            @error('title')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="body" class="form-control" rows="8" required maxlength="10000">{{ old('body') }}</textarea>
                            @error('body')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Publish</button>
                        <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
            @if (auth()->user()->isTenantAdmin())
                <script>
                    document.querySelectorAll('input[name="scope"]').forEach(function (r) {
                        r.addEventListener('change', function () {
                            var siteField = document.getElementById('site-field');
                            if (!siteField) return;
                            siteField.style.display = (document.getElementById('scope_site') && document.getElementById('scope_site').checked) ? '' : 'none';
                        });
                    });
                </script>
            @endif
        </div>
    </div>
@endsection
