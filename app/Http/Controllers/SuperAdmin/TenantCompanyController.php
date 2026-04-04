<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Site;
use App\Models\SubscriptionPackage;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\TenantRolesProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class TenantCompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    public function index(Request $request): View
    {
        $query = Company::query()->orderBy('company_name');

        if ($request->filled('search')) {
            $t = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($t) {
                $q->where('company_name', 'like', $t)
                    ->orWhere('company_email', 'like', $t)
                    ->orWhere('slug', 'like', $t);
            });
        }

        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $companies = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => Company::query()->count(),
            'active' => Company::query()->where('is_active', true)->count(),
            'inactive' => Company::query()->where('is_active', false)->count(),
            'locations' => \App\Models\Site::query()->count(),
        ];

        $packages = SubscriptionPackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('super-admin.companies.index', compact('companies', 'stats', 'packages'));
    }

    public function create(): View
    {
        $packages = SubscriptionPackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('super-admin.companies.create', compact('packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_mobile' => 'nullable|string|max:64',
            'company_address' => 'nullable|string|max:2000',
            'slug' => 'nullable|string|max:191|unique:companies,slug',
            'is_active' => 'nullable|boolean',
            'subscription_package_id' => 'nullable|exists:subscription_packages,id',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
            'admin_mobile' => 'nullable|string|max:32',
        ]);

        $slug = $data['slug'] ?? Str::slug($data['company_name']);
        $slug = $this->uniqueSlug($slug);

        $adminEmail = null;

        DB::transaction(function () use ($request, $data, $slug, &$adminEmail) {
            $company = Company::query()->create([
                'company_name' => $data['company_name'],
                'company_email' => $data['company_email'],
                'company_mobile' => $data['company_mobile'] ?? '',
                'company_address' => $data['company_address'] ?? '',
                'slug' => $slug,
                'is_active' => $request->boolean('is_active', true),
            ]);

            if (! empty($data['subscription_package_id'])) {
                $pkg = SubscriptionPackage::query()->findOrFail($data['subscription_package_id']);
                $starts = now();
                $ends = $pkg->billing_cycle === 'yearly'
                    ? $starts->copy()->addYear()
                    : $starts->copy()->addDays($pkg->billing_days ?: 30);

                TenantSubscription::query()->create([
                    'company_id' => $company->id,
                    'subscription_package_id' => $pkg->id,
                    'status' => 'active',
                    'payment_method' => 'pending',
                    'amount' => $pkg->price,
                    'starts_at' => $starts,
                    'ends_at' => $ends,
                ]);
            }

            $site = Site::query()->create([
                'company_id' => $company->id,
                'name' => 'Head office',
                'code' => 'HQ-'.$company->id,
                'address' => null,
                'is_active' => true,
                'is_default' => true,
            ]);

            TenantRolesProvisioner::syncSystemRolesForCompany($company->id);

            $plain = $data['admin_password'];
            $adminEmail = strtolower($data['admin_email']);
            $user = User::query()->create([
                'name' => $data['admin_name'],
                'email' => $adminEmail,
                'password' => Hash::make($plain),
                'confirm_password' => Hash::make($plain),
                'mobile' => $data['admin_mobile'] ?? null,
                'address' => null,
                'status' => '1',
                'is_admin' => 0,
                'is_super_admin' => false,
                'company_id' => $company->id,
                'site_id' => $site->id,
                'tenant_role' => 'tenant_admin',
                'user_img' => 'user.png',
            ]);

            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($company->id);
            $user->assignRole('Tenant Admin');
            $registrar->setPermissionsTeamId(null);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return redirect()->route('super-admin.companies.index')->with(
            'success',
            'Tenant company created. Tenant admin can sign in using '.$adminEmail.'.'
        );
    }

    public function edit(Company $company): View
    {
        $packages = SubscriptionPackage::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $subscription = TenantSubscription::query()
            ->where('company_id', $company->id)
            ->with('subscriptionPackage')
            ->orderByDesc('id')
            ->first();

        return view('super-admin.companies.edit', compact('company', 'packages', 'subscription'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'company_mobile' => 'nullable|string|max:64',
            'company_address' => 'nullable|string|max:2000',
            'slug' => 'nullable|string|max:191|unique:companies,slug,'.$company->id,
            'is_active' => 'nullable|boolean',
        ]);

        $slug = $data['slug'] ?? $company->slug ?? Str::slug($data['company_name']);
        if ($slug !== $company->slug) {
            $slug = $this->uniqueSlug($slug, $company->id);
        }

        $company->update([
            'company_name' => $data['company_name'],
            'company_email' => $data['company_email'],
            'company_mobile' => $data['company_mobile'] ?? '',
            'company_address' => $data['company_address'] ?? '',
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('super-admin.companies.index')->with('success', 'Company updated.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        if ($company->sites()->exists()) {
            return redirect()->route('super-admin.companies.index')
                ->with('error', 'Cannot delete: this tenant still has sites/branches. Remove or reassign them first.');
        }

        if ($company->users()->where('is_super_admin', false)->exists()) {
            return redirect()->route('super-admin.companies.index')
                ->with('error', 'Cannot delete: tenant still has users.');
        }

        $company->delete();

        return redirect()->route('super-admin.companies.index')->with('success', 'Company removed.');
    }

    private function uniqueSlug(string $base, ?int $exceptId = null): string
    {
        $slug = $base ?: 'tenant';
        $i = 0;
        do {
            $candidate = $i ? $slug.'-'.$i : $slug;
            $q = Company::query()->where('slug', $candidate);
            if ($exceptId) {
                $q->where('id', '!=', $exceptId);
            }
            $exists = $q->exists();
            $i++;
        } while ($exists);

        return $candidate;
    }
}
