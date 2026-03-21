<?php

namespace App\Listeners;

use App\Support\Audit;
use Illuminate\Auth\Events\Logout;

class RecordAuditLogout
{
    public function handle(Logout $event): void
    {
        $user = $event->user;
        if (! $user) {
            return;
        }

        Audit::record(
            'auth.logout',
            ['user_id' => $user->getKey()],
            null,
            get_class($user),
            (int) $user->getKey(),
            (int) $user->getKey(),
            ['audit_channel' => 'auth']
        );
    }
}
