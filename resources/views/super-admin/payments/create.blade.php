@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')
        <div class="card radius-10" style="max-width: 40rem;">
            <div class="card-body">
                <h5 class="mb-3">Record subscription payment</h5>
                <p class="small text-muted mb-3">Charges from tenants for SaaS packages. Not the same as tenant <strong>Medicines → Vendor payments</strong> (supplier invoices).</p>
                <form method="post" action="{{ route('super-admin.payments.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Tenant <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select" required>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link subscription (optional)</label>
                        <select name="tenant_subscription_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($subscriptions as $s)
                                <option value="{{ $s->id }}" @selected(old('tenant_subscription_id') == $s->id)>
                                    #{{ $s->id }} — {{ $s->company->company_name ?? '?' }} — {{ optional($s->subscriptionPackage)->name ?? 'plan' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Invoice reference</label>
                        <input type="text" name="invoice_reference" class="form-control" value="{{ old('invoice_reference') }}" maxlength="64" placeholder="INV001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment method</label>
                        <select name="payment_method" class="form-select">
                            <option value="">— Not specified —</option>
                            @foreach ($paymentMethods as $pm)
                                <option value="{{ $pm }}" @selected(old('payment_method') === $pm)>{{ $pm }}</option>
                            @endforeach
                        </select>
                        @error('payment_method')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach (['paid', 'unpaid', 'refunded'] as $st)
                                <option value="{{ $st }}" @selected(old('status', 'paid') === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paid at</label>
                        <input type="datetime-local" name="paid_at" class="form-control" value="{{ old('paid_at') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('super-admin.payments.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
