@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Create role</div>
            </div>
            @include('inc.msg')
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="post" class="row g-3">
                        @csrf
                        <div class="col-12 col-md-6">
                            <label class="form-label">Role name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required maxlength="64" placeholder="e.g. Senior cashier">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Permissions</label>
                            @include('tenant.roles.partials.permission-fields', ['assigned' => old('permissions', [])])
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Create</button>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
