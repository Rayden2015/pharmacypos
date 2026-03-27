@extends('layouts.dash')
@section('content')
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
                    <a href="{{ route('customers.index', array_merge(request()->query(), ['view' => 'grid'])) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3"><i class="bx bx-grid-alt"></i> Grid view</a>
                    <span class="badge bg-secondary rounded-pill px-3 py-2">List view</span>
                </div>
                <button type="button" class="btn btn-warning text-dark fw-semibold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="bx bx-plus-circle"></i> Add customer
                </button>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4">
                <div class="col"><div class="card bg-primary text-white border-0 rounded-3"><div class="card-body py-3"><div class="small opacity-75">Total</div><div class="fs-3 fw-bold">{{ $stats['total'] }}</div></div></div>
                <div class="col"><div class="card bg-success text-white border-0 rounded-3"><div class="card-body py-3"><div class="small opacity-75">Active</div><div class="fs-3 fw-bold">{{ $stats['active'] }}</div></div></div>
                <div class="col"><div class="card bg-danger text-white border-0 rounded-3"><div class="card-body py-3"><div class="small opacity-75">Inactive</div><div class="fs-3 fw-bold">{{ $stats['inactive'] }}</div></div></div>
                <div class="col"><div class="card bg-info text-white border-0 rounded-3"><div class="card-body py-3"><div class="small opacity-75">New this month</div><div class="fs-3 fw-bold">{{ $stats['new_this_month'] }}</div></div></div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="get" action="{{ route('customers.index') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="view" value="list">
                        <div class="col-md-5">
                            <label class="form-label small text-muted">Search</label>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, mobile, email">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $i => $customer)
                                    <tr>
                                        <td>{{ $customers->firstItem() + $i }}</td>
                                        <td>{{ $customer->code }}</td>
                                        <td><a href="{{ route('customers.edit', $customer) }}">{{ $customer->name }}</a></td>
                                        <td>{{ $customer->mobile }}</td>
                                        <td>{{ $customer->email ?? '—' }}</td>
                                        <td>{{ $customer->site->name ?? '—' }}</td>
                                        <td>
                                            @if ($customer->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCustomer{{ $customer->id }}"><i class="bx bx-edit"></i></button>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCustomer{{ $customer->id }}"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $customers->links() }}</div>
                </div>
            </div>

            @include('customers.partials.customer-modals', ['customers' => $customers, 'sites' => $sites])
        </div>
    </div>
</div>

{{-- Add customer (list view) --}}
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="{{ route('customers.store') }}">
                @csrf
                <input type="hidden" name="view" value="list">
                @if (request()->filled('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                @if (request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
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
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" selected>Active</option>
                                <option value="0">Inactive</option>
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
