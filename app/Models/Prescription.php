<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\CurrentSite;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescription extends Model
{
    use Auditable;

    protected $fillable = [
        'site_id',
        'doctor_id',
        'patient_name',
        'patient_phone',
        'rx_number',
        'status',
        'notes',
        'attachment_path',
        'user_id',
        'order_id',
        'dispensed_at',
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Branch staff see prescriptions for their site; super admins see all.
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
