@foreach ($customers as $customer)
    <div class="modal fade" id="editCustomer{{ $customer->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="{{ route('customers.update', $customer) }}">
                    @csrf
                    @method('put')
                    <input type="hidden" name="view" value="{{ request('view', 'grid') }}">
                    @if (request()->filled('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                    @if (request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="{{ $customer->name }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile <span class="text-danger">*</span></label>
                                <input type="text" name="mobile" class="form-control" required value="{{ $customer->mobile }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ $customer->email }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-control" value="{{ $customer->address }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ $customer->notes }}</textarea>
                            </div>
                            @if(auth()->user()->isSuperAdmin())
                                <div class="col-md-6">
                                    <label class="form-label">Site / branch</label>
                                    <select name="site_id" class="form-select">
                                        <option value="">— None —</option>
                                        @foreach ($sites as $s)
                                            <option value="{{ $s->id }}" {{ (int) $customer->site_id === (int) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" {{ $customer->is_active ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ ! $customer->is_active ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteCustomer{{ $customer->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="{{ route('customers.destroy', $customer) }}">
                    @csrf
                    @method('delete')
                    <input type="hidden" name="view" value="{{ request('view', 'grid') }}">
                    @if (request()->filled('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                    @if (request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    <div class="modal-body">
                        <p>Remove <strong>{{ $customer->name }}</strong> from the directory?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
