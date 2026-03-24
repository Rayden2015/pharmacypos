@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">{{ __('Doctors') }}</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ __('Doctors') }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                @include('pharmacy.partials.care-nav', ['active' => 'doctors'])

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="text-muted small mb-0">{{ __('Prescribing physicians for Rx logs and records.') }}</p>
                    <a href="{{ route('pharmacy.doctors.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>{{ __('Add doctor') }}</a>
                </div>

                <div class="card mb-3">
                    <div class="card-body py-2">
                        <form method="get" action="{{ route('pharmacy.doctors.index') }}" class="row g-2 align-items-end">
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label small mb-0">{{ __('Search') }}</label>
                                <input type="search" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="{{ __('Name, specialty, phone, license…') }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
                            </div>
                            @if ($search !== '')
                                <div class="col-auto">
                                    <a href="{{ route('pharmacy.doctors.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear') }}</a>
                                </div>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Specialty') }}</th>
                                        <th>{{ __('Phone') }}</th>
                                        <th>{{ __('Email') }}</th>
                                        <th>{{ __('License #') }}</th>
                                        <th>{{ __('Hospital / clinic') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($doctors as $d)
                                        <tr>
                                            <td class="fw-semibold">{{ $d->name }}</td>
                                            <td class="small">{{ $d->specialty ?? '—' }}</td>
                                            <td class="small">{{ $d->phone ?? '—' }}</td>
                                            <td class="small">{{ $d->email ?? '—' }}</td>
                                            <td class="small">{{ $d->license_number ?? '—' }}</td>
                                            <td class="small">{{ $d->hospital_or_clinic ?? '—' }}</td>
                                            <td class="text-end text-nowrap">
                                                @if ($d->prescriptions_count > 0)
                                                    <a href="{{ route('pharmacy.prescriptions', ['doctor_id' => $d->id]) }}" class="badge bg-light text-dark border">{{ $d->prescriptions_count }}</a>
                                                @else
                                                    <span class="text-muted small">0</span>
                                                @endif
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('pharmacy.doctors.edit', $d) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                                <form action="{{ route('pharmacy.doctors.destroy', $d) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('Delete this doctor?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">{{ __('No doctors yet. Add prescribers to attach them to prescriptions.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $doctors->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
