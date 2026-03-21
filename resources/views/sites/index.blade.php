@extends('layouts.dash')
@section('content')
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Sites / branches</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Sites</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                @include('inc.msg')

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted small mb-0">Each site has its own on-hand figures; product totals are the sum across sites.</p>
                    <a href="{{ route('sites.create') }}" class="btn btn-primary btn-sm">Add site</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Default</th>
                                        <th>Active</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sites as $site)
                                        <tr>
                                            <td>{{ $site->name }}</td>
                                            <td>{{ $site->code ?? '—' }}</td>
                                            <td>@if($site->is_default)<span class="badge bg-primary">Default</span>@else — @endif</td>
                                            <td>@if($site->is_active)<span class="text-success">Yes</span>@else <span class="text-muted">No</span>@endif</td>
                                            <td class="text-end">
                                                <a href="{{ route('sites.edit', $site) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                @if (! $site->is_default)
                                                    <form action="{{ route('sites.destroy', $site) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this site? Only allowed if it has no stock.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">No sites yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">{{ $sites->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
