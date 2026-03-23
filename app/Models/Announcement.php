<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    protected $fillable = [
        'company_id',
        'site_id',
        'author_id',
        'title',
        'body',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Tenant-wide: site_id is null. Site-only: site_id matches the viewer's branch
     * (including legacy users with null site_id mapped to the default site).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || ! $user->company_id) {
            return $query->whereRaw('0 = 1');
        }

        $effectiveSiteId = (int) ($user->site_id ?? Site::defaultId());

        return $query->where('announcements.company_id', $user->company_id)
            ->where(function (Builder $q) use ($effectiveSiteId) {
                $q->whereNull('announcements.site_id')
                    ->orWhere('announcements.site_id', $effectiveSiteId);
            });
    }

    public function scopeUnreadFor(Builder $query, User $user): Builder
    {
        return $query->whereDoesntHave('readByUsers', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }
}
