<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EmailLoginOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function show(Request $request)
    {
        if (! $request->session()->has('two_factor_login_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $userId = $request->session()->get('two_factor_login_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => 'required|string|max:12',
        ]);

        $code = preg_replace('/\D/', '', $request->input('code', ''));
        if (strlen($code) !== 6) {
            throw ValidationException::withMessages([
                'code' => [__('Enter the 6-digit code from your email.')],
            ]);
        }

        $cacheKey = EmailLoginOtp::cacheKey((int) $userId);
        $expectedHash = Cache::get($cacheKey);
        $hash = hash('sha256', $code);

        if (! $expectedHash || ! hash_equals($expectedHash, $hash)) {
            $attempts = (int) $request->session()->get('two_factor_login_attempts', 0) + 1;
            $request->session()->put('two_factor_login_attempts', $attempts);

            Log::channel('audit')->warning('auth.two_factor.email_code_failed', [
                'user_id' => (int) $userId,
                'attempt' => $attempts,
                'ip' => $request->ip(),
            ]);

            if ($attempts >= 8) {
                $request->session()->forget([
                    'two_factor_login_user_id',
                    'two_factor_login_remember',
                    'two_factor_login_attempts',
                ]);
                Cache::forget($cacheKey);

                Log::channel('audit')->warning('auth.two_factor.email_locked_out', [
                    'user_id' => (int) $userId,
                    'ip' => $request->ip(),
                ]);

                throw ValidationException::withMessages([
                    'code' => [__('Too many attempts. Try signing in again.')],
                ]);
            }

            throw ValidationException::withMessages([
                'code' => [__('That code is invalid or expired.')],
            ]);
        }

        $user = User::query()->find($userId);
        if (! $user) {
            $request->session()->forget(['two_factor_login_user_id', 'two_factor_login_remember', 'two_factor_login_attempts']);
            Cache::forget(EmailLoginOtp::cacheKey((int) $userId));

            return redirect()->route('login');
        }

        $remember = (bool) $request->session()->get('two_factor_login_remember', false);

        Cache::forget($cacheKey);
        $request->session()->forget([
            'two_factor_login_user_id',
            'two_factor_login_remember',
            'two_factor_login_attempts',
        ]);

        Auth::login($user, $remember);

        $request->session()->regenerate();
        if ($request->hasSession()) {
            $request->session()->put('auth.password_confirmed_at', time());
        }

        Log::channel('audit')->info('auth.two_factor.email_completed', [
            'user_id' => $user->id,
            'site_id' => $user->site_id,
            'company_id' => $user->company_id,
        ]);

        return redirect()->intended('/home');
    }

    public function resend(Request $request)
    {
        $userId = $request->session()->get('two_factor_login_user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $sent = (int) $request->session()->get('two_factor_resend_count', 0);
        if ($sent >= 5) {
            Log::channel('audit')->warning('auth.two_factor.email_resend_limit', [
                'user_id' => (int) $userId,
                'ip' => $request->ip(),
            ]);

            return redirect()->route('two-factor.challenge')->with('error', __('Too many resend attempts. Wait a few minutes or sign in again.'));
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->wantsEmailTwoFactorLogin()) {
            return redirect()->route('login');
        }

        if (! EmailLoginOtp::send($user)) {
            return redirect()->route('two-factor.challenge')->with('error', __('Could not send email. Check mail settings or try again later.'));
        }

        $request->session()->put('two_factor_resend_count', $sent + 1);

        return redirect()->route('two-factor.challenge')->with('success', __('A new code was sent to your email.'));
    }
}
