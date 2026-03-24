<?php

namespace Tests\Feature\Auth;

use App\Mail\TwoFactorLoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'TF User',
            'email' => uniqid('tf', true).'@example.test',
            'password' => bcrypt('secret'),
            'confirm_password' => bcrypt('secret'),
            'is_admin' => 1,
            'is_super_admin' => false,
            'mobile' => '0244222000',
            'status' => '1',
        ], $overrides));
    }

    public function test_login_without_email_2fa_goes_to_home(): void
    {
        $user = $this->makeUser();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_email_2fa_redirects_to_challenge_and_sends_mail(): void
    {
        Mail::fake();
        $user = $this->makeUser([
            'notification_preferences' => ['two_factor_email' => true],
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();
        Mail::assertSent(TwoFactorLoginCode::class);
    }

    public function test_user_can_complete_login_with_valid_code(): void
    {
        Mail::fake();
        $user = $this->makeUser([
            'notification_preferences' => ['two_factor_email' => true],
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertRedirect(route('two-factor.challenge'));

        $code = null;
        Mail::assertSent(TwoFactorLoginCode::class, function (TwoFactorLoginCode $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertNotNull($code);

        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_code_is_rejected(): void
    {
        Mail::fake();
        $user = $this->makeUser([
            'notification_preferences' => ['two_factor_email' => true],
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret',
        ]);

        $this->post(route('two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }
}
