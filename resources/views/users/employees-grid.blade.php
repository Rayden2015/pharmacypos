@extends('layouts.dash')
@section('content')
<style>
    .emp-card { border-radius: 12px; overflow: hidden; transition: box-shadow .2s; }
    .emp-card:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
    .emp-avatar { width: 88px; height: 88px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
    .emp-id { color: #fd7e14; font-weight: 600; font-size: .8rem; }
    .kpi-card { border-radius: 12px; border: none; color: #fff; }
</style>
<div class="wrapper">
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Employees</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active">Employees</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @include('inc.msg')

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">Grid view</span>
                    <a href="{{ route('pharmacy.showuser', request()->query()) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bx bx-list-ul"></i> List view</a>
                </div>
                <a href="{{ route('users.index') }}" class="btn btn-warning text-dark fw-semibold rounded-pill px-4 shadow-sm"><i class="bx bx-plus-circle"></i> Add employee</a>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
                <div class="col">
                    <div class="card kpi-card bg-primary">
                        <div class="card-body py-3">
                            <div class="text-white-50 small">Total employees</div>
                            <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card kpi-card bg-success">
                        <div class="card-body py-3">
                            <div class="text-white-50 small">Active</div>
                            <div class="fs-3 fw-bold">{{ $stats['active'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card kpi-card bg-danger">
                        <div class="card-body py-3">
                            <div class="text-white-50 small">Inactive</div>
                            <div class="fs-3 fw-bold">{{ $stats['inactive'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card kpi-card bg-info">
                        <div class="card-body py-3">
                            <div class="text-white-50 small">New this month</div>
                            <div class="fs-3 fw-bold">{{ $stats['new_this_month'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" action="{{ route('users.employees.grid') }}" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, email, mobile">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Role</label>
                            <select name="role" class="form-select">
                                <option value="">All</option>
                                @foreach (\App\Models\User::HIERARCHY_ROLE_LABELS as $value => $label)
                                    <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
                @foreach ($users as $user)
                    <div class="col">
                        <div class="card emp-card h-100 border-0 shadow-sm">
                            <div class="card-body position-relative pt-4">
                                <div class="position-absolute top-0 end-0 p-2">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editUser{{ $user->id }}"><i class="bx bx-edit-alt me-1"></i> Edit</a></li>
                                            <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteUser{{ $user->id }}"><i class="bx bx-trash me-1"></i> Delete</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <img src="{{ $user->avatarUrl() }}" class="emp-avatar mb-2" alt="">
                                    <div class="emp-id mb-1">EMP ID : POS{{ str_pad((string) $user->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    <h6 class="mb-1 fw-bold">{{ $user->name }}</h6>
                                    <div class="mb-2">
                                        @include('users.partials.employee-role-badge', ['user' => $user])
                                    </div>
                                </div>
                                <hr class="my-3">
                                <div class="row text-center small">
                                    <div class="col-6 border-end">
                                        <div class="text-muted">Joined</div>
                                        <div class="fw-semibold">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Branch</div>
                                        <div class="fw-semibold text-truncate" title="{{ $user->site->name ?? '' }}">{{ $user->site->name ?? '—' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>

            @include('users.partials.user-modals', ['users' => $users, 'sites' => $sites, 'assignableRoles' => $assignableRoles])
        </div>
    </div>
</div>
@endsection
