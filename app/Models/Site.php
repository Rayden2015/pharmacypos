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
        'manager_name',
        'phone',
        'email',
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

    /**
     * Human-readable branch reference (e.g. #BRN001) aligned with pharmacy POS branch listings.
     */
    public function branchDisplayId(): string
    {
        $id = (string) $this->id;
        $suffix = strlen($id) <= 3 ? str_pad($id, 3, '0', STR_PAD_LEFT) : $id;

        return '#BRN'.$suffix;
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

    /**
     * Home branch for the user (within their company). Used when non-admins may only use one site in the switcher.
     */
    public static function homeSiteIdForUser(User $user): ?int
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        $companyId = $user->company_id;
        if (! $companyId) {
            return null;
        }

        if ($user->site_id) {
            $ok = static::query()
                ->where('id', $user->site_id)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->exists();
            if ($ok) {
                return (int) $user->site_id;
            }
        }

        $def = static::query()
            ->where('company_id', $companyId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->value('id');

        if ($def) {
            return (int) $def;
        }

        $any = static::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $any ? (int) $any : null;
    }

    /**
     * Branches the user may pick in the header (POS / stock context). Super admins see all active sites;
     * tenant admins see every active branch in their company; everyone else sees their home branch only.
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function forSessionSwitcher(?User $user): \Illuminate\Support\Collection
    {
        if (! $user) {
            return collect();
        }

        if ($user->isSuperAdmin()) {
            return static::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        }

        $companyId = $user->company_id;
        if (! $companyId) {
            return collect();
        }

        $base = static::query()
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if ($user->isTenantAdmin()) {
            return $base->orderBy('name')->get(['id', 'name', 'code']);
        }

        $homeId = self::homeSiteIdForUser($user);
        if (! $homeId) {
            return collect();
        }

        return $base->where('id', $homeId)->orderBy('name')->get(['id', 'name', 'code']);
    }
}
