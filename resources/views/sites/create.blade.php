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
                                <li class="breadcrumb-item"><a href="{{ route('sites.index') }}">Branches</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Add new</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                @include('inc.msg')
                <div class="card">
                    <div class="card-body p-4" style="max-width: 36rem;">
                        <form method="post" action="{{ route('sites.store') }}">
                            @csrf
                            @if (auth()->user()->isSuperAdmin() && isset($companies) && $companies->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label">Tenant (company) <span class="text-danger">*</span></label>
                                    <select name="company_id" class="form-select" required>
                                        @foreach ($companies as $co)
                                            <option value="{{ $co->id }}" {{ (int) old('company_id', auth()->user()->company_id ?? 0) === (int) $co->id ? 'selected' : '' }}>{{ $co->company_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Code</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" maxlength="32" placeholder="e.g. WH2">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" maxlength="2000">{{ old('address') }}</textarea>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Branch manager</label>
                                    <input type="text" name="manager_name" class="form-control" value="{{ old('manager_name') }}" maxlength="255" placeholder="{{ __('Contact name at this branch') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" maxlength="64" placeholder="+1 …">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255" placeholder="branch@example.com">
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">Set as default site</label>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Create branch') }}</button>
                            <a href="{{ route('sites.index') }}" class="btn btn-light">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
