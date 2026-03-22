@extends('layouts.dash')
@section('content')
@php
    $catalog = $formCatalog ?? config('product_form');
    $catGroups = $catalog['categories'] ?? [];
@endphp
<div class="wrapper">
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Create product</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Create</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-left-arrow-alt"></i> Back to products</a>
                </div>
            </div>

            <div class="card radius-10 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h5 class="mb-0">Create product</h5>
                        <span class="text-muted small">Fields marked <span class="text-danger">*</span> are required.</span>
                    </div>
                    <hr>

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Please fix the following:</strong>
                            <ul class="mb-0 mt-2 small">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="add-product-form" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf

                        <div class="accordion accordion-flush border rounded" id="productAccordion">
                            {{-- Product information --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingInfo">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInfo" aria-expanded="true" aria-controls="collapseInfo">
                                        <i class="bx bx-package me-2"></i> Product information
                                    </button>
                                </h2>
                                <div id="collapseInfo" class="accordion-collapse collapse show" aria-labelledby="headingInfo" data-bs-parent="#productAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Store (branch) <span class="text-danger">*</span></label>
                                                <select name="site_id" class="form-select @error('site_id') is-invalid @enderror" required>
                                                    @foreach ($sites as $s)
                                                        <option value="{{ $s->id }}" {{ (string) old('site_id', $default_site_id ?? null) === (string) $s->id ? 'selected' : '' }}>
                                                            {{ $s->name }}@if($s->code) · {{ $s->code }}@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('site_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                <div class="form-text">Initial stock is posted to this branch.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Warehouse <span class="text-muted">(optional)</span></label>
                                                <input type="text" name="warehouse_note" class="form-control @error('warehouse_note') is-invalid @enderror" value="{{ old('warehouse_note') }}" placeholder="Shelf, bin, or cold room" maxlength="255">
                                                @error('warehouse_note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Medicine / product name <span class="text-danger">*</span></label>
                                                <input type="text" name="product_name" class="form-control @error('product_name') is-invalid @enderror" value="{{ old('product_name') }}" required maxlength="255" autocomplete="off">
                                                @error('product_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Slug <span class="text-muted">(optional)</span></label>
                                                <div class="input-group">
                                                    <input type="text" name="slug" id="field_slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="url-friendly-name" maxlength="255">
                                                    <button class="btn btn-outline-secondary" type="button" id="btn-gen-slug" title="Generate from name">Generate</button>
                                                </div>
                                                @error('slug')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">SKU <span class="text-muted">(optional)</span></label>
                                                <div class="input-group">
                                                    <input type="text" name="sku" id="field_sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}" maxlength="64" autocomplete="off">
                                                    <button class="btn btn-outline-secondary" type="button" id="btn-gen-sku">Generate</button>
                                                </div>
                                                @error('sku')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Item code <span class="text-muted">(optional)</span></label>
                                                <div class="input-group">
                                                    <input type="text" name="item_code" id="field_item_code" class="form-control @error('item_code') is-invalid @enderror" value="{{ old('item_code') }}" maxlength="64">
                                                    <button class="btn btn-outline-secondary" type="button" id="btn-gen-item">Generate</button>
                                                </div>
                                                @error('item_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Selling type <span class="text-danger">*</span></label>
                                                <select name="selling_type" class="form-select @error('selling_type') is-invalid @enderror" required>
                                                    @foreach ($catalog['selling_types'] as $val => $label)
                                                        <option value="{{ $val }}" {{ old('selling_type', 'retail') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('selling_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Category <span class="text-muted">(optional)</span></label>
                                                <select name="category" id="field_category" class="form-select @error('category') is-invalid @enderror">
                                                    <option value="">— Select —</option>
                                                    @foreach (array_keys($catGroups) as $catName)
                                                        <option value="{{ $catName }}" {{ old('category') === $catName ? 'selected' : '' }}>{{ $catName }}</option>
                                                    @endforeach
                                                </select>
                                                @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Sub category <span class="text-muted">(optional)</span></label>
                                                @php
                                                    $subsInitial = old('category') ? ($catGroups[old('category')] ?? []) : [];
                                                @endphp
                                                <select name="sub_category" id="field_sub_category" class="form-select @error('sub_category') is-invalid @enderror">
                                                    <option value="">— Select —</option>
                                                    @foreach ($subsInitial as $sub)
                                                        <option value="{{ $sub }}" {{ old('sub_category') === $sub ? 'selected' : '' }}>{{ $sub }}</option>
                                                    @endforeach
                                                </select>
                                                @error('sub_category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label d-inline-flex align-items-center flex-wrap gap-1">Brand (manufacturer) <span class="text-danger">*</span>
                                                    @include('products.partials.manufacturer-help')
                                                </label>
                                                <select name="manufacturer_id" class="form-select @error('manufacturer_id') is-invalid @enderror" required>
                                                    <option value="" disabled {{ old('manufacturer_id') ? '' : 'selected' }} hidden>Select manufacturer</option>
                                                    @foreach ($manufacturers as $m)
                                                        <option value="{{ $m->id }}" {{ (string) old('manufacturer_id') === (string) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('manufacturer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                <div class="form-text"><a href="{{ route('manufacturers.create') }}" target="_blank">Add manufacturer</a></div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Unit of measure @include('products.partials.packaging-help')</label>
                                                @include('products.partials.unit-of-measure-select', ['id' => 'unit_of_measure', 'selected' => old('unit_of_measure')])
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Barcode symbology <span class="text-muted">(optional)</span></label>
                                                <select name="barcode_symbology" class="form-select @error('barcode_symbology') is-invalid @enderror">
                                                    @foreach ($catalog['barcode_symbologies'] as $val => $label)
                                                        <option value="{{ $val }}" {{ old('barcode_symbology') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('barcode_symbology')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Description <span class="text-muted">(max 60 words)</span></label>
                                                <textarea name="description" id="field_description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="Short description for listings">{{ old('description') }}</textarea>
                                                @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                <div class="form-text"><span id="desc-word-count">0</span> / 60 words</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Pricing & stocks --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingPrice">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrice" aria-expanded="false" aria-controls="collapsePrice">
                                        <i class="bx bx-dollar-circle me-2"></i> Pricing &amp; stocks
                                    </button>
                                </h2>
                                <div id="collapsePrice" class="accordion-collapse collapse" aria-labelledby="headingPrice" data-bs-parent="#productAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label d-block">Product type <span class="text-danger">*</span></label>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="product_type" id="pt_single" value="single" {{ old('product_type', 'single') === 'single' ? 'checked' : '' }} required>
                                                    <label class="form-check-label" for="pt_single">Single product</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="product_type" id="pt_variable" value="variable" {{ old('product_type') === 'variable' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="pt_variable">Variable product</label>
                                                </div>
                                                <p class="small text-muted mb-0" id="variable-hint" style="display:none;">Saved as one SKU for now; variants can be added later.</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Selling price <span class="text-danger">*</span></label>
                                                <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required min="0" step="0.01" inputmode="decimal">
                                                @error('price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Cost price <span class="text-muted">(purchase unit)</span></label>
                                                <input type="number" name="supplierprice" class="form-control @error('supplierprice') is-invalid @enderror" value="{{ old('supplierprice') }}" min="0" step="0.01" inputmode="decimal">
                                                @error('supplierprice')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tax type <span class="text-muted">(optional)</span></label>
                                                <select name="tax_type" class="form-select @error('tax_type') is-invalid @enderror">
                                                    @foreach ($catalog['tax_types'] as $val => $label)
                                                        <option value="{{ $val }}" {{ old('tax_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('tax_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Discount type <span class="text-danger">*</span></label>
                                                <select name="discount_type" id="discount_type" class="form-select @error('discount_type') is-invalid @enderror" required>
                                                    @foreach ($catalog['discount_types'] as $val => $label)
                                                        <option value="{{ $val }}" {{ old('discount_type', 'none') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('discount_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6" id="wrap_discount_value" style="display:none;">
                                                <label class="form-label">Discount value</label>
                                                <input type="number" name="discount_value" class="form-control @error('discount_value') is-invalid @enderror" value="{{ old('discount_value') }}" min="0" step="0.01" placeholder="0">
                                                @error('discount_value')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">On-hand quantity <span class="text-danger">*</span> @include('products.partials.inventory-help', ['kind' => 'on_hand'])</label>
                                                <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required min="0" step="1">
                                                @error('quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Low stock alert @include('products.partials.inventory-help', ['kind' => 'alert'])</label>
                                                <input type="number" name="stock_alert" class="form-control @error('stock_alert') is-invalid @enderror" value="{{ old('stock_alert', '100') }}" min="0" step="1">
                                                @error('stock_alert')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Form <span class="text-danger">*</span></label>
                                                @php $formOld = old('form'); @endphp
                                                <select name="form" class="form-select @error('form') is-invalid @enderror" required>
                                                    <option value="" disabled {{ $formOld ? '' : 'selected' }} hidden>Select form type</option>
                                                    <option value="Tablet" {{ $formOld === 'Tablet' ? 'selected' : '' }}>Tablet</option>
                                                    <option value="Capsules" {{ $formOld === 'Capsules' ? 'selected' : '' }}>Capsule</option>
                                                    <option value="Injection" {{ $formOld === 'Injection' ? 'selected' : '' }}>Injection</option>
                                                    <option value="Eye Drop" {{ $formOld === 'Eye Drop' ? 'selected' : '' }}>Eye Drop</option>
                                                    <option value="Suspension" {{ $formOld === 'Suspension' ? 'selected' : '' }}>Suspension</option>
                                                    <option value="Cream" {{ $formOld === 'Cream' ? 'selected' : '' }}>Cream</option>
                                                    <option value="Saline" {{ $formOld === 'Saline' ? 'selected' : '' }}>Saline</option>
                                                    <option value="Inhaler" {{ $formOld === 'Inhaler' ? 'selected' : '' }}>Inhaler</option>
                                                    <option value="Powder" {{ $formOld === 'Powder' ? 'selected' : '' }}>Powder</option>
                                                    <option value="Spray" {{ $formOld === 'Spray' ? 'selected' : '' }}>Spray</option>
                                                    <option value="Paediatric Drop" {{ $formOld === 'Paediatric Drop' ? 'selected' : '' }}>Paediatric Drop</option>
                                                    <option value="Nebuliser Solution" {{ $formOld === 'Nebuliser Solution' ? 'selected' : '' }}>Nebuliser Solution</option>
                                                    <option value="Powder for Suspension" {{ $formOld === 'Powder for Suspension' ? 'selected' : '' }}>Powder for Suspension</option>
                                                    <option value="Nasal Drops" {{ $formOld === 'Nasal Drops' ? 'selected' : '' }}>Nasal Drops</option>
                                                    <option value="Eye Ointment" {{ $formOld === 'Eye Ointment' ? 'selected' : '' }}>Eye Ointment</option>
                                                </select>
                                                @error('form')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Volume / pack size</label>
                                                <input type="text" name="volume" class="form-control @error('volume') is-invalid @enderror" value="{{ old('volume') }}" maxlength="128" placeholder="e.g. 500 ml, 30 tablets">
                                                @error('volume')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Preferred supplier <span class="text-muted">(optional)</span></label>
                                                <select name="preferred_supplier_id" class="form-select @error('preferred_supplier_id') is-invalid @enderror">
                                                    <option value="">— None —</option>
                                                    @foreach ($suppliers as $s)
                                                        <option value="{{ $s->id }}" {{ (string) old('preferred_supplier_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->supplier_name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('preferred_supplier_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Alias <span class="text-muted">(optional)</span></label>
                                                <input type="text" name="alias" class="form-control @error('alias') is-invalid @enderror" value="{{ old('alias') }}" maxlength="255" placeholder="Alternate search name">
                                                @error('alias')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Images --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingImg">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseImg" aria-expanded="false" aria-controls="collapseImg">
                                        <i class="bx bx-image me-2"></i> Images
                                    </button>
                                </h2>
                                <div id="collapseImg" class="accordion-collapse collapse" aria-labelledby="headingImg" data-bs-parent="#productAccordion">
                                    <div class="accordion-body">
                                        <div class="border border-2 border-dashed rounded p-4 text-center bg-light" id="image-drop-zone">
                                            <i class="bx bx-image-add fs-1 text-secondary"></i>
                                            <p class="mb-2 small text-muted">Drag &amp; drop an image here or choose a file</p>
                                            <input id="product_image_add" type="file" class="form-control @error('product_img') is-invalid @enderror" name="product_img" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp">
                                            @error('product_img')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            <div class="invalid-feedback d-none mt-1 text-start" id="product-img-error-client"></div>
                                            <p class="form-text mb-0" id="product-img-hint">JPG, PNG, GIF or WebP · maximum 5 MB</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Custom fields --}}
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingCustom">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCustom" aria-expanded="false" aria-controls="collapseCustom">
                                        <i class="bx bx-slider-alt me-2"></i> Custom fields
                                    </button>
                                </h2>
                                <div id="collapseCustom" class="accordion-collapse collapse" aria-labelledby="headingCustom" data-bs-parent="#productAccordion">
                                    <div class="accordion-body">
                                        <div class="bg-light border rounded px-3 py-2 mb-3 d-flex flex-wrap gap-3 align-items-center">
                                            <span class="small text-muted me-2">Include:</span>
                                            <div class="form-check form-check-inline mb-0">
                                                <input type="hidden" name="feature_warranty" value="0">
                                                <input class="form-check-input" type="checkbox" name="feature_warranty" id="feature_warranty" value="1" {{ old('feature_warranty', '0') === '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="feature_warranty">Warranties</label>
                                            </div>
                                            <div class="form-check form-check-inline mb-0">
                                                <input type="hidden" name="feature_expiry" value="0">
                                                <input class="form-check-input" type="checkbox" name="feature_expiry" id="feature_expiry" value="1" {{ old('feature_expiry', '1') === '1' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="feature_expiry">Expiry</label>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6" id="wrap_warranty">
                                                <label class="form-label">Warranty</label>
                                                <select name="warranty_term" class="form-select @error('warranty_term') is-invalid @enderror">
                                                    @foreach ($catalog['warranty_terms'] as $val => $label)
                                                        <option value="{{ $val }}" {{ old('warranty_term') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('warranty_term')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Manufactured date <span class="text-muted">(optional)</span></label>
                                                <input type="date" name="manufactured_date" class="form-control @error('manufactured_date') is-invalid @enderror" value="{{ old('manufactured_date') }}">
                                                @error('manufactured_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-6" id="wrap_expire">
                                                <label class="form-label">Expiry date <span class="text-danger" id="expire-required-star">*</span></label>
                                                <input type="date" name="expiredate" class="form-control @error('expiredate') is-invalid @enderror" value="{{ old('expiredate') }}" id="field_expiredate">
                                                @error('expiredate')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('products.index') }}" class="btn btn-light px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5">Add product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var subCatByCat = @json($catGroups);

    var form = document.getElementById('add-product-form');
    var cat = document.getElementById('field_category');
    var sub = document.getElementById('field_sub_category');
    if (cat && sub) {
        cat.addEventListener('change', function () {
            var key = cat.value;
            var opts = subCatByCat[key] || [];
            sub.innerHTML = '<option value=\"\">— Select —</option>';
            opts.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o;
                opt.textContent = o;
                sub.appendChild(opt);
            });
        });
    }

    function randHex(n) {
        var s = '';
        while (s.length < n) s += Math.floor(Math.random() * 16).toString(16);
        return s.toUpperCase();
    }

    document.getElementById('btn-gen-sku') && document.getElementById('btn-gen-sku').addEventListener('click', function () {
        var el = document.getElementById('field_sku');
        if (el) el.value = 'SKU-' + randHex(8);
    });
    document.getElementById('btn-gen-item') && document.getElementById('btn-gen-item').addEventListener('click', function () {
        var el = document.getElementById('field_item_code');
        if (el) el.value = 'IC-' + randHex(6);
    });
    document.getElementById('btn-gen-slug') && document.getElementById('btn-gen-slug').addEventListener('click', function () {
        var name = document.querySelector('[name=\"product_name\"]');
        var el = document.getElementById('field_slug');
        if (!el || !name) return;
        var t = (name.value || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        el.value = t || 'product';
    });

    var desc = document.getElementById('field_description');
    var wc = document.getElementById('desc-word-count');
    function countWords(text) {
        var plain = (text || '').replace(/<[^>]+>/g, ' ').trim();
        if (!plain) return 0;
        return plain.split(/\s+/).filter(Boolean).length;
    }
    function refreshDesc() {
        if (!desc || !wc) return;
        wc.textContent = countWords(desc.value);
    }
    if (desc) {
        desc.addEventListener('input', refreshDesc);
        refreshDesc();
    }

    var dt = document.getElementById('discount_type');
    var wrapDisc = document.getElementById('wrap_discount_value');
    function toggleDisc() {
        if (!dt || !wrapDisc) return;
        wrapDisc.style.display = (dt.value === 'none') ? 'none' : 'block';
    }
    if (dt) {
        dt.addEventListener('change', toggleDisc);
        toggleDisc();
    }

    var ptVar = document.getElementById('pt_variable');
    var ptSingle = document.getElementById('pt_single');
    var vHint = document.getElementById('variable-hint');
    function toggleVar() {
        if (!vHint) return;
        vHint.style.display = (ptVar && ptVar.checked) ? 'block' : 'none';
    }
    if (ptVar) ptVar.addEventListener('change', toggleVar);
    if (ptSingle) ptSingle.addEventListener('change', toggleVar);
    toggleVar();

    var fe = document.getElementById('feature_expiry');
    var star = document.getElementById('expire-required-star');
    var exp = document.getElementById('field_expiredate');
    function toggleExpiry() {
        if (!fe || !exp) return;
        var on = fe.checked;
        if (star) star.style.visibility = on ? 'visible' : 'hidden';
        exp.required = on;
        if (!on) exp.removeAttribute('required');
    }
    if (fe) {
        fe.addEventListener('change', toggleExpiry);
        toggleExpiry();
    }

    var zone = document.getElementById('image-drop-zone');
    var fileInput = document.getElementById('product_image_add');
    if (zone && fileInput) {
        ;['dragenter','dragover','dragleave','drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); e.stopPropagation(); }, false);
        });
        zone.addEventListener('dragover', function () { zone.classList.add('bg-white'); });
        zone.addEventListener('dragleave', function () { zone.classList.remove('bg-white'); });
        zone.addEventListener('drop', function (e) {
            zone.classList.remove('bg-white');
            var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
            if (f) { fileInput.files = e.dataTransfer.files; fileInput.dispatchEvent(new Event('change')); }
        });
    }

    if (!form) return;
    var feedbackEl = document.getElementById('product-img-error-client');
    var hintEl = document.getElementById('product-img-hint');
    var maxBytes = 5 * 1024 * 1024;
    var allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    function extOk(name) { return /\.(jpe?g|png|gif|webp)$/i.test(name || ''); }
    function showFileError(msg) {
        if (!feedbackEl) return;
        feedbackEl.textContent = msg;
        feedbackEl.classList.remove('d-none');
        if (fileInput) fileInput.classList.add('is-invalid');
    }
    function clearFileError() {
        if (!feedbackEl) return;
        feedbackEl.textContent = '';
        feedbackEl.classList.add('d-none');
        if (fileInput) fileInput.classList.remove('is-invalid');
    }
    function validateImageFile(file) {
        if (!file) return true;
        if (file.size > maxBytes) { showFileError('Image is too large (max 5 MB).'); return false; }
        var mime = (file.type || '').toLowerCase();
        if (mime && allowedMime.indexOf(mime) === -1) { showFileError('Use a JPG, PNG, GIF, or WebP image.'); return false; }
        if (!mime && !extOk(file.name)) { showFileError('Use a JPG, PNG, GIF, or WebP image.'); return false; }
        clearFileError();
        return true;
    }
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            clearFileError();
            if (this.files && this.files[0]) {
                validateImageFile(this.files[0]);
                if (hintEl && this.files[0].name) {
                    var mb = (this.files[0].size / (1024 * 1024)).toFixed(2);
                    hintEl.textContent = 'Selected: ' + this.files[0].name + ' (' + mb + ' MB)';
                }
            } else if (hintEl) hintEl.textContent = 'JPG, PNG, GIF or WebP · maximum 5 MB';
        });
    }
    form.addEventListener('submit', function (e) {
        clearFileError();
        if (desc && countWords(desc.value) > 60) {
            e.preventDefault();
            e.stopPropagation();
            alert('Description must be at most 60 words.');
            return;
        }
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        if (fileInput && fileInput.files && fileInput.files[0]) {
            if (!validateImageFile(fileInput.files[0])) { e.preventDefault(); e.stopPropagation(); }
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>
@endsection
