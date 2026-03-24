@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">{{ __('Edit doctor') }}</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('pharmacy.doctors.index') }}">{{ __('Doctors') }}</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="card">
                    <div class="card-body" style="max-width: 40rem;">
                        <form action="{{ route('pharmacy.doctors.update', $doctor) }}" method="post">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $doctor->name) }}" required maxlength="255">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Specialty') }}</label>
                                <input type="text" name="specialty" class="form-control" value="{{ old('specialty', $doctor->specialty) }}" maxlength="255">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Phone') }}</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $doctor->phone) }}" maxlength="50">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Email') }}</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $doctor->email) }}" maxlength="255">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label">{{ __('License / registration #') }}</label>
                                <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $doctor->license_number) }}" maxlength="120">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Hospital / clinic') }}</label>
                                <input type="text" name="hospital_or_clinic" class="form-control" value="{{ old('hospital_or_clinic', $doctor->hospital_or_clinic) }}" maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Address') }}</label>
                                <textarea name="address" class="form-control" rows="2" maxlength="2000">{{ old('address', $doctor->address) }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="5000">{{ old('notes', $doctor->notes) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                            <a href="{{ route('pharmacy.doctors.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
