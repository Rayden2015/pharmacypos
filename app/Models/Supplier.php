<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use Auditable;

    protected $table = 'suppliers';

    protected $fillable = [
        'company_id',
        'supplier_name',
        'address',
        'mobile',
        'email',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Tenant staff only see vendors attached to their organization.
     */
    public function scopeForUserTenant(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->company_id) {
            return $query->where('company_id', $user->company_id);
        }

        return $query->whereRaw('0 = 1');
    }

    public function stockReceipts()
    {
        return $this->hasMany(StockReceipt::class);
    }

    public function preferredByProducts()
    {
        return $this->hasMany(Product::class, 'preferred_supplier_id');
    }
}
