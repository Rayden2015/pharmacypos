<?php

namespace Tests\Feature\Pharmacy;

use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsTenantPermissions;
use Tests\TestCase;

class DoctorsTest extends TestCase
{
    use GrantsTenantPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(): User
    {
        $this->seedPermissionsCatalog();

        $user = User::create([
            'name' => 'Doc User',
            'email' => uniqid('doc', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        return $this->grantPermissions($user, ['prescriptions.manage']);
    }

    public function test_guest_cannot_access_doctors(): void
    {
        $this->get(route('pharmacy.doctors.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_prescriptions_permission_gets_403_on_doctors(): void
    {
        $this->seedPermissionsCatalog();
        $user = User::create([
            'name' => 'No Rx',
            'email' => uniqid('norx', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'mobile' => '0244333002',
            'status' => '1',
        ]);

        $this->actingAs($user)
            ->get(route('pharmacy.doctors.index'))
            ->assertForbidden();
    }

    public function test_user_can_create_list_and_delete_doctor(): void
    {
        $user = $this->makeUser();
        $siteId = Site::defaultId();

        $this->actingAs($user)
            ->post(route('pharmacy.doctors.store'), [
                'name' => 'Dr. Test',
                'specialty' => 'GP',
                'phone' => '0200000000',
                'email' => 'dr.test@example.test',
                'license_number' => 'GMC-1',
            ])
            ->assertRedirect(route('pharmacy.doctors.index'));

        $doctor = Doctor::query()->where('name', 'Dr. Test')->first();
        $this->assertNotNull($doctor);
        $this->assertSame($siteId, (int) $doctor->site_id);

        $this->actingAs($user)
            ->get(route('pharmacy.doctors.index'))
            ->assertOk()
            ->assertSee('Dr. Test', false);

        $this->actingAs($user)
            ->delete(route('pharmacy.doctors.destroy', $doctor))
            ->assertRedirect(route('pharmacy.doctors.index'));
    }

    public function test_cannot_delete_doctor_linked_to_prescription(): void
    {
        $user = $this->makeUser();
        $doctor = Doctor::create([
            'site_id' => Site::defaultId(),
            'name' => 'Dr. Linked',
        ]);

        Prescription::create([
            'site_id' => Site::defaultId(),
            'doctor_id' => $doctor->id,
            'patient_name' => 'Patient',
            'status' => 'pending',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('pharmacy.doctors.destroy', $doctor))
            ->assertRedirect(route('pharmacy.doctors.index'))
            ->assertSessionHas('error');

        $this->assertTrue(Doctor::query()->whereKey($doctor->id)->exists());
    }

    public function test_prescription_can_reference_doctor(): void
    {
        $user = $this->makeUser();
        $doctor = Doctor::create([
            'site_id' => Site::defaultId(),
            'name' => 'Dr. Rx',
            'specialty' => 'Cardiology',
        ]);

        $this->actingAs($user)
            ->post(route('pharmacy.prescriptions.store'), [
                'patient_name' => 'Jane',
                'doctor_id' => $doctor->id,
            ])
            ->assertRedirect(route('pharmacy.prescriptions'));

        $rx = Prescription::query()->where('patient_name', 'Jane')->first();
        $this->assertNotNull($rx);
        $this->assertSame($doctor->id, (int) $rx->doctor_id);
    }
}
