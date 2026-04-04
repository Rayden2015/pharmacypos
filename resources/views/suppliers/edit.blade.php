@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Edit supplier</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Edit</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="card">
                    <div class="card-body" style="max-width: 36rem;">
                        <form action="{{ route('suppliers.update', $supplier) }}" method="post">
                            @csrf
                            @method('PUT')
                            @if(auth()->user()->isSuperAdmin())
                                <div class="mb-3">
                                    <label class="form-label">Organization <span class="text-danger">*</span></label>
                                    <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required>
                                        @foreach($companies as $c)
                                            <option value="{{ $c->id }}" @selected((int) old('company_id', $supplier->company_id) === (int) $c->id)>{{ $c->company_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Supplier name <span class="text-danger">*</span></label>
                                <input type="text" name="supplier_name" class="form-control @error('supplier_name') is-invalid @enderror" value="{{ old('supplier_name', $supplier->supplier_name) }}" required maxlength="255">
                                @error('supplier_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" maxlength="2000">{{ old('address', $supplier->address) }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $supplier->mobile) }}" maxlength="50">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}" maxlength="255">
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Back</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
