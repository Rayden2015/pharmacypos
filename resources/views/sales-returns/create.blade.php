@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Record sales return</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('sales.returns.index') }}">Sales returns</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Order #ORD-{{ str_pad((string) $order->id, 5, '0', STR_PAD_LEFT) }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
            @include('inc.msg')

            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <p class="mb-1"><strong>Branch:</strong> {{ $order->site?->name ?? '—' }}</p>
                        <p class="mb-0 text-muted small">Stock is increased on this branch for each line you return. Batch and rack are optional catalog fields and are not required.</p>
                    </div>

                    <form method="post" action="{{ route('sales.returns.store', $order) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Note <span class="text-muted">(optional)</span></label>
                            <input type="text" name="note" class="form-control" maxlength="500" value="{{ old('note') }}" placeholder="Reason or reference">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Sold</th>
                                        <th class="text-end">Already returned</th>
                                        <th class="text-end">Can return</th>
                                        <th class="text-end" style="width: 8rem;">Return qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lines as $i => $row)
                                        @php($d = $row['detail'])
                                        @if ($row['returnable'] < 1)
                                            <tr class="table-secondary">
                                                <td>{{ $d->product->product_name ?? '—' }}</td>
                                                <td class="text-end">{{ (int) $d->quantity }}</td>
                                                <td class="text-end">{{ $row['returned'] }}</td>
                                                <td class="text-end">0</td>
                                                <td class="text-end"><span class="text-muted small">—</span></td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td>
                                                    {{ $d->product->product_name ?? '—' }}
                                                    <input type="hidden" name="lines[{{ $i }}][order_detail_id]" value="{{ $d->id }}">
                                                </td>
                                                <td class="text-end">{{ (int) $d->quantity }}</td>
                                                <td class="text-end">{{ $row['returned'] }}</td>
                                                <td class="text-end">{{ $row['returnable'] }}</td>
                                                <td class="text-end">
                                                    <input type="number"
                                                           name="lines[{{ $i }}][quantity]"
                                                           class="form-control form-control-sm text-end"
                                                           min="0"
                                                           max="{{ $row['returnable'] }}"
                                                           value="{{ old('lines.'.$i.'.quantity', '0') }}">
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save return &amp; update stock</button>
                            <a href="{{ route('sales.returns.index') }}" class="btn btn-light">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
