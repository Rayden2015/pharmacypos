@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Edit role: {{ $role->name }}</div>
            </div>
            @include('inc.msg')
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('roles.update', $role) }}" method="post" class="row g-3">
                        @csrf
                        @method('put')
                        @php
                            $system = in_array($role->name, ['Tenant Admin', 'Branch Manager', 'Cashier', 'Supervisor'], true);
                        @endphp
                        @if (! $system)
                            <div class="col-12 col-md-6">
                                <label class="form-label">Role name</label>
                                <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="64">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @else
                            <div class="col-12">
                                <p class="text-muted small mb-0">Built-in role name cannot be changed.</p>
                            </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label">Permissions</label>
                            @include('tenant.roles.partials.permission-fields', ['assigned' => old('permissions', $assigned)])
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
