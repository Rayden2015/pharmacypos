@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Add tenant admin</div>
        </div>
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')

        <p class="text-muted small mb-3">Create a <strong>Tenant admin</strong> user for an <strong>existing</strong> organization. They receive full permissions for that tenant (same as the admin created with a new tenant).</p>

        <div class="card radius-10" style="max-width: 44rem;">
            <div class="card-body">
                <form method="post" action="{{ route('super-admin.tenant-admins.store') }}" id="tenantAdminForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Tenant (company) <span class="text-danger">*</span></label>
                        <select name="company_id" id="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                            <option value="">— Select tenant —</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}" @selected(old('company_id', $selectedCompanyId) == $c->id)>{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Home branch <span class="text-danger">*</span></label>
                        <select name="site_id" id="site_id" class="form-select @error('site_id') is-invalid @enderror" required>
                            <option value="">— Select branch —</option>
                            @foreach ($sites as $s)
                                <option value="{{ $s->id }}" data-company="{{ $s->company_id }}" @selected((string) old('site_id') === (string) $s->id)>
                                    {{ $s->name }}@if($s->code) ({{ $s->code }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Must belong to the selected tenant. If there is no branch yet, create one under <a href="{{ route('sites.create') }}">Sites</a> first.</div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3">Administrator account</h6>
                    <div class="mb-3">
                        <label class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" required maxlength="255">
                        @error('admin_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Login email <span class="text-danger">*</span></label>
                        <input type="email" name="admin_email" class="form-control @error('admin_email') is-invalid @enderror" value="{{ old('admin_email') }}" required>
                        @error('admin_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="admin_mobile" class="form-control @error('admin_mobile') is-invalid @enderror" value="{{ old('admin_mobile') }}" maxlength="32">
                        @error('admin_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="admin_password" class="form-control @error('admin_password') is-invalid @enderror" required minlength="8" autocomplete="new-password">
                        @error('admin_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password <span class="text-danger">*</span></label>
                        <input type="password" name="admin_password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-primary">Create tenant admin</button>
                    <a href="{{ route('super-admin.companies.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var companyEl = document.getElementById('company_id');
    var siteEl = document.getElementById('site_id');
    if (!companyEl || !siteEl) return;

    function filterSites() {
        var cid = companyEl.value;
        var firstOk = null;
        for (var i = 0; i < siteEl.options.length; i++) {
            var opt = siteEl.options[i];
            if (!opt.value) {
                opt.disabled = false;
                continue;
            }
            var ok = opt.getAttribute('data-company') === cid;
            opt.disabled = !ok;
            if (ok && !firstOk) firstOk = opt;
        }
        if (cid && siteEl.selectedOptions.length && siteEl.selectedOptions[0].disabled) {
            siteEl.value = firstOk ? firstOk.value : '';
        }
    }

    companyEl.addEventListener('change', filterSites);
    document.addEventListener('DOMContentLoaded', filterSites);
})();
</script>
@endsection
