<?php

namespace Tests\Feature\Pharmacy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class InventoryNavigationTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeAdmin(): User
    {
        $this->seedPermissionsCatalog();

        $user = User::create([
            'name' => 'Nav Admin',
            'email' => 'nav-admin@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        return $this->grantPermissions($user, [
            'inventory.view',
            'inventory.receive',
            'products.view',
            'prescriptions.manage',
        ]);
    }

    public function test_inventory_pages_are_reachable(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)->get(route('inventory.low-stock'))->assertOk();
        $this->actingAs($user)->get(route('inventory.manage-stock'))->assertOk();
        $this->actingAs($user)->get(route('inventory.stock-adjustment.create'))->assertOk();
        $this->actingAs($user)->get(route('inventory.stock-transfer'))->assertOk();
        $this->actingAs($user)->get(route('inventory.catalog.categories'))->assertOk();
        $this->actingAs($user)->get(route('inventory.catalog.brands'))->assertRedirect(route('manufacturers.index'));
        $this->actingAs($user)->get(route('manufacturers.index'))->assertOk();
        $this->actingAs($user)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($user)->get(route('inventory.catalog.units'))->assertOk();
        $this->actingAs($user)->get(route('inventory.expiry-tracking'))->assertOk();
        $this->actingAs($user)->get(route('inventory.batches'))->assertOk();
        $this->actingAs($user)->get(route('pharmacy.prescriptions'))->assertOk();
    }

}
