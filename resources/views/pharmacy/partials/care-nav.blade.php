{{-- Shared sub-navigation: Prescriptions ↔ Doctors (Pharmacy & Rx workflow) --}}
@php
    $active = $active ?? 'prescriptions';
@endphp
<ul class="nav nav-pills flex-wrap gap-1 mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $active === 'prescriptions' ? 'active' : '' }}" href="{{ route('pharmacy.prescriptions') }}">
            <i class="bx bx-file me-1"></i>{{ __('Prescriptions') }}
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'doctors' ? 'active' : '' }}" href="{{ route('pharmacy.doctors.index') }}">
            <i class="bx bx-plus-medical me-1"></i>{{ __('Doctors') }}
        </a>
    </li>
</ul>
