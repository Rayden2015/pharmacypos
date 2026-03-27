<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if (! $user->can('audit.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->company_id) {
            return false;
        }

        $actor = $auditLog->user;
        if (! $actor) {
            return false;
        }

        return (int) $actor->company_id === (int) $user->company_id;
    }
}
