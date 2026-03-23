@extends('layouts.dash')
@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Roles &amp; permissions</div>
            </div>
            @include('inc.msg')
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <p class="text-muted small mb-0">Roles are scoped to your organization. Built-in roles can be updated; custom roles can be added or removed.</p>
                        <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">New role</a>
                    </div>
                    <div class="table-responsive border rounded">
                        <table class="table table-striped mb-0 w-100 align-middle" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 20%;">
                                <col style="width: 62%;">
                                <col style="width: 18%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap">Name</th>
                                    <th scope="col">Permissions</th>
                                    <th scope="col" class="text-end text-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr>
                                        <td class="fw-semibold text-break">{{ $role->name }}</td>
                                        <td class="small" style="min-width: 0;">
                                            @if ($role->permissions->isEmpty())
                                                <span class="text-muted">—</span>
                                            @else
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach ($role->permissions as $perm)
                                                        <span class="badge bg-light text-dark border text-start text-break d-inline-block" style="max-width: 100%;">{{ $perm->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            @php $builtIn = in_array($role->name, ['Tenant Admin', 'Branch Manager', 'Cashier', 'Supervisor'], true); @endphp
                                            @if (! $builtIn)
                                                <form action="{{ route('roles.destroy', $role) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this role?');">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">No roles yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
