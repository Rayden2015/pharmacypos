@extends('layouts.dash')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        @include('inc.msg')
        @include('super-admin.partials.nav-pills')
        <div class="card radius-10" style="max-width: 40rem;">
            <div class="card-body">
                <h5 class="mb-3">New subscription</h5>
                <form method="post" action="{{ route('super-admin.subscriptions.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Tenant <span class="text-danger">*</span></label>
                        <select name="company_id" class="form-select" required>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>{{ $c->company_name }} — {{ $c->company_email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Package <span class="text-danger">*</span></label>
                        <select name="subscription_package_id" class="form-select" required>
                            @foreach ($packages as $p)
                                <option value="{{ $p->id }}" @selected(old('subscription_package_id') == $p->id)>{{ $p->displayLabel() }} — ${{ number_format($p->price, 2) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach (['active', 'pending', 'expired', 'cancelled'] as $st)
                                <option value="{{ $st }}" @selected(old('status', 'active') === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount override</label>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="Defaults to package price">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment method</label>
                        <input type="text" name="payment_method" class="form-control" value="{{ old('payment_method') }}" placeholder="Credit Card, Paypal, …">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Starts at</label>
                            <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ends at</label>
                            <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('super-admin.subscriptions.index') }}" class="btn btn-light">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
