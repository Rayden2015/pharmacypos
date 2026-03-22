@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Add tenant</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        <div class="card radius-10" style="max-width: 40rem;">
            <div class="card-body">
                <form method="post" action="{{ route('super-admin.companies.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Company name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name') }}" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="company_email" class="form-control" value="{{ old('company_email') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="company_mobile" class="form-control" value="{{ old('company_mobile') }}" maxlength="64">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="company_address" class="form-control" rows="2" maxlength="2000">{{ old('company_address') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (account URL)</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" maxlength="191" placeholder="auto from name if empty">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial subscription package</label>
                        <select name="subscription_package_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($packages as $p)
                                <option value="{{ $p->id }}" @selected(old('subscription_package_id') == $p->id)>{{ $p->displayLabel() }} — ${{ number_format($p->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="{{ route('super-admin.companies.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
