{{-- Optional $id, $name, $selected. $unitsCatalog from View::composer when table is seeded. --}}
@php
    $selectId = $id ?? 'unit_of_measure';
    $selectName = $name ?? 'unit_of_measure';
    $selectedVal = $selected ?? old($selectName);
    $catalog = isset($unitsCatalog) ? $unitsCatalog : collect();
    $legacyFallback = ['Each', 'Tablet', 'Capsule', 'ml', 'L', 'g', 'mg', 'mcg', 'IU', 'Dose', 'Vial', 'Ampoule', 'Bottle', 'Tube', 'Sachet', 'Pack', 'Box', 'Spray', 'Drop', 'Puff', 'Application'];
    $namesInCatalog = $catalog->pluck('name')->all();
    $optionKeys = $catalog->isNotEmpty() ? $namesInCatalog : $legacyFallback;
    $orphanSelected = $selectedVal !== null && $selectedVal !== ''
        && ! in_array((string) $selectedVal, array_map('strval', $optionKeys), true);
@endphp
<select name="{{ $selectName }}" id="{{ $selectId }}" class="form-select">
    <option value="">— Not set —</option>
    @if ($orphanSelected)
        <option value="{{ $selectedVal }}" selected>{{ $selectedVal }} (legacy)</option>
    @endif
    @if ($catalog->isNotEmpty())
        @foreach ($catalog as $uom)
            <option value="{{ $uom->name }}" {{ (string) $selectedVal === $uom->name ? 'selected' : '' }}>
                {{ $uom->name }}@if ($uom->code) ({{ $uom->code }})@endif
            </option>
        @endforeach
    @else
        @foreach ($legacyFallback as $opt)
            <option value="{{ $opt }}" {{ (string) $selectedVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    @endif
</select>
@if ($catalog->isNotEmpty())
    <p class="form-text mb-0 small text-muted">Based on SI units and common pharmaceutical dose-form / dispensing terms (WHO/EDQM-style wording where applicable).</p>
@endif
