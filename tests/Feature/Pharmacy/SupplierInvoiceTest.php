<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class SupplierInvoiceTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeManager(): User
    {
        $this->seedPermissionsCatalog();

        $user = User::create([
            'name' => 'AP Manager',
            'email' => uniqid('ap', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
            'company_id' => Company::defaultId(),
        ]);

        return $this->grantPermissions($user, ['suppliers.manage']);
    }

    public function test_guest_redirected_from_vendor_payments(): void
    {
        $this->get(route('supplier-invoices.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_403(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'No AP',
            'email' => uniqid('noap', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222001',
            'status' => '1',
            'company_id' => Company::defaultId(),
        ]);

        $this->actingAs($user)->get(route('supplier-invoices.index'))->assertForbidden();
    }

    public function test_create_list_edit_delete_vendor_invoice(): void
    {
        $user = $this->makeManager();
        $supplier = Supplier::create([
            'company_id' => (int) $user->company_id,
            'supplier_name' => 'MedLife Test',
            'address' => 'Addr',
            'mobile' => '0200000000',
            'email' => 'm@example.test',
        ]);

        $this->actingAs($user)
            ->post(route('supplier-invoices.store'), [
                'supplier_id' => $supplier->id,
                'invoice_number' => 'INV-AP-1',
                'invoice_date' => '2026-03-01',
                'due_date' => '2026-03-15',
                'total_amount' => '120.00',
                'paid_amount' => '120.00',
                'payment_method' => 'Card',
                'notes' => null,
            ])
            ->assertRedirect(route('supplier-invoices.index'));

        $inv = SupplierInvoice::query()->where('invoice_number', 'INV-AP-1')->first();
        $this->assertNotNull($inv);
        $this->assertSame((int) $user->company_id, (int) $inv->company_id);
        $this->assertStringStartsWith('VP-', $inv->reference);
        $this->assertSame('paid', $inv->computedStatus());

        $this->actingAs($user)
            ->get(route('supplier-invoices.index'))
            ->assertOk()
            ->assertSee('MedLife Test', false)
            ->assertSee('INV-AP-1', false);

        $this->actingAs($user)
            ->put(route('supplier-invoices.update', $inv), [
                'supplier_id' => $supplier->id,
                'invoice_number' => 'INV-AP-1',
                'invoice_date' => '2026-03-01',
                'due_date' => '2026-12-31',
                'total_amount' => '120.00',
                'paid_amount' => '50.00',
                'payment_method' => 'UPI',
                'notes' => 'partial',
            ])
            ->assertRedirect(route('supplier-invoices.index'));

        $inv->refresh();
        $this->assertSame('partially_paid', $inv->computedStatus());

        $this->actingAs($user)
            ->delete(route('supplier-invoices.destroy', $inv))
            ->assertRedirect(route('supplier-invoices.index'));

        $this->assertNull(SupplierInvoice::query()->find($inv->id));
    }
}
