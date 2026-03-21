@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Stock adjustment</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Stock adjustment</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                @include('inc.msg')
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <p class="text-muted small mb-0">Use for physical counts, damage, write-offs, or small corrections. For supplier deliveries with batch details, use <a href="{{ route('inventory.receive.create') }}">Receive stock</a>.</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body p-4">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <form method="post" action="{{ route('inventory.stock-adjustment.store') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Site / branch <span class="text-danger">*</span></label>
                                    <select name="site_id" class="form-select" required>
                                        @foreach ($sites as $s)
                                            <option value="{{ $s->id }}" {{ (string) old('site_id', $defaultSiteId) === (string) $s->id ? 'selected' : '' }}>
                                                {{ $s->name }}@if($s->code) ({{ $s->code }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Adjusts on-hand at this location only. Use the site switcher in the header to set your default POS / receive site.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Product <span class="text-danger">*</span></label>
                                    <select name="product_id" class="single-select w-100" required data-placeholder="Select product">
                                        <option value=""></option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" {{ (string) old('product_id') === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->product_name }} — on hand {{ $p->quantity }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Direction <span class="text-danger">*</span></label>
                                    <select name="direction" class="form-select" required>
                                        <option value="add" {{ old('direction') === 'add' ? 'selected' : '' }}>Add to stock</option>
                                        <option value="remove" {{ old('direction') === 'remove' ? 'selected' : '' }}>Remove from stock</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" class="form-control" min="1" step="1" value="{{ old('quantity') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <textarea name="reason" class="form-control" rows="2" maxlength="500" required placeholder="e.g. Annual stock count variance, damaged units, sampling…">{{ old('reason') }}</textarea>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary px-4">Save adjustment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
