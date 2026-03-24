<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
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
            'name' => 'Profile User',
            'email' => uniqid('prof', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);
    }

    /**
     * Daily audit log path (see config/logging.php channel "audit").
     */
    private function auditLogPathForToday(): string
    {
        return storage_path('logs/audit-'.date('Y-m-d').'.log');
    }

    private function assertAuditLogContains(string $needle): void
    {
        $path = $this->auditLogPathForToday();
        $this->assertFileExists($path, 'Audit log file should exist after profile actions (config channel "audit").');
        $this->assertStringContainsString($needle, file_get_contents($path));
    }

    public function test_guest_is_redirected_from_profile(): void
    {
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('Basic Information', false)
            ->assertSee('Change password', false)
            ->assertSee('Two step verification', false);
    }

    public function test_user_can_update_profile_details(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                '_section' => 'profile',
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => $user->email,
                'mobile' => '0244222000',
                'address_line1' => 'Line 1',
                'city' => 'Accra',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('Line 1', $user->address_line1);
        $this->assertSame('Accra', $user->city);

        $this->assertAuditLogContains('profile.updated');
        $this->assertAuditLogContains('"user_id":'.$user->id);
        $this->assertAuditLogContains('"section":"basic"');
    }

    public function test_profile_update_without_optional_address_fields_does_not_error(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                '_section' => 'profile',
                'first_name' => 'Only',
                'last_name' => 'Required',
                'email' => $user->email,
                'mobile' => '0244222000',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->address_line2);
        $this->assertNull($user->country);
    }

    public function test_profile_validation_requires_first_name(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.update'), [
                '_section' => 'profile',
                'last_name' => 'X',
                'email' => $user->email,
                'mobile' => '0244222000',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('first_name');
    }

    public function test_profile_validation_rejects_duplicate_email(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.update'), [
                '_section' => 'profile',
                'first_name' => 'Dup',
                'last_name' => 'Test',
                'email' => $other->email,
                'mobile' => '0244222000',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('email');
    }

    public function test_profile_can_upload_avatar(): void
    {
        Storage::fake('local');
        $user = $this->makeUser();

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                '_section' => 'profile',
                'first_name' => 'Photo',
                'last_name' => 'User',
                'email' => $user->email,
                'mobile' => '0244222000',
                'user_img' => $file,
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertNotSame('user.png', $user->user_img);
        Storage::disk('local')->assertExists('public/users/'.$user->user_img);
    }

    public function test_wrong_current_password_is_rejected_when_changing_password(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->from(route('profile'))
            ->put(route('profile.update'), [
                '_section' => 'password',
                'current_password' => 'wrong',
                'password' => 'newpass12',
                'password_confirmation' => 'newpass12',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHasErrors('current_password');
    }

    public function test_user_can_change_password_with_correct_current(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                '_section' => 'password',
                'current_password' => 'secret',
                'password' => 'newpass12',
                'password_confirmation' => 'newpass12',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('newpass12', $user->password));

        $this->assertAuditLogContains('"section":"password"');
        $this->assertAuditLogContains('"user_id":'.$user->id);
    }

    public function test_user_can_save_two_factor_preferences(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                '_section' => 'security',
                'two_factor_email' => '1',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->notification_preferences['two_factor_email'] ?? false);
        $this->assertFalse($user->notification_preferences['two_factor_sms'] ?? false);

        $this->assertAuditLogContains('"section":"security"');
    }

    public function test_security_section_can_enable_sms_and_email(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                '_section' => 'security',
                'two_factor_sms' => '1',
                'two_factor_email' => '1',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->notification_preferences['two_factor_sms'] ?? false);
        $this->assertTrue($user->notification_preferences['two_factor_email'] ?? false);
    }
}
