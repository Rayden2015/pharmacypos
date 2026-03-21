<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\CurrentSite;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Auditable;
    use HasFactory, Notifiable;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (User $user) {
            if ($user->site_id === null && ! $user->is_super_admin) {
                $user->site_id = Site::defaultId();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'confirm_password',
        'is_admin',
        'is_super_admin',
        'site_id',
        'mobile',
        'user_img',
        'status',
        'address',

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'confirm_password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin' => 'boolean',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Branch staff see only users at their assigned branch; super admins see everyone.
     *
     * Uses the viewer's {@see User::$site_id} (home branch), not the header "active site"
     * session, so switching POS/inventory context does not hide the employee list.
     * Legacy rows with null site_id are included when viewing the default site.
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

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
