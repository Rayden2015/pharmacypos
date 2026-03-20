{{-- Optional $id (input id), $name (defaults unit_of_measure), $selected (current value) --}}
@php
    $selectId = $id ?? 'unit_of_measure';
    $selectName = $name ?? 'unit_of_measure';
    $selectedVal = $selected ?? old($selectName);
    $options = ['Each', 'Tablet', 'Capsule', 'ml', 'L', 'g', 'mg', 'mcg', 'IU', 'Dose', 'Vial', 'Ampoule', 'Bottle', 'Tube', 'Sachet', 'Pack', 'Box', 'Spray', 'Drop', 'Puff', 'Application'];
@endphp
<select name="{{ $selectName }}" id="{{ $selectId }}" class="form-select">
    <option value="">— Not set —</option>
    @foreach ($options as $opt)
        <option value="{{ $opt }}" {{ (string) $selectedVal === $opt ? 'selected' : '' }}>{{ $opt }}</option>
    @endforeach
</select>
