<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Ensures Spatie "system" roles exist for one tenant (company) with correct permissions.
 */
final class TenantRolesProvisioner
{
    /**
     * Idempotent: (re)syncs Tenant Admin, Branch Manager, Supervisor, Cashier for {@see $companyId}.
     *
     * @param  Collection<int, Permission>|null  $allPermissions  Pass from seeders to avoid reloading.
     */
    public static function syncSystemRolesForCompany(int $companyId, ?Collection $allPermissions = null): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($companyId);

        $all = $allPermissions ?? Permission::query()->where('guard_name', 'web')->get();

        $tenantAdmin = Role::findOrCreate('Tenant Admin', 'web');
        $tenantAdmin->syncPermissions($all);

        $manager = Role::findOrCreate('Branch Manager', 'web');
        $manager->syncPermissions(self::pickPermissions($all, [
            'tenant.users.manage', 'sites.manage', 'pos.access', 'pos.refund',
            'products.view', 'products.manage', 'inventory.view', 'inventory.receive',
            'inventory.adjust', 'inventory.transfer', 'reports.view', 'reports.export',
            'settings.manage', 'audit.view', 'prescriptions.manage', 'customers.manage',
            'suppliers.manage', 'transactions.view',
        ]));

        $supervisor = Role::findOrCreate('Supervisor', 'web');
        $supervisor->syncPermissions(self::pickPermissions($all, [
            'pos.access', 'pos.refund', 'products.view', 'inventory.view',
            'reports.view', 'audit.view', 'prescriptions.manage', 'customers.manage', 'transactions.view',
        ]));

        $cashier = Role::findOrCreate('Cashier', 'web');
        $cashier->syncPermissions(self::pickPermissions($all, [
            'pos.access', 'products.view', 'customers.manage', 'transactions.view',
            'prescriptions.manage',
        ]));

        $registrar->setPermissionsTeamId(null);
    }

    /**
     * @param  Collection<int, Permission>  $all
     * @param  list<string>  $names
     * @return array<int, Permission>
     */
    private static function pickPermissions(Collection $all, array $names): array
    {
        return $all->whereIn('name', $names)->values()->all();
    }
}
