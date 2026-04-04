<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Site;
use App\Models\User;
use App\Support\TenantRolesProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class TenantAdminUserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    public function create(Request $request): View
    {
        $companies = Company::query()->orderBy('company_name')->get(['id', 'company_name', 'slug']);
        $sites = Site::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'company_id']);
        $selectedCompanyId = $request->query('company_id');

        return view('super-admin.tenant-admins.create', compact('companies', 'sites', 'selectedCompanyId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'site_id' => [
                'required',
                Rule::exists('sites', 'id')->where(function ($query) use ($request) {
                    $query->where('company_id', (int) $request->input('company_id'));
                }),
            ],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'admin_mobile' => ['nullable', 'string', 'max:32'],
        ]);

        $companyId = (int) $data['company_id'];
        $adminEmail = null;

        DB::transaction(function () use ($data, $companyId, &$adminEmail) {
            TenantRolesProvisioner::syncSystemRolesForCompany($companyId);

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
                'company_id' => $companyId,
                'site_id' => (int) $data['site_id'],
                'tenant_role' => 'tenant_admin',
                'user_img' => 'user.png',
            ]);

            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionsTeamId($companyId);
            $user->syncRoles([]);
            $user->assignRole('Tenant Admin');
            $registrar->setPermissionsTeamId(null);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        return redirect()
            ->route('super-admin.companies.index')
            ->with('success', 'Tenant admin created. They can sign in using '.$adminEmail.'.');
    }
}
