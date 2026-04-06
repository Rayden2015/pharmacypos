<?php

namespace App\Models;

use App\Support\CurrentSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /**
     * Canonical mobile key for matching: digits only, then last 9 digits (national significant number).
     * Numbers with 9 or fewer digits are kept in full. E.g. +2333504065214 and 0504065214 → 504065214.
     */
    public static function normalizeMobile(?string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) <= 9) {
            return $digits;
        }

        return substr($digits, -9);
    }

    /**
     * Find a customer in this company (any branch site) whose mobile matches after normalizeMobile().
     */
    public static function findForCompanyByNormalizedMobile(int $companyId, string $rawMobile): ?self
    {
        $norm = self::normalizeMobile($rawMobile);
        if ($norm === '') {
            return null;
        }

        $siteIds = Site::query()->where('company_id', $companyId)->pluck('id')->all();
        if ($siteIds === []) {
            return null;
        }

        return self::query()
            ->whereIn('site_id', $siteIds)
            ->get()
            ->first(fn (self $c) => self::normalizeMobile($c->mobile) === $norm);
    }

    protected $fillable = [
        'code',
        'name',
        'mobile',
        'email',
        'address',
        'notes',
        'site_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (Customer $customer) {
            if (empty($customer->code)) {
                $customer->code = 'CUST-'.str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT);
                $customer->saveQuietly();
            }
        });
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Company used to resolve POS orders for this directory entry (any branch in the org).
     */
    public function companyIdForSalesHistory(): ?int
    {
        if ($this->site_id) {
            $id = Site::query()->whereKey($this->site_id)->value('company_id');

            return $id !== null ? (int) $id : null;
        }
        $user = auth()->user();
        if ($user && $user->company_id) {
            return (int) $user->company_id;
        }

        return null;
    }

    /**
     * Branch staff see only customers for their site; super admins see all.
     */
    public function scopeForCurrentSiteContext(Builder $query): Builder
    {
        $viewer = auth()->user();
        if (! $viewer) {
            return $query->whereRaw('0 = 1');
        }
        if ($viewer->isSuperAdmin()) {
            return $query;
        }

        $siteId = $viewer->site_id ?? CurrentSite::id();

        return $query->where(function (Builder $q) use ($siteId) {
            $q->where('site_id', $siteId);
            if ($siteId === Site::defaultId()) {
                $q->orWhereNull('site_id');
            }
        });
    }
}
