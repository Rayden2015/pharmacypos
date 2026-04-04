<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Support\TenantRolesProvisioner;
use App\Support\TenantUserRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates default roles per company and maps users by tenant_role / legacy is_admin.
 *
 * Default hierarchy (every tenant): L2 Tenant Admin (full), L3 Branch Manager,
 * L4 Supervisor, L5 Cashier (see docs/roles-architecture.md). Platform L1 Super
 * Admin is not a tenant Spatie role.
 */
class TenantRolesBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        /** @var Collection<int, Permission> $allPermissions */
        $allPermissions = Permission::query()->where('guard_name', 'web')->get();

        foreach (Company::query()->orderBy('id')->get() as $company) {
            TenantRolesProvisioner::syncSystemRolesForCompany($company->id, $allPermissions);
        }

        foreach (User::query()->where('is_super_admin', false)->whereNotNull('company_id')->get() as $user) {
            TenantUserRoles::syncBuiltInSpatieRole($user);
        }

        $registrar->setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
