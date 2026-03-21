<?php

namespace Tests\Feature\Pharmacy;

use App\Http\Controllers\DashboardController;
use App\Models\Prescription;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrescriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Rx User',
            'email' => uniqid('rx', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244333000',
            'status' => '1',
        ]);
    }

    public function test_guest_is_redirected_from_prescriptions(): void
    {
        $this->get(route('pharmacy.prescriptions'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_create_and_complete_prescription(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post(route('pharmacy.prescriptions.store'), [
                'patient_name' => 'Test Patient',
                'patient_phone' => '0244000001',
                'rx_number' => 'RX-100',
                'notes' => 'Demo',
            ])
            ->assertRedirect(route('pharmacy.prescriptions'));

        $rx = Prescription::query()->where('rx_number', 'RX-100')->first();
        $this->assertNotNull($rx);
        $this->assertSame('pending', $rx->status);

        $this->actingAs($user)
            ->patch(route('pharmacy.prescriptions.update', $rx), ['status' => 'completed'])
            ->assertRedirect(route('pharmacy.prescriptions'));

        $rx->refresh();
        $this->assertSame('completed', $rx->status);
        $this->assertNotNull($rx->dispensed_at);
    }

    public function test_dashboard_includes_prescription_and_ar_metrics(): void
    {
        $user = $this->makeUser();

        Prescription::create([
            'site_id' => Site::defaultId(),
            'patient_name' => 'A',
            'status' => 'completed',
            'user_id' => $user->id,
        ]);
        Prescription::create([
            'site_id' => Site::defaultId(),
            'patient_name' => 'B',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        $d = DashboardController::dashboardViewData();
        $this->assertSame(1, $d['rx_completed']);
        $this->assertSame(1, $d['rx_pending']);
        $this->assertGreaterThanOrEqual(2, $d['prescriptions_last_30']);
    }
}
