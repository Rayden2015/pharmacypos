<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPackage;
use App\Models\TenantSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    public function index(Request $request): View
    {
        $query = TenantSubscription::query()
            ->with(['company:id,company_name,company_email', 'subscriptionPackage:id,name,billing_cycle'])
            ->orderByDesc('ends_at')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $t = '%'.$request->input('search').'%';
            $query->whereHas('company', function ($q) use ($t) {
                $q->where('company_name', 'like', $t)->orWhere('company_email', 'like', $t);
            });
        }

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        $subscriptions = $query->paginate(12)->withQueryString();

        $stats = [
            'total_tx' => (float) (TenantSubscription::query()->sum('amount') ?? 0),
            'subscribers' => TenantSubscription::query()->select('company_id')->groupBy('company_id')->get()->count(),
            'active' => TenantSubscription::query()->where('status', 'active')->count(),
            'expired' => TenantSubscription::query()->where('status', 'expired')->count(),
        ];

        return view('super-admin.subscriptions.index', compact('subscriptions', 'stats'));
    }

    public function create(): View
    {
        $companies = Company::query()->orderBy('company_name')->get(['id', 'company_name', 'company_email']);
        $packages = SubscriptionPackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('super-admin.subscriptions.create', compact('companies', 'packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'subscription_package_id' => 'required|exists:subscription_packages,id',
            'status' => 'required|in:active,expired,cancelled,pending',
            'payment_method' => 'nullable|string|max:48',
            'amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'notes' => 'nullable|string|max:5000',
        ]);

        $pkg = SubscriptionPackage::query()->findOrFail($data['subscription_package_id']);
        $starts = isset($data['starts_at']) ? \Carbon\Carbon::parse($data['starts_at']) : now();
        $ends = isset($data['ends_at'])
            ? \Carbon\Carbon::parse($data['ends_at'])
            : ($pkg->billing_cycle === 'yearly' ? $starts->copy()->addYear() : $starts->copy()->addDays($pkg->billing_days ?: 30));

        TenantSubscription::query()->create([
            'company_id' => (int) $data['company_id'],
            'subscription_package_id' => $pkg->id,
            'status' => $data['status'],
            'payment_method' => $data['payment_method'] ?? null,
            'amount' => $data['amount'] ?? $pkg->price,
            'starts_at' => $starts,
            'ends_at' => $ends,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('super-admin.subscriptions.index')->with('success', 'Subscription saved.');
    }
}
