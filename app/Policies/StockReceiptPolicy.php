<?php

namespace App\Policies;

use App\Models\StockReceipt;
use App\Models\User;

class StockReceiptPolicy
{
    public function view(User $user, StockReceipt $stockReceipt): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $site = $stockReceipt->site;
        if (! $site) {
            return false;
        }

        $cid = (int) ($user->company_id ?? 0);

        return $cid > 0 && (int) $site->company_id === $cid;
    }
}
