<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Database\Seeders\PermissionCatalogSeeder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** @var list<string> */
    private const SYSTEM_ROLE_NAMES = [
        'Tenant Admin',
        'Branch Manager',
        'Cashier',
        'Supervisor',
    ];

    public function index()
    {
        $companyId = auth()->user()->company_id;
        abort_if(! $companyId, 403);

        $roles = Role::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('tenant.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('tenant.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;
        abort_if(! $companyId, 403);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:64',
                function ($attribute, $value, $fail) {
                    if (in_array($value, self::SYSTEM_ROLE_NAMES, true)) {
                        $fail('This name is reserved for built-in roles.');
                    }
                },
                Rule::unique('roles', 'name')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)->where('guard_name', 'web');
                }),
            ],
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(PermissionCatalogSeeder::NAMES)],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'company_id' => $companyId,
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role created.');
    }

    public function edit(int $role)
    {
        $roleModel = $this->roleForTenant($role);
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();
        $assigned = $roleModel->permissions->pluck('name')->all();

        return view('tenant.roles.edit', [
            'role' => $roleModel,
            'permissions' => $permissions,
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request, int $role)
    {
        $roleModel = $this->roleForTenant($role);

        $rules = [
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(PermissionCatalogSeeder::NAMES)],
        ];

        if (! $this->isSystemRole($roleModel)) {
            $companyId = auth()->user()->company_id;
            $rules['name'] = [
                'required',
                'string',
                'max:64',
                function ($attribute, $value, $fail) {
                    if (in_array($value, self::SYSTEM_ROLE_NAMES, true)) {
                        $fail('This name is reserved for built-in roles.');
                    }
                },
                Rule::unique('roles', 'name')->where(function ($query) use ($companyId) {
                    return $query->where('company_id', $companyId)->where('guard_name', 'web');
                })->ignore($roleModel->id),
            ];
        }

        $validated = $request->validate($rules);

        if (isset($validated['name'])) {
            $roleModel->name = $validated['name'];
            $roleModel->save();
        }

        $roleModel->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Role updated.');
    }

    public function destroy(int $role)
    {
        $roleModel = $this->roleForTenant($role);

        if ($this->isSystemRole($roleModel)) {
            return redirect()->route('roles.index')->with('error', 'Built-in roles cannot be deleted.');
        }

        if ($roleModel->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', 'Remove this role from all users before deleting.');
        }

        $roleModel->delete();

        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }

    private function roleForTenant(int $id): Role
    {
        $companyId = auth()->user()->company_id;
        abort_if(! $companyId, 403);

        return Role::query()
            ->where('company_id', $companyId)
            ->whereKey($id)
            ->firstOrFail();
    }

    private function isSystemRole(Role $role): bool
    {
        return in_array($role->name, self::SYSTEM_ROLE_NAMES, true);
    }
}
