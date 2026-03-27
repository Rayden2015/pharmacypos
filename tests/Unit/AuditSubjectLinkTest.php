<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Prescription;
use App\Models\User;
use App\Support\AuditSubjectLink;
use Tests\TestCase;

class AuditSubjectLinkTest extends TestCase
{
    public function test_product_maps_to_edit_url(): void
    {
        $url = AuditSubjectLink::url(Product::class, 42, null);
        $this->assertNotNull($url);
        $this->assertStringContainsString('products', $url);
        $this->assertStringContainsString('42', $url);
    }

    public function test_prescription_maps_to_show_url(): void
    {
        $url = AuditSubjectLink::url(Prescription::class, 12, null);
        $this->assertNotNull($url);
        $this->assertStringContainsString('prescriptions', $url);
        $this->assertStringContainsString('12', $url);
    }

    public function test_customer_maps_to_edit_url(): void
    {
        $url = AuditSubjectLink::url(Customer::class, 8, null);
        $this->assertNotNull($url);
        $this->assertStringContainsString('customers', $url);
        $this->assertStringContainsString('8', $url);
        $this->assertStringContainsString('edit', $url);
    }

    public function test_company_hidden_for_non_super_admin(): void
    {
        $user = new User([
            'company_id' => 1,
            'is_super_admin' => false,
        ]);
        $this->assertNull(AuditSubjectLink::url(Company::class, 99, $user));
    }

    public function test_company_maps_for_super_admin(): void
    {
        $user = new User([
            'is_super_admin' => true,
        ]);
        $url = AuditSubjectLink::url(Company::class, 3, $user);
        $this->assertNotNull($url);
        $this->assertStringContainsString('super-admin', $url);
    }
}
