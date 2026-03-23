<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use App\Models\TenantSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionPaymentController extends Controller
{
    /** @var list<string> */
    public const PAYMENT_METHOD_OPTIONS = ['Cash', 'Mobile Money', 'POS', 'Card'];

    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    public function index(Request $request): View
    {
        $query = SubscriptionPayment::query()
            ->with('company:id,company_name,company_email')
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $t = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($t) {
                $q->where('invoice_reference', 'like', $t)
                    ->orWhere('description', 'like', $t)
                    ->orWhereHas('company', function ($c) use ($t) {
                        $c->where('company_name', 'like', $t)->orWhere('company_email', 'like', $t);
                    });
            });
        }

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        $payments = $query->paginate(12)->withQueryString();

        return view('super-admin.payments.index', compact('payments'));
    }

    public function create(): View
    {
        $companies = Company::query()->orderBy('company_name')->get(['id', 'company_name', 'company_email']);
        $subscriptions = TenantSubscription::query()
            ->with(['company:id,company_name', 'subscriptionPackage:id,name,billing_cycle'])
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $paymentMethods = self::PAYMENT_METHOD_OPTIONS;

        return view('super-admin.payments.create', compact('companies', 'subscriptions', 'paymentMethods'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'tenant_subscription_id' => 'nullable|exists:tenant_subscriptions,id',
            'invoice_reference' => 'nullable|string|max:64|unique:subscription_payments,invoice_reference',
            'amount' => 'required|numeric|min:0',
            'payment_method' => ['nullable', 'string', 'max:48', Rule::in(self::PAYMENT_METHOD_OPTIONS)],
            'status' => 'required|in:paid,unpaid,refunded',
            'paid_at' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
        ]);

        $paidAt = isset($data['paid_at']) ? \Carbon\Carbon::parse($data['paid_at']) : ($data['status'] === 'paid' ? now() : null);

        $method = $data['payment_method'] ?? null;
        if ($method === '' || $method === null) {
            $method = null;
        }

        SubscriptionPayment::query()->create([
            'company_id' => (int) $data['company_id'],
            'tenant_subscription_id' => $data['tenant_subscription_id'] ?? null,
            'invoice_reference' => $data['invoice_reference'] ?? null,
            'amount' => $data['amount'],
            'payment_method' => $method,
            'status' => $data['status'],
            'paid_at' => $paidAt,
            'description' => $data['description'] ?? null,
        ]);

        return redirect()->route('super-admin.payments.index')->with('success', 'Purchase transaction recorded.');
    }
}
