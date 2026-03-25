@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Settings</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Branches</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="card radius-10 border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 3rem; height: 3rem;">
                                    <i class="bx bx-buildings fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Branches</h5>
                                    <p class="text-muted small mb-0">Register locations, branch contacts, and status. Stock and sales are tracked per branch.</p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 w-100 w-lg-auto">
                                <form method="get" action="{{ route('sites.index') }}" class="flex-grow-1 flex-lg-grow-0" style="min-width: 12rem; max-width: 22rem;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-transparent border-end-0"><i class="bx bx-search-alt"></i></span>
                                        <input type="search" name="q" class="form-control border-start-0" value="{{ $q ?? '' }}" placeholder="Search name, code, manager, phone, email…" maxlength="120">
                                    </div>
                                </form>
                                <a href="{{ route('sites.create') }}" class="btn btn-primary btn-sm px-3">
                                    <i class="bx bx-plus me-1"></i> Add new
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card radius-10 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-nowrap">ID</th>
                                        <th>{{ __('Branch name') }}</th>
                                        @if (auth()->user()->isSuperAdmin())
                                            <th class="d-none d-md-table-cell">{{ __('Tenant') }}</th>
                                        @endif
                                        <th class="d-none d-lg-table-cell">{{ __('Manager') }}</th>
                                        <th class="d-none d-xl-table-cell">{{ __('Phone') }}</th>
                                        <th class="d-none d-xl-table-cell">{{ __('Email') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="text-end text-nowrap">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sites as $site)
                                        <tr>
                                            <td class="fw-semibold text-primary text-nowrap">{{ $site->branchDisplayId() }}</td>
                                            <td>
                                                <div class="fw-medium">{{ $site->name }}</div>
                                                @if ($site->code)
                                                    <span class="text-muted small">{{ $site->code }}</span>
                                                @endif
                                                <div class="d-lg-none small text-muted mt-1">
                                                    @if ($site->manager_name)
                                                        <div><i class="bx bx-user me-1"></i>{{ $site->manager_name }}</div>
                                                    @endif
                                                    @if ($site->phone)
                                                        <div><i class="bx bx-phone me-1"></i>{{ $site->phone }}</div>
                                                    @endif
                                                    @if ($site->email)
                                                        <div><i class="bx bx-envelope me-1"></i>{{ $site->email }}</div>
                                                    @endif
                                                </div>
                                            </td>
                                            @if (auth()->user()->isSuperAdmin())
                                                <td class="d-none d-md-table-cell">{{ $site->company?->company_name ?? '—' }}</td>
                                            @endif
                                            <td class="d-none d-lg-table-cell">{{ $site->manager_name ?: '—' }}</td>
                                            <td class="d-none d-xl-table-cell">{{ $site->phone ?: '—' }}</td>
                                            <td class="d-none d-xl-table-cell">{{ $site->email ? \Illuminate\Support\Str::limit($site->email, 28) : '—' }}</td>
                                            <td>
                                                @if ($site->is_active)
                                                    <span class="d-inline-flex align-items-center text-success small fw-medium">
                                                        <i class="bx bxs-circle me-1" style="font-size: 0.45rem;"></i> Active
                                                    </span>
                                                @else
                                                    <span class="text-muted small">Inactive</span>
                                                @endif
                                                @if ($site->is_default)
                                                    <span class="badge bg-primary ms-1">Default</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('sites.edit', $site) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                                    <i class="bx bx-edit-alt"></i>
                                                </a>
                                                @if (! $site->is_default)
                                                    <form action="{{ route('sites.destroy', $site) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this branch? Only if it has no stock on hand.') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="text-center text-muted py-4">
                                                @if (! empty(trim($q ?? '')))
                                                    {{ __('No branches match your search.') }}
                                                @else
                                                    {{ __('No branches yet. Add your first location.') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $sites->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
