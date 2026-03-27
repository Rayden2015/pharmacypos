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
                            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @include('inc.msg')

            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Edit customer</h6>
                    <p class="text-muted small mb-0">{{ $customer->name }}</p>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('customers.update', $customer) }}" class="row g-3">
                        @csrf
                        @method('put')
                        <input type="hidden" name="stay_on_edit" value="1">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name', $customer->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" required value="{{ old('mobile', $customer->mobile) }}">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                        </div>
                        @if (auth()->user()->isSuperAdmin())
                            <div class="col-md-6">
                                <label class="form-label">Site / branch</label>
                                <select name="site_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($sites as $s)
                                        <option value="{{ $s->id }}" {{ (string) old('site_id', $customer->site_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', $customer->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $customer->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Save changes</button>
                            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Back to directory</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
