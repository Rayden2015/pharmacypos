@extends('layouts.dash')
@section('content')
<style>
    .cust-card { border-radius: 12px; transition: box-shadow .2s; }
    .cust-card:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
    .cust-avatar {
        width: 88px; height: 88px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; font-weight: 700; color: #fff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .kpi-card { border-radius: 12px; border: none; color: #fff; }
</style>
<div class="wrapper">
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Customers</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active">Customers</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @include('inc.msg')

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">Grid view</span>
                    <a href="{{ route('customers.index', array_merge(request()->query(), ['view' => 'list'])) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3"><i class="bx bx-list-ul"></i> List view</a>
                </div>
                <button type="button" class="btn btn-warning text-dark fw-semibold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="bx bx-plus-circle"></i> Add customer
                </button>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
                <div class="col">
                    <div class="card kpi-card bg-primary"><div class="card-body py-3">
                        <div class="text-white-50 small">Total customers</div>
                        <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
                    </div></div>
                </div>
                <div class="col">
                    <div class="card kpi-card bg-success"><div class="card-body py-3">
                        <div class="text-white-50 small">Active</div>
                        <div class="fs-3 fw-bold">{{ $stats['active'] }}</div>
                    </div></div>
                </div>
                <div class="col">
                    <div class="card kpi-card bg-danger"><div class="card-body py-3">
                        <div class="text-white-50 small">Inactive</div>
                        <div class="fs-3 fw-bold">{{ $stats['inactive'] }}</div>
                    </div></div>
                </div>
                <div class="col">
                    <div class="card kpi-card bg-info"><div class="card-body py-3">
                        <div class="text-white-50 small">New this month</div>
                        <div class="fs-3 fw-bold">{{ $stats['new_this_month'] }}</div>
                    </div></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" action="{{ route('customers.index') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="view" value="grid">
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, mobile, email, code">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
                @foreach ($customers as $customer)
                    @php
                        $parts = preg_split('/\s+/', trim($customer->name));
                        $initials = '';
                        foreach (array_slice($parts, 0, 2) as $p) {
                            if ($p !== '') {
                                $initials .= strtoupper(substr($p, 0, 1));
                            }
                        }
                        if ($initials === '') {
                            $initials = '?';
                        }
                    @endphp
                    <div class="col">
                        <div class="card cust-card h-100 border-0 shadow-sm">
                            <div class="card-body position-relative pt-4">
                                <div class="position-absolute top-0 end-0 p-2">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle" type="button" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('customers.edit', $customer) }}"><i class="bx bx-link-external me-1"></i> Open page</a></li>
                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editCustomer{{ $customer->id }}"><i class="bx bx-edit-alt me-1"></i> Edit</a></li>
                                            <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteCustomer{{ $customer->id }}"><i class="bx bx-trash me-1"></i> Delete</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="cust-avatar mx-auto mb-2">{{ $initials }}</div>
                                    <div class="text-warning fw-semibold small mb-1">{{ $customer->code ?? 'CUST-' }}</div>
                                    <h6 class="mb-1 fw-bold"><a href="{{ route('customers.edit', $customer) }}" class="text-decoration-none text-dark">{{ $customer->name }}</a></h6>
                                    <div class="mb-2">
                                        @if ($customer->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                                <hr class="my-3">
                                <div class="row text-center small">
                                    <div class="col-6 border-end">
                                        <div class="text-muted">Since</div>
                                        <div class="fw-semibold">{{ $customer->created_at ? $customer->created_at->format('d M Y') : '—' }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Branch</div>
                                        <div class="fw-semibold text-truncate" title="{{ $customer->site->name ?? '' }}">{{ $customer->site->name ?? '—' }}</div>
                                    </div>
                                </div>
                                <div class="mt-2 text-center small text-muted">
                                    <i class="bx bx-phone"></i> {{ $customer->mobile }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $customers->links() }}</div>

            @include('customers.partials.customer-modals', ['customers' => $customers, 'sites' => $sites])
        </div>
    </div>
</div>

{{-- Add customer --}}
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('customers.store') }}">
                @csrf
                <input type="hidden" name="view" value="grid">
                @foreach (['search', 'status'] as $k)
                    @if (request()->filled($k))
                        <input type="hidden" name="{{ $k }}" value="{{ request($k) }}">
                    @endif
                @endforeach
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control" required value="{{ old('mobile') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>
                        @if(auth()->user()->isSuperAdmin())
                            <div class="col-md-6">
                                <label class="form-label">Site / branch</label>
                                <select name="site_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($sites as $s)
                                        <option value="{{ $s->id }}" {{ (string) old('site_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
