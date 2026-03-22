<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Site;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $view = $request->query('view', 'grid');

        $query = Customer::query()
            ->forCurrentSiteContext()
            ->with('site:id,name,code');

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('code', 'like', $term);
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $statsBase = Customer::query()->forCurrentSiteContext();
        $stats = [
            'total' => (clone $statsBase)->count(),
            'active' => (clone $statsBase)->where('is_active', true)->count(),
            'inactive' => (clone $statsBase)->where('is_active', false)->count(),
            'new_this_month' => (clone $statsBase)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $perPage = $view === 'grid' ? 12 : 20;
        $customers = $query->orderBy('name')->paginate($perPage)->withQueryString();

        $sites = Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        if ($view === 'list') {
            return view('customers.list', compact('customers', 'sites', 'stats'));
        }

        return view('customers.grid', compact('customers', 'sites', 'stats'));
    }

    public function store(Request $request)
    {
        $viewer = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'string', 'max:32', Rule::unique('customers', 'mobile')],
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['site_id'] = 'nullable|exists:sites,id';
        }

        $this->validate($request, $rules);

        $customer = new Customer;
        $customer->fill($request->only(['name', 'mobile', 'email', 'address', 'notes']));
        $customer->is_active = $request->boolean('is_active', true);

        if ($viewer->isSuperAdmin()) {
            $customer->site_id = $request->filled('site_id') ? (int) $request->site_id : null;
        } else {
            $customer->site_id = (int) CurrentSite::id();
        }

        $customer->save();

        return $this->redirectToCustomerIndex($request)->with('success', 'Customer added successfully.');
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);

        $viewer = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'string', 'max:32', Rule::unique('customers', 'mobile')->ignore($customer->id)],
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ];
        if ($viewer->isSuperAdmin()) {
            $rules['site_id'] = 'nullable|exists:sites,id';
        }

        $this->validate($request, $rules);

        $customer->fill($request->only(['name', 'mobile', 'email', 'address', 'notes']));
        $customer->is_active = $request->boolean('is_active', true);

        if ($viewer->isSuperAdmin()) {
            $customer->site_id = $request->filled('site_id') ? (int) $request->site_id : null;
        }

        $customer->save();

        return $this->redirectToCustomerIndex($request)->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->delete();

        return $this->redirectToCustomerIndex($request)->with('success', 'Customer removed.');
    }

    private function redirectToCustomerIndex(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('customers.index', array_filter([
            'view' => $request->input('view', $request->query('view', 'grid')),
            'search' => $request->input('search', $request->query('search')),
            'status' => $request->input('status', $request->query('status')),
        ], function ($v) {
            return $v !== null && $v !== '';
        }));
    }

    private function authorizeCustomer(Customer $customer): void
    {
        $viewer = auth()->user();
        if ($viewer->isSuperAdmin()) {
            return;
        }
        $sid = (int) CurrentSite::id();
        if ((int) $customer->site_id === $sid) {
            return;
        }
        if ($customer->site_id === null && $sid === (int) Site::defaultId()) {
            return;
        }
        abort(403);
    }
}
