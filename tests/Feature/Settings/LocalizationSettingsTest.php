<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_localization_settings_page_loads(): void
    {
        $user = User::create([
            'name' => 'Loc User',
            'email' => uniqid('loc', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222001',
            'status' => '1',
        ]);

        $this->actingAs($user)
            ->get(route('settings.localization'))
            ->assertOk()
            ->assertSee('Regional settings', false);
    }

    public function test_user_can_save_localization_settings(): void
    {
        $user = User::create([
            'name' => 'Loc Save',
            'email' => uniqid('locs', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222002',
            'status' => '1',
        ]);

        $response = $this->actingAs($user)->put(route('settings.localization.update'), [
            'currency_symbol' => '₦',
            'currency_code' => 'NGN',
            'app_locale' => 'en',
            'app_timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'g:i A',
        ]);

        $response->assertRedirect(route('settings.localization'));
        $response->assertSessionHas('success');

        Setting::clearRuntimeCache();
        $this->assertSame('₦', Setting::where('key', 'currency_symbol')->value('value'));
        $this->assertSame('NGN', Setting::where('key', 'currency_code')->value('value'));
        $this->assertSame('UTC', Setting::where('key', 'app_timezone')->value('value'));
        $this->assertSame('Y-m-d', Setting::where('key', 'date_format')->value('value'));
        $this->assertSame('g:i A', Setting::where('key', 'time_format')->value('value'));
    }
}
