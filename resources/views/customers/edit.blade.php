@extends('layouts.dash')
@section('content')
<div class="wrapper">
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Customers</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                </div>
            </div>

            @include('inc.msg')

            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0">Edit customer</h6>
                    <p class="text-muted small mb-0">{{ $customer->name }}</p>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('customers.update', $customer) }}" class="row g-3">
                        @csrf
                        @method('put')
                        <input type="hidden" name="stay_on_edit" value="1">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" required value="{{ old('name', $customer->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" required value="{{ old('mobile', $customer->mobile) }}">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $customer->notes) }}</textarea>
                        </div>
                        @if (auth()->user()->isSuperAdmin())
                            <div class="col-md-6">
                                <label class="form-label">Site / branch</label>
                                <select name="site_id" class="form-select">
                                    <option value="">— None —</option>
                                    @foreach ($sites as $s)
                                        <option value="{{ $s->id }}" {{ (string) old('site_id', $customer->site_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" {{ old('is_active', $customer->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $customer->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Save changes</button>
                            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Back to directory</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h6 class="mb-0">Sales history</h6>
                        <p class="text-muted small mb-0">POS orders across all branches in your organization. Rows match on the <strong>last 9 digits</strong> of the phone (international and local formats).</p>
                    </div>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-primary">Open POS</a>
                </div>
                <div class="card-body p-0">
                    @if ($salesOrders->total() === 0)
                        <p class="text-muted small mb-0 px-3 py-4">No matching sales yet, or the mobile number is too short to match.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Invoice</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Branch</th>
                                        <th scope="col" class="text-end">Lines</th>
                                        <th scope="col" class="text-end">Total</th>
                                        <th scope="col" class="text-end">Paid</th>
                                        <th scope="col" class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($salesOrders as $sale)
                                        @php
                                            $lineTotal = $sale->orderdetail->sum('amount');
                                            $tx = $sale->transaction;
                                        @endphp
                                        <tr>
                                            <td class="text-nowrap">#{{ $sale->id }}</td>
                                            <td class="text-nowrap">{{ $sale->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i a') ?? '—' }}</td>
                                            <td>{{ $sale->site?->name ?? '—' }}@if ($sale->site?->code)<span class="text-muted small"> · {{ $sale->site->code }}</span>@endif</td>
                                            <td class="text-end">{{ $sale->orderdetail->count() }}</td>
                                            <td class="text-end">{{ $currencySymbol }}{{ number_format((float) $lineTotal, 2) }}</td>
                                            <td class="text-end">{{ $tx ? $currencySymbol . number_format((float) $tx->paid_amount, 2) : '—' }}</td>
                                            <td class="text-end">{{ $tx ? $currencySymbol . number_format((float) $tx->balance, 2) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="px-3 py-3 border-top">
                            {{ $salesOrders->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
