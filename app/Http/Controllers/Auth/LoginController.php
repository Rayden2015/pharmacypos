<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailLoginOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Handle a login request to the application.
     *
     * When the user enables email two-factor in profile, password success sends a code by mail
     * before the session is fully established.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            /** @var User $user */
            $user = $this->guard()->user();

            if ($user->wantsEmailTwoFactorLogin()) {
                $remember = $request->boolean('remember');
                $this->guard()->logout();

                $request->session()->put('two_factor_login_user_id', $user->id);
                $request->session()->put('two_factor_login_remember', $remember);
                $request->session()->put('two_factor_resend_count', 0);
                $request->session()->put('two_factor_login_attempts', 0);

                if (! EmailLoginOtp::send($user)) {
                    $request->session()->forget(EmailLoginOtp::SESSION_KEYS);

                    return redirect()->route('login')
                        ->withInput($request->only('email', 'remember'))
                        ->with('error', __('Could not send verification email. Please try again or contact support.'));
                }

                $this->clearLoginAttempts($request);

                return redirect()->route('two-factor.challenge');
            }

            if ($user->wantsSmsTwoFactorLogin() && ! $user->wantsEmailTwoFactorLogin()) {
                Log::channel('audit')->info('auth.two_factor.sms_only_skipped', [
                    'user_id' => $user->id,
                    'site_id' => $user->site_id,
                    'company_id' => $user->company_id,
                    'message' => 'SMS login code not enforced until SMS delivery is configured.',
                ]);
            }

            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }

            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
}
