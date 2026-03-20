{{-- Expects $kind: on_hand | alert --}}
@php
    $kind = $kind ?? 'on_hand';
    $hints = [
        'on_hand' => 'How many units you have in stock right now. POS sales do not lower this automatically—update when you restock or adjust inventory.',
        'alert' => 'Low-stock warning level (not the same as current stock). When on-hand quantity is at or below this number, the product list shows a low-stock alert.',
    ];
    $text = $hints[$kind] ?? $hints['on_hand'];
@endphp
<span class="text-info cursor-pointer ms-1" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top"
    title="{{ $text }}" role="button" aria-label="More information"><i class="bx bx-info-circle"></i></span>
