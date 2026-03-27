@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Prescriptions</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Prescriptions</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                @include('pharmacy.partials.care-nav', ['active' => 'prescriptions'])

                @if ($doctorCount === 0)
                    <div class="alert alert-info border-0 mb-3" role="alert">
                        <strong>{{ __('No prescribers yet.') }}</strong>
                        {{ __('Add doctors to your directory so you can attach them to each Rx for clearer records and reporting.') }}
                        <a href="{{ route('pharmacy.doctors.create') }}" class="alert-link">{{ __('Add a doctor') }}</a>
                    </div>
                @endif

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Log a prescription</h6>
                        <p class="text-muted small mb-0">{{ __('Queue items for dispensing; link a prescriber when available. Status feeds the dashboard Rx chart.') }}</p>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('pharmacy.prescriptions.store') }}" method="post" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label">Patient name <span class="text-danger">*</span></label>
                                <input type="text" name="patient_name" class="form-control @error('patient_name') is-invalid @enderror" value="{{ old('patient_name') }}" required maxlength="255">
                                @error('patient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Phone</label>
                                <input type="text" name="patient_phone" class="form-control" value="{{ old('patient_phone') }}" maxlength="50">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Doctor</label>
                                <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror">
                                    <option value="">—</option>
                                    @foreach ($doctors as $doc)
                                        <option value="{{ $doc->id }}" {{ (string) old('doctor_id') === (string) $doc->id ? 'selected' : '' }}>{{ $doc->displayLabel() }}</option>
                                    @endforeach
                                </select>
                                @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Rx / file #</label>
                                <input type="text" name="rx_number" class="form-control" value="{{ old('rx_number') }}" maxlength="100">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="5000" placeholder="Optional">
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100"><i class="bx bx-plus me-1"></i>Add</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body py-2">
                        <form method="get" action="{{ route('pharmacy.prescriptions') }}" class="row g-2 align-items-end">
                            <div class="col-md-3 col-lg-2">
                                <label class="form-label small mb-0">{{ __('Status') }}</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                    <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small mb-0">{{ __('Doctor') }}</label>
                                <select name="doctor_id" class="form-select form-select-sm">
                                    <option value="">{{ __('All prescribers') }}</option>
                                    @foreach ($doctors as $doc)
                                        <option value="{{ $doc->id }}" {{ (string) ($filters['doctor_id'] ?? '') === (string) $doc->id ? 'selected' : '' }}>{{ $doc->displayLabel() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-3">
                                <label class="form-label small mb-0">{{ __('Search') }}</label>
                                <input type="search" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Patient, Rx #, phone…') }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Apply') }}</button>
                            </div>
                            @if (($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '' || ($filters['doctor_id'] ?? '') !== '')
                                <div class="col-auto">
                                    <a href="{{ route('pharmacy.prescriptions') }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear filters') }}</a>
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
                                        <th>When</th>
                                        <th>Patient</th>
                                        <th>Phone</th>
                                        <th>Doctor</th>
                                        <th>Rx #</th>
                                        <th>Status</th>
                                        <th>Logged by</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($prescriptions as $rx)
                                        <tr>
                                            <td class="text-nowrap small">{{ $rx->created_at->format('M j, Y H:i') }}</td>
                                            <td><a href="{{ route('pharmacy.prescriptions.show', $rx) }}">{{ $rx->patient_name }}</a></td>
                                            <td class="small">{{ $rx->patient_phone ?? '—' }}</td>
                                            <td class="small">
                                                @if ($rx->doctor)
                                                    <a href="{{ route('pharmacy.prescriptions', ['doctor_id' => $rx->doctor_id]) }}">{{ $rx->doctor->displayLabel() }}</a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="small">{{ $rx->rx_number ?? '—' }}</td>
                                            <td>
                                                @if ($rx->status === 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @elseif ($rx->status === 'cancelled')
                                                    <span class="badge bg-danger">Cancelled</span>
                                                @else
                                                    <span class="badge bg-info text-dark">Pending</span>
                                                @endif
                                            </td>
                                            <td class="small">{{ $rx->user->name ?? '—' }}</td>
                                            <td class="text-end">
                                                @if ($rx->status === 'pending')
                                                    <form action="{{ route('pharmacy.prescriptions.update', $rx) }}" method="post" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Dispense</button>
                                                    </form>
                                                    <form action="{{ route('pharmacy.prescriptions.update', $rx) }}" method="post" class="d-inline" onsubmit="return confirm('Cancel this prescription?');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Cancel</button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No prescriptions yet. Add one above or seed demo data.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $prescriptions->withQueryString()->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
