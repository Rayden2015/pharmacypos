<?php

namespace Tests\Concerns;

use App\Models\User;
use Database\Seeders\PermissionCatalogSeeder;
use Spatie\Permission\PermissionRegistrar;

trait GrantsTenantPermissions
{
    protected function seedPermissionsCatalog(): void
    {
        $this->seed(PermissionCatalogSeeder::class);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function grantPermissions(User $user, array $permissions): User
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($user->company_id);
        $user->givePermissionTo($permissions);
        $registrar->forgetCachedPermissions();

        return $user->fresh();
    }
}
