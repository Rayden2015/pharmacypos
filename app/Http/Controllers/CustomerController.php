<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Site;
use App\Models\User;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

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

        $sites = $this->sitesForCustomerForms($request);

        if ($view === 'list') {
            return view('customers.list', compact('customers', 'sites', 'stats'));
        }

        return view('customers.grid', compact('customers', 'sites', 'stats'));
    }

    public function edit(Request $request, Customer $customer): View
    {
        $this->authorizeCustomer($customer);

        $sites = $this->sitesForCustomerForms($request);
        $salesOrders = $this->paginateSalesOrdersForCustomer($request, $customer);

        return view('customers.edit', compact('customer', 'sites', 'salesOrders'));
    }

    public function store(Request $request)
    {
        $viewer = $request->user();

        $rules = [
            'name' => 'required|string|max:255',
            'mobile' => array_merge(
                ['required', 'string', 'max:32'],
                [$this->mobileUniqueInCompanyRule($request, $viewer, null)]
            ),
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
            'mobile' => array_merge(
                ['required', 'string', 'max:32'],
                [$this->mobileUniqueInCompanyRule($request, $viewer, $customer)]
            ),
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

        if ($request->boolean('stay_on_edit')) {
            return redirect()->route('customers.edit', $customer)->with('success', 'Customer updated successfully.');
        }

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

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\Site>
     */
    private function sitesForCustomerForms(Request $request)
    {
        $viewer = $request->user();
        $q = Site::query()->where('is_active', true)->orderBy('name');

        if ($viewer && ! $viewer->isSuperAdmin()) {
            $q->forUserTenant($viewer);
        }

        return $q->get(['id', 'name', 'code']);
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

    /**
     * POS orders company-wide whose phone matches this customer (normalized), paginated.
     */
    private function paginateSalesOrdersForCustomer(Request $request, Customer $customer, int $perPage = 15): LengthAwarePaginator
    {
        $companyId = $customer->companyIdForSalesHistory();
        $norm = Customer::normalizeMobile((string) $customer->mobile);
        $page = max(1, (int) $request->input('sales_page', 1));

        $empty = new LengthAwarePaginator([], 0, $perPage, $page, [
            'path' => $request->url(),
            'pageName' => 'sales_page',
        ]);

        if (! $companyId || strlen($norm) < 7) {
            return $empty->withQueryString();
        }

        $siteIds = Site::query()->where('company_id', $companyId)->pluck('id');
        if ($siteIds->isEmpty()) {
            return $empty->withQueryString();
        }

        $eager = ['site:id,name,code', 'transaction', 'orderdetail'];

        $driver = Order::query()->getConnection()->getDriverName();
        if ($driver === 'mysql') {
            return Order::query()
                ->whereIn('site_id', $siteIds)
                ->whereNotNull('mobile')
                ->whereRaw(
                    'RIGHT(REGEXP_REPLACE(COALESCE(orders.mobile, \'\'), \'[^0-9]\', \'\'), 9) = ?',
                    [$norm]
                )
                ->with($eager)
                ->orderByDesc('orders.id')
                ->paginate($perPage, ['*'], 'sales_page')
                ->withQueryString();
        }

        $allIds = Order::query()
            ->whereIn('site_id', $siteIds)
            ->whereNotNull('mobile')
            ->orderByDesc('id')
            ->pluck('id');

        $matchedIds = [];
        foreach ($allIds->chunk(500) as $chunkIds) {
            $rows = Order::query()->whereIn('id', $chunkIds)->get(['id', 'mobile']);
            foreach ($rows as $row) {
                if (Customer::normalizeMobile($row->mobile) === $norm) {
                    $matchedIds[] = $row->id;
                }
            }
        }

        $total = count($matchedIds);
        $offset = ($page - 1) * $perPage;
        $pageIds = array_slice($matchedIds, $offset, $perPage);

        $items = $pageIds === []
            ? collect()
            : Order::query()->whereIn('id', $pageIds)->with($eager)->orderByDesc('id')->get();

        return (new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => $request->url(),
            'pageName' => 'sales_page',
        ]))->withQueryString();
    }

    /**
     * Branch/site used to resolve company when checking mobile uniqueness (last 9 digits per org).
     */
    private function companyIdForMobileUniquenessCheck(Request $request, User $viewer, ?Customer $customer = null): int
    {
        if ($viewer->isSuperAdmin()) {
            if ($request->has('site_id') && $request->input('site_id') !== null && $request->input('site_id') !== '') {
                $siteId = (int) $request->site_id;
            } elseif ($customer && $customer->site_id) {
                $siteId = (int) $customer->site_id;
            } else {
                $siteId = (int) CurrentSite::id();
            }
        } else {
            $siteId = (int) CurrentSite::id();
        }

        $companyId = Site::query()->whereKey($siteId)->value('company_id');
        if ($companyId !== null) {
            return (int) $companyId;
        }

        return (int) ($viewer->company_id ?? 0);
    }

    /**
     * @return \Closure(string, mixed, \Closure): void
     */
    private function mobileUniqueInCompanyRule(Request $request, User $viewer, ?Customer $ignoreCustomer): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($request, $viewer, $ignoreCustomer): void {
            $companyId = $this->companyIdForMobileUniquenessCheck($request, $viewer, $ignoreCustomer);
            if ($companyId < 1) {
                return;
            }
            if (Customer::normalizeMobile((string) $value) === '') {
                return;
            }
            $existing = Customer::findForCompanyByNormalizedMobile($companyId, (string) $value);
            if ($existing && ($ignoreCustomer === null || (int) $existing->id !== (int) $ignoreCustomer->id)) {
                $fail('A customer with this mobile number already exists for this organization.');
            }
        };
    }
}
