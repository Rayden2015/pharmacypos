<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Keeps {@see User::$tenant_role}, legacy {@see User::$is_admin}, and Spatie built-in roles aligned.
 */
final class TenantUserRoles
{
    /**
     * Map Spatie role display name (per company) to hierarchy fields.
     * Custom roles get no tenant_role slot; permissions come from Spatie only.
     *
     * @return array{tenant_role: string|null, is_admin: int}
     */
    public static function mapSpatieRoleNameToTenantFields(string $roleName): array
    {
        return match ($roleName) {
            'Tenant Admin' => ['tenant_role' => 'tenant_admin', 'is_admin' => 0],
            'Branch Manager' => ['tenant_role' => 'branch_manager', 'is_admin' => 3],
            'Supervisor' => ['tenant_role' => 'supervisor', 'is_admin' => 1],
            'Cashier' => ['tenant_role' => 'cashier', 'is_admin' => 2],
            default => ['tenant_role' => null, 'is_admin' => 0],
        };
    }

    /**
     * Assign a tenant company Spatie role and align {@see User::$tenant_role} / {@see User::$is_admin}.
     */
    public static function syncSpatieRoleAssignment(User $user, int $roleId): void
    {
        if ($user->is_super_admin || ! $user->company_id) {
            return;
        }

        $role = Role::query()
            ->whereKey($roleId)
            ->where('company_id', $user->company_id)
            ->where('guard_name', 'web')
            ->firstOrFail();

        $mapped = self::mapSpatieRoleNameToTenantFields((string) $role->name);
        $user->tenant_role = $mapped['tenant_role'];
        $user->is_admin = $mapped['is_admin'];
        $user->save();

        TenantRolesProvisioner::syncSystemRolesForCompany($user->company_id);

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->company_id);
        $user->syncRoles([]);
        $user->assignRole($role);
        $registrar->setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

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
