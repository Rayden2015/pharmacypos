<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\CurrentSite;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Auditable;
    use HasFactory, HasRoles, Notifiable;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (User $user) {
            if ($user->site_id === null && ! $user->is_super_admin) {
                $user->site_id = Site::defaultId();
            }
            if ($user->company_id === null && ! $user->is_super_admin) {
                $user->company_id = Company::defaultId();
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
        'company_id',
        'tenant_role',
        'site_id',
        'mobile',
        'user_img',
        'status',
        'address',
        'notification_preferences',

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
        'notification_preferences' => 'array',
    ];

    /**
     * Per-user notification toggles (see settings/notifications).
     *
     * @param  bool  $default  When the key is missing or preferences are null.
     */
    public function notificationPreference(string $key, bool $default = true): bool
    {
        $prefs = $this->notification_preferences;
        if (! is_array($prefs) || ! array_key_exists($key, $prefs)) {
            return $default;
        }

        return filter_var($prefs[$key], FILTER_VALIDATE_BOOLEAN);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** Platform operator (Dreams POS “Super Admin” sidebar). */
    public function isPlatformSuperAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    /** Tenant-level administrator (manages org, branches, billing contact). */
    public function isTenantAdmin(): bool
    {
        return $this->tenant_role === 'tenant_admin';
    }

    /**
     * Hierarchy: tenant_admin → branch_manager → supervisor → cashier → officer.
     * Legacy is_admin: 1 ≈ branch admin, 2 = cashier, 3 = manager maps to branch_manager.
     */
    public function hierarchyLabel(): string
    {
        if ($this->is_super_admin) {
            return 'Super admin (platform)';
        }
        if ($this->tenant_role) {
            return match ($this->tenant_role) {
                'tenant_admin' => 'Tenant admin',
                'branch_manager' => 'Branch manager',
                'supervisor' => 'Supervisor',
                'cashier' => 'Cashier',
                'officer' => 'Officer',
                default => $this->tenant_role,
            };
        }

        return match ((int) $this->is_admin) {
            1 => 'Admin (branch)',
            2 => 'Cashier',
            3 => 'Manager',
            default => 'Staff',
        };
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

    public function sentDirectMessages()
    {
        return $this->hasMany(DirectMessage::class, 'sender_id');
    }

    public function receivedDirectMessages()
    {
        return $this->hasMany(DirectMessage::class, 'recipient_id');
    }

    public function readAnnouncements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /** Tenant staff messaging & announcements (not platform super admins). */
    public function canUseTenantCommunications(): bool
    {
        return ! $this->isSuperAdmin() && $this->company_id !== null;
    }

    /**
     * Who may post org/branch announcements: tenant admins, branch managers, legacy managers.
     */
    public function canPublishAnnouncements(): bool
    {
        if (! $this->canUseTenantCommunications()) {
            return false;
        }

        if ($this->isTenantAdmin()) {
            return true;
        }

        if ($this->tenant_role === 'branch_manager') {
            return true;
        }

        return (int) $this->is_admin === 3;
    }

    /** Branch-scoped announcement authors may only target their home branch. */
    public function isBranchAnnouncementAuthor(): bool
    {
        return $this->tenant_role === 'branch_manager' || ((int) $this->is_admin === 3 && ! $this->isTenantAdmin());
    }
}
