@extends('layouts.dash')
@section('content')
<style>
    path{
        display: none;
    }
    svg{
        display: none;
    }
</style>
    <!--wrapper-->
    <div class="wrapper">
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <!--breadcrumb-->
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Employee</div>
                   

                </div>
                <hr />
                @include('inc.msg')
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    <span class="badge bg-secondary rounded-pill px-3 py-2">List view</span>
                    <a href="{{ route('users.employees.grid', request()->query()) }}" class="btn btn-outline-primary btn-sm rounded-pill px-3"><i class="bx bx-grid-alt"></i> Grid view</a>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="example2" class="table table-striped table-bordered">
                                <thead>

                                    <tr>
                                        <th>#</th>
                                        <th>User Image</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Address</th>
                                        <th>Site</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                        {{-- <th>Salary</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $key => $user)
                                        
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td><img src="/storage/users/{{ $user->user_img }}" class="user-img" alt=""></td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>{{ $user->mobile }}</td>
                                            <td>{{ $user->address }}</td>
                                            <td>
                                                @if ($user->is_super_admin)
                                                    <span class="badge bg-warning text-dark">Super</span>
                                                @endif
                                                @if ($user->site)
                                                    <span class="small">{{ $user->site->name }}</span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($user->is_admin == 1) 
                                                <span class="badge bg-success">Admin</span>
                                                @elseif ($user->is_admin == 2) 
                                                <span class="badge bg-secondary">Cashier</span>
                                                @else 
                                                <span class="badge bg-info">Manager</span>
                                                @endif

                                            </td>
                                            <td>
                                                @if ($user->status == 1)
                                                <span class="badge bg-primary">ACTIVE</span> 
                                                
                                                @else 
                                                <span class="badge bg-danger">INACTIVE</span> 
                                                
                                                @endif

                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#editUser{{ $user->id }}"><i
                                                        class="bx bxs-edit"></i>Edit</a>
                                                <a href="#" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                    data-bs-target="#deleteUser{{ $user->id }}"><i
                                                        class="bx bxs-trash"></i>Delete</a>
                                            </td>

                                        </tr>
                                    @endforeach
                                    
                                </tbody>
                                {{-- <tfoot>
                                    <tr>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Office</th>
                                        <th>Age</th>
                                        <th>Start date</th>
                                        <th>Salary</th>
                                    </tr>
                                </tfoot> --}}
                               
                            </table>
                            @include('users.partials.user-modals', ['users' => $users, 'sites' => $sites])
                        </div>
                        <nav aria-label="..." class="py-5">
							<ul class="pagination">
								{{$users->links()}}
								
								
								
							</ul>
						</nav>
                    </div>
                </div>
            </div>
        </div>
    </div>







@endsection