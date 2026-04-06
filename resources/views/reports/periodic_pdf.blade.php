<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 14px; margin: 0 0 8px 0; }
        .meta { margin-bottom: 12px; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        td.num { text-align: right; }
        .total-row td { font-weight: bold; background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Product line items</h1>
    <div class="meta">{{ $start_date }} — {{ $end_date }}</div>
    <table>
        <thead>
            <tr>
                <th style="width:3%">#</th>
                <th style="width:42%">Product</th>
                <th style="width:25%">Packaging</th>
                <th style="width:12%">Amount</th>
                <th style="width:18%">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($debt as $row)
                @php
                    $pl = $row->packaging_label ?? ($row->product ? ($row->product->packaging_label ?? '') : '');
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        @if ($row->product)
                            {{ $row->product->product_name }}@if ($row->product->alias) ({{ $row->product->alias }})@endif
                        @else
                            Name not found
                        @endif
                    </td>
                    <td>{{ $pl }}</td>
                    <td class="num">{{ $currencySymbol }}{{ number_format((float) ($row->amount ?? 0), 2) }}</td>
                    <td>{{ $row->created_at ? $row->created_at->format('Y-m-d H:i') : '' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td class="num">{{ $currencySymbol }}{{ number_format((float) $total, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
