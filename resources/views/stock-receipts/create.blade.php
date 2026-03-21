@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Receive stock</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ url('/home') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Receive</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h5 class="card-title mb-1">Goods receipt</h5>
                                <p class="text-muted small mb-0">
                                    Record stock you are adding for a SKU: quantity received, batch traceability, and supplier / document references.
                                    On-hand balance increases by the quantity you enter.
                                </p>
                            </div>
                            <a href="{{ route('inventory.receipts.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bx bx-list-ul"></i> Receipt history
                            </a>
                        </div>
                        <hr>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Please fix the following:</strong>
                                <ul class="mb-0 mt-2 small">
                                    @foreach ($errors->all() as $err)
                                        <li>{{ $err }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('inventory.receive.store') }}" method="post" class="mt-3">
                            @csrf

                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="border rounded p-4 h-100">
                                        <h6 class="text-uppercase text-muted small mb-3">Item &amp; quantity</h6>
                                        <div class="mb-3">
                                            <label for="site_id" class="form-label">Receive into site / branch</label>
                                            <select name="site_id" id="site_id" class="form-select @error('site_id') is-invalid @enderror">
                                                @foreach ($sites as $site)
                                                    <option value="{{ $site->id }}" {{ (string) old('site_id', $defaultSiteId) === (string) $site->id ? 'selected' : '' }}>
                                                        {{ $site->name }}@if($site->code) ({{ $site->code }})@endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Matches the active site in the header unless you pick another branch.</small>
                                            @error('site_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="product_id" class="form-label">Product <span class="text-danger">*</span></label>
                                            <select name="product_id" id="product_id" class="single-select w-100 @error('product_id') is-invalid @enderror"
                                                data-placeholder="Search or select product" required>
                                                <option value=""></option>
                                                @foreach ($products as $product)
                                                    <option value="{{ $product->id }}" {{ (string) old('product_id', $prefillProductId ?? '') === (string) $product->id ? 'selected' : '' }}>
                                                        {{ $product->product_name }}
                                                        @if ($product->alias) ({{ $product->alias }}) @endif
                                                        — on hand: {{ $product->quantity }}
                                                        @if ($product->packaging_label) · {{ $product->packaging_label }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('product_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label for="quantity" class="form-label">Quantity received <span class="text-danger">*</span></label>
                                            <input type="number" name="quantity" id="quantity" min="1" step="1" class="form-control @error('quantity') is-invalid @enderror"
                                                value="{{ old('quantity') }}" required placeholder="Units in this delivery">
                                            <small class="text-muted">Number of units to add to stock (not the new total).</small>
                                            @error('quantity')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="border rounded p-4 h-100">
                                        <h6 class="text-uppercase text-muted small mb-3">Batch &amp; dating</h6>
                                        <div class="mb-3">
                                            <label for="batch_number" class="form-label">Batch / lot number</label>
                                            <input type="text" name="batch_number" id="batch_number" maxlength="128" class="form-control @error('batch_number') is-invalid @enderror"
                                                value="{{ old('batch_number') }}" placeholder="e.g. BN2026-0142" autocomplete="off">
                                            @error('batch_number')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label for="expiry_date" class="form-label">Batch expiry date</label>
                                            <input type="date" name="expiry_date" id="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror"
                                                value="{{ old('expiry_date') }}">
                                            <small class="text-muted">For this batch/lot (optional if not applicable).</small>
                                            @error('expiry_date')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="border rounded p-4 h-100">
                                        <h6 class="text-uppercase text-muted small mb-3">Source &amp; documents</h6>
                                        <div class="mb-3">
                                            <label for="supplier_id" class="form-label">Supplier</label>
                                            <select name="supplier_id" id="supplier_id" class="single-select w-100 @error('supplier_id') is-invalid @enderror"
                                                data-placeholder="Optional — select supplier">
                                                <option value=""></option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->supplier_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('supplier_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label for="document_reference" class="form-label">Invoice / PO / delivery note #</label>
                                            <input type="text" name="document_reference" id="document_reference" maxlength="128"
                                                class="form-control @error('document_reference') is-invalid @enderror"
                                                value="{{ old('document_reference') }}" placeholder="e.g. INV-44021" autocomplete="off">
                                            @error('document_reference')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="border rounded p-4 h-100">
                                        <h6 class="text-uppercase text-muted small mb-3">When &amp; notes</h6>
                                        <div class="mb-3">
                                            <label for="received_at" class="form-label">Received date <span class="text-danger">*</span></label>
                                            <input type="date" name="received_at" id="received_at" required
                                                class="form-control @error('received_at') is-invalid @enderror"
                                                value="{{ old('received_at', date('Y-m-d')) }}">
                                            @error('received_at')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-0">
                                            <label for="notes" class="form-label">Notes</label>
                                            <textarea name="notes" id="notes" rows="3" maxlength="2000" class="form-control @error('notes') is-invalid @enderror"
                                                placeholder="Condition, cold chain, damages, remarks…">{{ old('notes') }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="bx bx-check"></i> Post receipt
                                </button>
                                <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
