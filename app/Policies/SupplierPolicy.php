<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->ownsSupplier($user, $supplier);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->ownsSupplier($user, $supplier);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->ownsSupplier($user, $supplier);
    }

    private function ownsSupplier(User $user, Supplier $supplier): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $cid = (int) ($user->company_id ?? 0);

        return $cid > 0 && (int) $supplier->company_id === $cid;
    }
}
