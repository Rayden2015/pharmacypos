<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use Auditable;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Site $site) {
            if ($site->company_id === null) {
                $default = static::query()->where('is_default', true)->first();
                $site->company_id = $default?->company_id ?? Company::defaultId();
            }
        });
    }

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'address',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function productSiteStocks(): HasMany
    {
        return $this->hasMany(ProductSiteStock::class, 'site_id');
    }

    /**
     * Tenant staff only see branches belonging to their organization.
     */
    public function scopeForUserTenant(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->company_id) {
            return $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    public static function defaultId(): int
    {
        $id = static::query()->where('is_default', true)->value('id');

        return (int) ($id ?? static::query()->orderBy('id')->value('id'));
    }
}
