@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Suppliers</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Suppliers</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="text-muted small mb-0">Wholesalers / distributors you <strong>purchase</strong> from (receive stock, receipts).</p>
                    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add supplier</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($suppliers as $s)
                                        <tr>
                                            <td class="fw-semibold">{{ $s->supplier_name }}</td>
                                            <td class="small">{{ $s->mobile ?: '—' }}</td>
                                            <td class="small">{{ $s->email ?: '—' }}</td>
                                            <td class="small">{{ \Illuminate\Support\Str::limit($s->address, 40) ?: '—' }}</td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('suppliers.edit', $s) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form action="{{ route('suppliers.destroy', $s) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this supplier?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No suppliers yet. Add one for receive-stock and preferred vendor on medicines.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $suppliers->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
