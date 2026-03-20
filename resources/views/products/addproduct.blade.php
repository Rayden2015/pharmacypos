@extends('layouts.dash')
@section('content')
<div class="wrapper">
    <!--start page wrapper -->
    <div class="page-wrapper">
        <div class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Add Product</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Product</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="card">
                <div class="card-body p-4">
                    <h5 class="card-title">Add New Product</h5>
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
                        <div class="form-body mt-4">
                            <div class="row">
                                <div class="col-lg-7">
                                    <div class="border border-3 p-4 rounded">
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Medicine Name</label>
                                            <input type="text" class="form-control @error('product_name') is-invalid @enderror" id="product_name" name="product_name"
                                                value="{{ old('product_name') }}" placeholder="Enter Medicine Name" required maxlength="255" autocomplete="off" />
                                            @error('product_name')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="alias" class="form-label">Alias</label>
                                            <input type="text" class="form-control @error('alias') is-invalid @enderror" id="alias" name="alias"
                                                value="{{ old('alias') }}" placeholder="Alternate name, SKU, or short code (optional)" maxlength="255" />
                                            @error('alias')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="inputProductDescription" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="inputProductDescription" rows="6" name="description">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="product_image_add" class="form-label">Product image <span class="text-muted fw-normal">(optional)</span></label>
                                            <input id="product_image_add" type="file" class="form-control @error('product_img') is-invalid @enderror"
                                                name="product_img"
                                                accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp">
                                            @error('product_img')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            <div class="invalid-feedback d-none mt-1" id="product-img-error-client"></div>
                                            <p class="form-text mb-0" id="product-img-hint">JPG, PNG, GIF or WebP · maximum 5 MB</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="border border-3 p-4 rounded">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label for="brand" class="form-label d-inline-flex align-items-center flex-wrap gap-1">
                                                    Manufacturer
                                                    @include('products.partials.manufacturer-help')
                                                </label>
                                                <input type="text" class="form-control @error('brand') is-invalid @enderror" id="brand"
                                                    placeholder="e.g. company that makes this medicine"
                                                    name="brand"
                                                    value="{{ old('brand') }}"
                                                    required maxlength="255"
                                                    autocomplete="organization"
                                                    aria-describedby="brand-help-hint">
                                                @error('brand')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                                <div id="brand-help-hint" class="form-text">Who produced this product—not necessarily who you buy it from.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="Price" class="form-label">Selling Price</label>
                                                <input type="number" class="form-control @error('price') is-invalid @enderror" id="Price" name="price"
                                                    value="{{ old('price') }}" placeholder="0.00" required min="0" step="0.01" inputmode="decimal">
                                                @error('price')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="inputCompareatprice" class="form-label">Supplier Price</label>
                                                <input type="number" class="form-control @error('supplierprice') is-invalid @enderror" id="inputCompareatprice"
                                                    name="supplierprice" value="{{ old('supplierprice') }}" placeholder="0.00" min="0" step="0.01" inputmode="decimal">
                                                @error('supplierprice')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="quantity" class="form-label d-inline-flex align-items-center flex-wrap gap-1">
                                                    On-hand quantity
                                                    @include('products.partials.inventory-help', ['kind' => 'on_hand'])
                                                </label>
                                                <input type="number" name="quantity" class="form-control @error('quantity') is-invalid @enderror" id="quantity"
                                                    value="{{ old('quantity') }}"
                                                    placeholder="Stock count in your unit of measure" required min="0" step="1">
                                                @error('quantity')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="stock_alert" class="form-label d-inline-flex align-items-center flex-wrap gap-1">
                                                    Low stock alert level
                                                    @include('products.partials.inventory-help', ['kind' => 'alert'])
                                                </label>
                                                <input type="number" name="stock_alert" class="form-control @error('stock_alert') is-invalid @enderror" id="stock_alert"
                                                    placeholder="Reorder / warning threshold" required min="0" step="1" value="{{ old('stock_alert', '100') }}">
                                                @error('stock_alert')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="expiredate" class="form-label">Expire Date</label>
                                                <input type="date" name="expiredate" class="form-control @error('expiredate') is-invalid @enderror" id="expiredate"
                                                    value="{{ old('expiredate') }}" required>
                                                @error('expiredate')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="product_form" class="form-label">Form</label>
                                                @php $formOld = old('form'); @endphp
                                                <select name="form" id="product_form" class="form-select @error('form') is-invalid @enderror" required>
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
                                                @error('form')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="unit_of_measure" class="form-label d-inline-flex align-items-center flex-wrap gap-1">
                                                    Unit of measure
                                                    @include('products.partials.packaging-help')
                                                </label>
                                                @include('products.partials.unit-of-measure-select', ['id' => 'unit_of_measure', 'selected' => old('unit_of_measure')])
                                            </div>
                                            <div class="col-md-6">
                                                <label for="volume" class="form-label">Volume / pack size</label>
                                                <input type="text" class="form-control @error('volume') is-invalid @enderror" id="volume" name="volume"
                                                    value="{{ old('volume') }}"
                                                    maxlength="128"
                                                    placeholder="e.g. 500 ml, 30 tablets, 10 ml"
                                                    aria-describedby="volume-hint">
                                                @error('volume')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                                <div id="volume-hint" class="form-text">Strength, liquid volume, or count per sellable unit.</div>
                                            </div>
                                            {{-- <div class="col-12">
                                                <label for="inputProductType" class="form-label">Product Type</label>
                                                <select class="form-select" id="inputProductType">
                                                    <option></option>
                                                    <option value="1">One</option>
                                                    <option value="2">Two</option>
                                                    <option value="3">Three</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="inputVendor" class="form-label">Vendor</label>
                                                <select class="form-select" id="inputVendor">
                                                    <option></option>
                                                    <option value="1">One</option>
                                                    <option value="2">Two</option>
                                                    <option value="3">Three</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="inputCollection" class="form-label">Collection</label>
                                                <select class="form-select" id="inputCollection">
                                                    <option></option>
                                                    <option value="1">One</option>
                                                    <option value="2">Two</option>
                                                    <option value="3">Three</option>
                                                </select>
                                            </div> --}}
                                            
                                            <div class="col-12">
                                                <div class="d-grid">
                                                    <button type="submit"
                                                    class="btn btn-primary px-5">Register</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end row-->
                        </div>
                    </form>
                </div>
            </div>


        </div>
    </div>
</div>
    <!--end page wrapper -->
@endsection

@section('script')
<script>
(function () {
    var form = document.getElementById('add-product-form');
    if (!form) return;

    var fileInput = document.getElementById('product_image_add');
    var feedbackEl = document.getElementById('product-img-error-client');
    var hintEl = document.getElementById('product-img-hint');
    var maxBytes = 5 * 1024 * 1024;
    var allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    function extOk(name) {
        return /\.(jpe?g|png|gif|webp)$/i.test(name || '');
    }

    function showFileError(msg) {
        if (!feedbackEl) return;
        feedbackEl.textContent = msg;
        feedbackEl.classList.remove('d-none');
        feedbackEl.style.display = 'block';
        if (fileInput) fileInput.classList.add('is-invalid');
    }

    function clearFileError() {
        if (!feedbackEl) return;
        feedbackEl.textContent = '';
        feedbackEl.classList.add('d-none');
        feedbackEl.style.display = 'none';
        if (fileInput) fileInput.classList.remove('is-invalid');
    }

    function validateImageFile(file) {
        if (!file) return true;
        if (file.size > maxBytes) {
            showFileError('Image is too large (max 5 MB).');
            return false;
        }
        var mime = (file.type || '').toLowerCase();
        if (mime && allowedMime.indexOf(mime) === -1) {
            showFileError('Use a JPG, PNG, GIF, or WebP image.');
            return false;
        }
        if (!mime && !extOk(file.name)) {
            showFileError('Use a JPG, PNG, GIF, or WebP image.');
            return false;
        }
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
                    hintEl.textContent = 'Selected: ' + this.files[0].name + ' (' + mb + ' MB) · JPG, PNG, GIF or WebP · max 5 MB';
                }
            } else if (hintEl) {
                hintEl.textContent = 'JPG, PNG, GIF or WebP · maximum 5 MB';
            }
        });
    }

    form.addEventListener('submit', function (e) {
        clearFileError();

        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (fileInput && fileInput.files && fileInput.files[0]) {
            if (!validateImageFile(fileInput.files[0])) {
                e.preventDefault();
                e.stopPropagation();
            }
        }

        form.classList.add('was-validated');
    }, false);
})();
</script>
@endsection
