@php
    $grouped = $permissions->groupBy(function ($p) {
        return explode('.', $p->name)[0];
    });
@endphp
<div class="permission-matrix">
    <div class="row g-3">
        @foreach ($grouped as $group => $items)
            <div class="col-12">
                <h6 class="text-muted text-uppercase small mb-2">{{ $group }}</h6>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-2">
                    @foreach ($items as $perm)
                        <div class="col min-w-0">
                            <div class="form-check d-flex align-items-start gap-2">
                                <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" name="permissions[]" value="{{ $perm->name }}" id="perm_{{ $perm->id }}"
                                    {{ in_array($perm->name, $assigned ?? [], true) ? 'checked' : '' }}>
                                <label class="form-check-label small text-break" for="perm_{{ $perm->id }}">{{ $perm->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
