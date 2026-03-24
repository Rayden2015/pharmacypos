<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_notifications_settings_page_loads(): void
    {
        $user = User::create([
            'name' => 'Prefs User',
            'email' => uniqid('ns', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        $this->actingAs($user)
            ->get(route('settings.notifications'))
            ->assertOk()
            ->assertSee('Notification preferences', false);
    }

    public function test_user_can_save_notification_preferences(): void
    {
        $user = User::create([
            'name' => 'Prefs User 2',
            'email' => uniqid('ns2', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ]);

        $this->actingAs($user)
            ->put(route('settings.notifications.update'), [
                'announcements_enabled' => '1',
                'direct_messages_enabled' => '0',
                'email_notifications_enabled' => '1',
                'email_low_stock' => '1',
                'email_expiry_alerts' => '0',
                'email_sales_digest' => '0',
            ])
            ->assertRedirect(route('settings.notifications'));

        $user->refresh();
        $this->assertTrue($user->notificationPreference('announcements_enabled', false));
        $this->assertFalse($user->notificationPreference('direct_messages_enabled', true));
        $this->assertTrue($user->notificationPreference('email_notifications_enabled', false));
        $this->assertTrue($user->notificationPreference('email_low_stock', false));
    }
}
