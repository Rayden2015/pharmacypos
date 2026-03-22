@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Edit tenant</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        @if ($subscription)
            <div class="alert alert-light border mb-3">
                <span class="small text-muted">Latest subscription:</span>
                <strong>{{ optional($subscription->subscriptionPackage)->name ?? '—' }}</strong>
                · {{ ucfirst($subscription->status) }}
                @if ($subscription->ends_at)
                    · ends {{ $subscription->ends_at->format('M j, Y') }}
                @endif
            </div>
        @endif

        <div class="card radius-10" style="max-width: 40rem;">
            <div class="card-body">
                <form method="post" action="{{ route('super-admin.companies.update', $company) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Company name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $company->company_name) }}" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $company->company_email) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="company_mobile" class="form-control" value="{{ old('company_mobile', $company->company_mobile) }}" maxlength="64">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="company_address" class="form-control" rows="2" maxlength="2000">{{ old('company_address', $company->company_address) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="{{ old('slug', $company->slug) }}" maxlength="191">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $company->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('super-admin.companies.index') }}" class="btn btn-light">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
