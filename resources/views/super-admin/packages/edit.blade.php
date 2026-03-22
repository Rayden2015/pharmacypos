@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')
        <div class="card radius-10" style="max-width: 36rem;">
            <div class="card-body">
                <h5 class="mb-3">Edit package</h5>
                <form method="post" action="{{ route('super-admin.packages.update', $package) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $package->name) }}" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing cycle <span class="text-danger">*</span></label>
                        <select name="billing_cycle" class="form-select" required>
                            <option value="monthly" @selected(old('billing_cycle', $package->billing_cycle) === 'monthly')>Monthly</option>
                            <option value="yearly" @selected(old('billing_cycle', $package->billing_cycle) === 'yearly')>Yearly</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (USD) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $package->price) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Billing days</label>
                        <input type="number" name="billing_days" class="form-control" value="{{ old('billing_days', $package->billing_days) }}" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $package->sort_order) }}" min="0">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('super-admin.packages.index') }}" class="btn btn-light">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
