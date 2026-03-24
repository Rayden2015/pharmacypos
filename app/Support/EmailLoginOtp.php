<?php

namespace App\Support;

use App\Mail\TwoFactorLoginCode;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailLoginOtp
{
    public static function cacheKey(int $userId): string
    {
        return 'two_factor_login:'.$userId;
    }

    /**
     * Generate a 6-digit code, store hash in cache (10 min), and email the user.
     */
    public static function send(User $user): bool
    {
        $code = (string) random_int(100000, 999999);
        Cache::put(self::cacheKey($user->id), hash('sha256', $code), now()->addMinutes(10));

        try {
            Mail::to($user->email)->send(new TwoFactorLoginCode($code));
        } catch (\Throwable $e) {
            report($e);
            Cache::forget(self::cacheKey($user->id));
            Log::channel('audit')->warning('auth.two_factor.email_send_failed', [
                'user_id' => $user->id,
                'site_id' => $user->site_id,
                'company_id' => $user->company_id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }

        Log::channel('audit')->info('auth.two_factor.email_code_sent', [
            'user_id' => $user->id,
            'site_id' => $user->site_id,
            'company_id' => $user->company_id,
        ]);

        return true;
    }
}
