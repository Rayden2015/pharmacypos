<?php

namespace App\Listeners;

use App\Support\Audit;
use Illuminate\Auth\Events\Login;

class RecordAuditLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        Audit::record(
            'auth.login',
            null,
            [
                'user_id' => $user->getKey(),
                'email' => $user->email ?? null,
                'name' => $user->name ?? null,
                'remember' => $event->remember,
            ],
            get_class($user),
            (int) $user->getKey(),
            (int) $user->getKey(),
            ['audit_channel' => 'auth']
        );
    }
}
