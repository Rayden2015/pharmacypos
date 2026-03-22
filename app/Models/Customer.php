<?php

namespace App\Models;

use App\Support\CurrentSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
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
