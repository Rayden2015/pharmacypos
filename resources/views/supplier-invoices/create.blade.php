@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Record vendor invoice</div>
                </div>
                @include('inc.msg')

                <div class="card radius-10">
                    <div class="card-body">
                        <form method="post" action="{{ route('supplier-invoices.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                    <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                                        <option value="">— Select —</option>
                                        @foreach ($suppliers as $s)
                                            <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->supplier_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Invoice number <span class="text-danger">*</span></label>
                                    <input type="text" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number') }}" required maxlength="64">
                                    @error('invoice_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Invoice date <span class="text-danger">*</span></label>
                                    <input type="date" name="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror" value="{{ old('invoice_date', now()->toDateString()) }}" required>
                                    @error('invoice_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Due date</label>
                                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}">
                                    @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment method</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="">—</option>
                                        @foreach ($paymentMethods as $pm)
                                            <option value="{{ $pm }}" @selected(old('payment_method') === $pm)>{{ $pm }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Invoice total ({{ $currencySymbol }}) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" name="total_amount" class="form-control @error('total_amount') is-invalid @enderror" value="{{ old('total_amount') }}" required>
                                    @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount paid so far ({{ $currencySymbol }}) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" name="paid_amount" class="form-control @error('paid_amount') is-invalid @enderror" value="{{ old('paid_amount', '0') }}" required>
                                    @error('paid_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                <a href="{{ route('supplier-invoices.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
