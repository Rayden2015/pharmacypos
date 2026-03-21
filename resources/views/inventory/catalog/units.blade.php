@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Units of measure</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Units</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="card mb-3">
                    <div class="card-body p-4">
                        <p class="mb-2">
                            This catalog drives the <strong>Unit of measure</strong> dropdown on medicines. Values are stored on each product and copied to POS lines and receipts.
                        </p>
                        <p class="text-muted small mb-0">
                            Seeded list aligns with <strong>SI</strong> (e.g. mL, mg) and common <strong>dose-form / dispensing</strong> wording used internationally (WHO ATC / EDQM-style terms, adapted for short labels).
                            Add or change rows in the database (or extend with admin CRUD later); run <code class="small">php artisan db:seed --class=PharmaceuticalUnitOfMeasureSeeder</code> to refresh defaults.
                        </p>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm mt-3">Go to products</a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3">Catalog ({{ $units->total() }} entries)</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Unit (stored value)</th>
                                        <th>Code</th>
                                        <th>Category</th>
                                        <th class="text-end">Sort</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($units as $u)
                                        <tr>
                                            <td class="fw-semibold">{{ $u->name }}</td>
                                            <td class="small text-muted">{{ $u->code ?? '—' }}</td>
                                            <td class="small">{{ str_replace('_', ' ', $u->category ?? '—') }}</td>
                                            <td class="text-end small">{{ $u->sort_order }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                No units seeded yet. Run <code>php artisan db:seed --class=PharmaceuticalUnitOfMeasureSeeder</code> or full <code>php artisan db:seed</code>.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $units->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
