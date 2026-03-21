@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Manufacturers</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Manufacturers</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <p class="text-muted small mb-0">Companies that <strong>produce</strong> medicines (labels on stock cards, product master).</p>
                    <a href="{{ route('manufacturers.create') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add manufacturer</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($manufacturers as $m)
                                        <tr>
                                            <td class="fw-semibold">{{ $m->name }}</td>
                                            <td class="small">{{ $m->phone ?? '—' }}</td>
                                            <td class="small">{{ $m->email ?? '—' }}</td>
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('manufacturers.edit', $m) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <form action="{{ route('manufacturers.destroy', $m) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this manufacturer?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No manufacturers yet. Add one to use it on medicines.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $manufacturers->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
