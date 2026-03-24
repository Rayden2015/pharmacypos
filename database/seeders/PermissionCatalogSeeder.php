<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * Global permission names (not tenant-scoped). Roles are per-company and attach these.
 */
class PermissionCatalogSeeder extends Seeder
{
    /** @var list<string> */
    public const NAMES = [
        'tenant.roles.manage',
        'tenant.users.manage',
        'sites.manage',
        'pos.access',
        'pos.refund',
        'products.view',
        'products.manage',
        'inventory.view',
        'inventory.receive',
        'inventory.adjust',
        'inventory.transfer',
        'reports.view',
        'reports.export',
        'settings.manage',
        // Rx queue + prescriber directory (doctors); same capability for operational consistency.
        'prescriptions.manage',
        'customers.manage',
        'suppliers.manage',
        'transactions.view',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Permission::query()->firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
        }
    }
}
