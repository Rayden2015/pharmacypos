@foreach ($users as $user)
    <!-- Modal -->
    <div class="modal fade" id="editUser{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content ">
                <div class="modal-header">
                    <h5 class="modal-title">EDIT USER</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card border-top border-0 border-4 border-primary">
                        <div class="card-body p-5">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bxs-user me-1 font-22 text-primary"></i></div>
                                <h5 class="mb-0 text-primary">Edit User</h5>
                            </div>
                            <hr>
                            <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data" class="row g-3 pt-4">
                                @csrf
                                @method('put')
                                <div class="row">
                                    <div class="col-6">
                                        <label for="edit_name_{{ $user->id }}" class="form-label">Name</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class='bx bxs-user'></i></span>
                                            <input type="text" class="form-control border-start-0" id="edit_name_{{ $user->id }}" name="name" placeholder="Enter Name" value="{{ $user->name }}" required />
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label for="edit_mobile_{{ $user->id }}" class="form-label">Phone No</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class='bx bxs-mobile'></i></span>
                                            <input type="text" class="form-control border-start-0" name="mobile" id="edit_mobile_{{ $user->id }}" placeholder="Phone No" value="{{ $user->mobile }}" required />
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label for="edit_email_{{ $user->id }}" class="form-label">Email Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class='bx bxs-envelope'></i></span>
                                            <input type="text" class="form-control border-start-0" id="edit_email_{{ $user->id }}" name="email" placeholder="Email Address" value="{{ $user->email }}" required />
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label for="edit_addr_{{ $user->id }}" class="form-label">Address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class='bx bxs-map'></i></span>
                                            <input type="text" class="form-control border-start-0" id="edit_addr_{{ $user->id }}" name="address" placeholder="Address" value="{{ $user->address }}" required />
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Role</label>
                                        <select name="is_admin" class="form-select">
                                            <option value="1" @if ($user->is_admin == 1) selected @endif>Admin</option>
                                            <option value="2" @if ($user->is_admin == 2) selected @endif>Cashier</option>
                                            <option value="3" @if ($user->is_admin == 3) selected @endif>Manager</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="1" @if ($user->status == 1) selected @endif>ACTIVE</option>
                                            <option value="2" @if ($user->status == 2) selected @endif>INACTIVE</option>
                                        </select>
                                    </div>
                                    @if(auth()->user()->isSuperAdmin())
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_super_admin" value="1" id="edit_super_{{ $user->id }}" {{ $user->is_super_admin ? 'checked' : '' }}>
                                                <label class="form-check-label" for="edit_super_{{ $user->id }}">Super admin</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">Site / branch</label>
                                            <select name="site_id" class="form-select">
                                                <option value="">— None (global) —</option>
                                                @foreach ($sites as $s)
                                                    <option value="{{ $s->id }}" {{ (int) $user->site_id === (int) $s->id ? 'selected' : '' }}>{{ $s->name }}@if($s->code) ({{ $s->code }})@endif</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="col-6">
                                        <label class="form-label">Choose Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class='bx bxs-lock-open'></i></span>
                                            <input type="password" class="form-control border-start-0" name="password" placeholder="Leave blank to keep current" />
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Confirm Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class='bx bxs-lock'></i></span>
                                            <input type="password" class="form-control border-start-0" name="confirm_password" placeholder="Confirm Password" />
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">User Image</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-transparent"><i class='bx bxs-user-circle'></i></span>
                                            <input type="file" class="form-control border-start-0" name="user_img" />
                                        </div>
                                    </div>
                                    <div class="col-12 pt-5">
                                        <button type="submit" class="btn btn-primary px-5">Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUser{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">DELETE USER</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card border-top border-0 border-4 border-danger">
                        <div class="card-body p-5">
                            <div class="card-title d-flex align-items-center">
                                <div><i class="bx bxs-user me-1 font-22 text-danger"></i></div>
                                <h5 class="mb-0 text-danger">Delete User</h5>
                            </div>
                            <hr>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="row g-3">
                                @csrf
                                @method('delete')
                                <div class="container">
                                    <h6 class="text-center">Are you sure you want to delete <br><br> <span class="text-danger">{{ $user->name }}</span></h6>
                                </div>
                                <hr>
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger px-5">Delete</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
