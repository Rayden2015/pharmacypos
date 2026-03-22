<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPackageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    public function index(Request $request): View
    {
        $query = SubscriptionPackage::query()->orderBy('sort_order')->orderBy('name');

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $packages = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => SubscriptionPackage::query()->count(),
            'active' => SubscriptionPackage::query()->where('is_active', true)->count(),
            'inactive' => SubscriptionPackage::query()->where('is_active', false)->count(),
            'cycles' => SubscriptionPackage::query()->select('billing_cycle')->groupBy('billing_cycle')->get()->count(),
        ];

        return view('super-admin.packages.index', compact('packages', 'stats'));
    }

    public function create(): View
    {
        return view('super-admin.packages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'billing_cycle' => 'required|in:monthly,yearly',
            'price' => 'required|numeric|min:0',
            'billing_days' => 'nullable|integer|min:1|max:3660',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        SubscriptionPackage::query()->create([
            'name' => $data['name'],
            'billing_cycle' => $data['billing_cycle'],
            'price' => $data['price'],
            'billing_days' => $data['billing_days'] ?? ($data['billing_cycle'] === 'yearly' ? 365 : 30),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('super-admin.packages.index')->with('success', 'Package created.');
    }

    public function edit(SubscriptionPackage $package): View
    {
        return view('super-admin.packages.edit', compact('package'));
    }

    public function update(Request $request, SubscriptionPackage $package): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'billing_cycle' => 'required|in:monthly,yearly',
            'price' => 'required|numeric|min:0',
            'billing_days' => 'nullable|integer|min:1|max:3660',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        $package->update([
            'name' => $data['name'],
            'billing_cycle' => $data['billing_cycle'],
            'price' => $data['price'],
            'billing_days' => $data['billing_days'] ?? ($data['billing_cycle'] === 'yearly' ? 365 : 30),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('super-admin.packages.index')->with('success', 'Package updated.');
    }

    public function destroy(SubscriptionPackage $package): RedirectResponse
    {
        if ($package->tenantSubscriptions()->exists()) {
            return redirect()->route('super-admin.packages.index')
                ->with('error', 'Cannot delete: package is assigned to tenants.');
        }

        $package->delete();

        return redirect()->route('super-admin.packages.index')->with('success', 'Package removed.');
    }
}
