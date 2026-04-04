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

        $tenantId = (int) $user->company_id;

        if ($auditLog->company_id !== null) {
            return (int) $auditLog->company_id === $tenantId;
        }

        $actor = $auditLog->user;
        if (! $actor) {
            return false;
        }

        return (int) $actor->company_id === $tenantId;
    }
}
