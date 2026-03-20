@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Settings</div>
            </div>
            @include('inc.msg')
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Currency</h5>
                    <p class="text-muted small mb-4">Used on POS, product prices, receipts, and reports.</p>
                    <form action="{{ route('settings.update') }}" method="POST" class="row g-3" style="max-width: 36rem;">
                        @csrf
                        @method('put')
                        <div class="col-12">
                            <label for="currency_symbol" class="form-label">Currency symbol</label>
                            <input type="text" name="currency_symbol" id="currency_symbol" class="form-control @error('currency_symbol') is-invalid @enderror"
                                value="{{ old('currency_symbol', $currencySymbol) }}" maxlength="16" required
                                placeholder="e.g. GH₵, $, ₦, £">
                            @error('currency_symbol')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="currency_code" class="form-label">Currency code <span class="text-muted">(optional)</span></label>
                            <input type="text" name="currency_code" id="currency_code" class="form-control @error('currency_code') is-invalid @enderror"
                                value="{{ old('currency_code', $currencyCode) }}" maxlength="8"
                                placeholder="e.g. GHS, USD, NGN">
                            @error('currency_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
