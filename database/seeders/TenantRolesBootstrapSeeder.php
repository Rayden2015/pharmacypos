<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
            $registrar->setPermissionsTeamId($company->id);

            $tenantAdmin = Role::findOrCreate('Tenant Admin', 'web');
            $tenantAdmin->syncPermissions($allPermissions);

            $manager = Role::findOrCreate('Branch Manager', 'web');
            $manager->syncPermissions($this->pickPermissions($allPermissions, [
                'tenant.users.manage', 'sites.manage', 'pos.access', 'pos.refund',
                'products.view', 'products.manage', 'inventory.view', 'inventory.receive',
                'inventory.adjust', 'inventory.transfer', 'reports.view', 'reports.export',
                'settings.manage', 'audit.view', 'prescriptions.manage', 'customers.manage',
                'suppliers.manage', 'transactions.view',
            ]));

            $supervisor = Role::findOrCreate('Supervisor', 'web');
            $supervisor->syncPermissions($this->pickPermissions($allPermissions, [
                'pos.access', 'pos.refund', 'products.view', 'inventory.view',
                'reports.view', 'audit.view', 'prescriptions.manage', 'customers.manage', 'transactions.view',
            ]));

            $cashier = Role::findOrCreate('Cashier', 'web');
            $cashier->syncPermissions($this->pickPermissions($allPermissions, [
                'pos.access', 'products.view', 'customers.manage', 'transactions.view',
                'prescriptions.manage',
            ]));
        }

        foreach (User::query()->where('is_super_admin', false)->whereNotNull('company_id')->get() as $user) {
            $registrar->setPermissionsTeamId($user->company_id);
            $user->syncRoles([]);

            if ($user->tenant_role === 'tenant_admin') {
                $user->assignRole('Tenant Admin');

                continue;
            }
            if ($user->tenant_role === 'branch_manager') {
                $user->assignRole('Branch Manager');

                continue;
            }
            if ($user->tenant_role === 'supervisor') {
                $user->assignRole('Supervisor');

                continue;
            }
            if ($user->tenant_role === 'cashier') {
                $user->assignRole('Cashier');

                continue;
            }

            match ((int) $user->is_admin) {
                3 => $user->assignRole('Branch Manager'),
                2 => $user->assignRole('Cashier'),
                1 => $user->assignRole('Supervisor'),
                default => $user->assignRole('Cashier'),
            };
        }

        $registrar->setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  Collection<int, Permission>  $all
     * @param  list<string>  $names
     * @return array<int, Permission>
     */
    private function pickPermissions(Collection $all, array $names): array
    {
        return $all->whereIn('name', $names)->values()->all();
    }
}
