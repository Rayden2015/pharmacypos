<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

/**
 * Keeps {@see User::$tenant_role}, legacy {@see User::$is_admin}, and Spatie built-in roles aligned.
 */
final class TenantUserRoles
{
    /**
     * Sync Tenant Admin / Branch Manager / Supervisor / Cashier Spatie role for a tenant user.
     */
    public static function syncBuiltInSpatieRole(User $user): void
    {
        if ($user->is_super_admin || ! $user->company_id) {
            return;
        }

        TenantRolesProvisioner::syncSystemRolesForCompany($user->company_id);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->company_id);
        $user->syncRoles([]);

        if ($user->tenant_role === 'tenant_admin') {
            $user->assignRole('Tenant Admin');
        } elseif ($user->tenant_role === 'branch_manager') {
            $user->assignRole('Branch Manager');
        } elseif ($user->tenant_role === 'supervisor') {
            $user->assignRole('Supervisor');
        } elseif ($user->tenant_role === 'cashier') {
            $user->assignRole('Cashier');
        } else {
            match ((int) $user->is_admin) {
                3 => $user->assignRole('Branch Manager'),
                1 => $user->assignRole('Supervisor'),
                default => $user->assignRole('Cashier'),
            };
        }

        $registrar->setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array{tenant_role: string|null, is_admin: int}
     */
    public static function tenantRoleAndLegacyIsAdmin(string $hierarchyRole): array
    {
        return match ($hierarchyRole) {
            'tenant_admin' => ['tenant_role' => 'tenant_admin', 'is_admin' => 0],
            'branch_manager' => ['tenant_role' => 'branch_manager', 'is_admin' => 3],
            'supervisor' => ['tenant_role' => 'supervisor', 'is_admin' => 1],
            'cashier' => ['tenant_role' => 'cashier', 'is_admin' => 2],
            default => ['tenant_role' => 'cashier', 'is_admin' => 2],
        };
    }
}
