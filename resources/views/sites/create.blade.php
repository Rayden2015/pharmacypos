@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Add site</div>
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
                                            <option value="{{ $co->id }}" @selected(old('company_id', auth()->user()->company_id) == $co->id)>{{ $co->company_name }}</option>
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
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">Set as default site</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Create</button>
                            <a href="{{ route('sites.index') }}" class="btn btn-light">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
