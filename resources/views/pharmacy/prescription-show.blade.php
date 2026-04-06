@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Prescription</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item"><a href="{{ route('pharmacy.prescriptions') }}">Prescriptions</a></li>
                                <li class="breadcrumb-item active" aria-current="page">#{{ $prescription->id }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                @include('pharmacy.partials.care-nav', ['active' => 'prescriptions'])

                <div class="card mb-4">
                    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h6 class="mb-0">{{ $prescription->patient_name }}</h6>
                            <p class="text-muted small mb-0">{{ __('Logged') }} {{ $prescription->created_at->format('M j, Y H:i') }}</p>
                        </div>
                        <div>
                            @if ($prescription->status === 'completed')
                                <span class="badge bg-success">{{ __('Completed') }}</span>
                            @elseif ($prescription->status === 'cancelled')
                                <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                            @else
                                <span class="badge bg-info text-dark">{{ __('Pending') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">{{ __('Patient phone') }}</dt>
                            <dd class="col-sm-9">{{ $prescription->patient_phone ?? '—' }}</dd>
                            <dt class="col-sm-3">{{ __('Rx / file #') }}</dt>
                            <dd class="col-sm-9">{{ $prescription->rx_number ?? '—' }}</dd>
                            <dt class="col-sm-3">{{ __('Doctor') }}</dt>
                            <dd class="col-sm-9">
                                @if ($prescription->doctor)
                                    <a href="{{ route('pharmacy.prescriptions', ['doctor_id' => $prescription->doctor_id]) }}">{{ $prescription->doctor->displayLabel() }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="col-sm-3">{{ __('Branch') }}</dt>
                            <dd class="col-sm-9">{{ $prescription->site->name ?? '—' }}</dd>
                            <dt class="col-sm-3">{{ __('Logged by') }}</dt>
                            <dd class="col-sm-9">{{ $prescription->user->name ?? '—' }}</dd>
                            @if ($prescription->dispensed_at)
                                <dt class="col-sm-3">{{ __('Dispensed at') }}</dt>
                                <dd class="col-sm-9">{{ $prescription->dispensed_at->format('M j, Y H:i') }}</dd>
                            @endif
                            <dt class="col-sm-3">{{ __('Notes') }}</dt>
                            <dd class="col-sm-9">@if ($prescription->notes){!! nl2br(e($prescription->notes)) !!}@else — @endif</dd>
                            @if ($prescription->attachment_path)
                                <dt class="col-sm-3">{{ __('Attachment') }}</dt>
                                <dd class="col-sm-9">
                                    <a href="{{ asset('storage/'.$prescription->attachment_path) }}" target="_blank" rel="noopener">{{ __('Open image') }}</a>
                                    <div class="mt-2 border rounded overflow-hidden bg-light" style="max-width: 28rem;">
                                        <img src="{{ asset('storage/'.$prescription->attachment_path) }}" alt="{{ __('Prescription image') }}" class="img-fluid d-block">
                                    </div>
                                </dd>
                            @endif
                        </dl>
                        @if ($prescription->status === 'pending')
                            <hr class="my-4">
                            <div class="d-flex flex-wrap gap-2">
                                <form action="{{ route('pharmacy.prescriptions.update', $prescription) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="btn btn-success">{{ __('Mark dispensed') }}</button>
                                </form>
                                <form action="{{ route('pharmacy.prescriptions.update', $prescription) }}" method="post" class="d-inline" onsubmit="return confirm(@json(__('Cancel this prescription?')));">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn btn-outline-secondary">{{ __('Cancel') }}</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
