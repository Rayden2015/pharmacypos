@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Stock transfer</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Stock transfer</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                            <div>
                                <h5 class="card-title mb-1">Move stock between branches</h5>
                                <p class="text-muted small mb-0">
                                    Quantity is removed from the source site and added to the destination. Totals on the product record stay in sync with the sum across all sites.
                                </p>
                            </div>
                            <a href="{{ route('sites.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('Manage branches') }}</a>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <form method="post" action="{{ route('inventory.stock-transfer.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">From site <span class="text-danger">*</span></label>
                                    <select name="from_site_id" class="form-select" required>
                                        <option value="">— Select —</option>
                                        @foreach ($sites as $s)
                                            <option value="{{ $s->id }}" {{ (string) old('from_site_id') === (string) $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}@if($s->code) ({{ $s->code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">To site <span class="text-danger">*</span></label>
                                    <select name="to_site_id" class="form-select" required>
                                        <option value="">— Select —</option>
                                        @foreach ($sites as $s)
                                            <option value="{{ $s->id }}" {{ (string) old('to_site_id') === (string) $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}@if($s->code) ({{ $s->code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Product <span class="text-danger">*</span></label>
                                    <select name="product_id" class="single-select w-100" required data-placeholder="Select product">
                                        <option value=""></option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" {{ (string) old('product_id') === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->product_name }} — total on hand {{ $p->quantity }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" class="form-control" min="1" step="1" value="{{ old('quantity') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Note</label>
                                    <textarea name="note" class="form-control" rows="2" maxlength="500" placeholder="Optional reference for this transfer">{{ old('note') }}</textarea>
                                </div>
                            </div>
                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary px-4"><i class="bx bx-transfer"></i> Complete transfer</button>
                                <a href="{{ route('inventory.manage-stock') }}" class="btn btn-light">Back to manage stock</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
