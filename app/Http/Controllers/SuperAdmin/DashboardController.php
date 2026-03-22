<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPackage;
use App\Models\SubscriptionPayment;
use App\Models\TenantSubscription;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    public function index(): View
    {
        $stats = [
            'tenants_total' => Company::query()->count(),
            'tenants_active' => Company::query()->where('is_active', true)->count(),
            'packages_total' => SubscriptionPackage::query()->count(),
            'packages_active' => SubscriptionPackage::query()->where('is_active', true)->count(),
            'subscriptions_active' => TenantSubscription::query()->where('status', 'active')->count(),
            'subscriptions_expired' => TenantSubscription::query()->where('status', 'expired')->count(),
            'payments_sum' => (float) (SubscriptionPayment::query()->where('status', 'paid')->sum('amount') ?? 0),
            'payments_unpaid' => SubscriptionPayment::query()->where('status', 'unpaid')->count(),
        ];

        return view('super-admin.dashboard', compact('stats'));
    }
}
