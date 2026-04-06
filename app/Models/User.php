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

    /**
     * Built-in tenant hierarchy (forms & badges). Order: tenant admin → branch manager → supervisor → cashier.
     *
     * @var array<string, string>
     */
    public const HIERARCHY_ROLE_LABELS = [
        'tenant_admin' => 'Tenant admin',
        'branch_manager' => 'Branch manager',
        'supervisor' => 'Supervisor',
        'cashier' => 'Cashier',
    ];

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
        'first_name',
        'last_name',
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
        'address_line1',
        'address_line2',
        'country',
        'city',
        'state_region',
        'postal_code',
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

    /** Email OTP at sign-in (profile → Two step verification). */
    public function wantsEmailTwoFactorLogin(): bool
    {
        return $this->notificationPreference('two_factor_email', false);
    }

    /** SMS OTP at sign-in (when wired to your SMS provider). */
    public function wantsSmsTwoFactorLogin(): bool
    {
        return $this->notificationPreference('two_factor_sms', false);
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
     * Legacy {@see $is_admin} aligns with {@see TenantRolesBootstrapSeeder}: 1 → Supervisor, 2 → Cashier, 3 → Branch manager.
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
            1 => 'Supervisor',
            2 => 'Cashier',
            3 => 'Branch manager',
            default => 'Staff',
        };
    }

    /**
     * Role key for employee forms: prefers {@see $tenant_role}, else maps legacy {@see $is_admin}.
     */
    public function hierarchyRoleKey(): string
    {
        if ($this->tenant_role && array_key_exists($this->tenant_role, self::HIERARCHY_ROLE_LABELS)) {
            return $this->tenant_role;
        }
        if ($this->tenant_role === 'officer') {
            return 'cashier';
        }

        return match ((int) $this->is_admin) {
            1 => 'supervisor',
            2 => 'cashier',
            3 => 'branch_manager',
            default => 'cashier',
        };
    }

    /**
     * Spatie role id for this user's organization (for employee forms). Null if custom/no role or super admin.
     */
    public function primarySpatieRoleIdForCompany(): ?int
    {
        if (! $this->company_id || $this->is_super_admin) {
            return null;
        }

        $registrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($this->company_id);
        $role = $this->roles()->reorder()->first();
        $registrar->setPermissionsTeamId(null);

        if ($role) {
            return (int) $role->id;
        }

        $fallbackName = match ($this->tenant_role) {
            'tenant_admin' => 'Tenant Admin',
            'branch_manager' => 'Branch Manager',
            'supervisor' => 'Supervisor',
            'cashier' => 'Cashier',
            default => null,
        };
        if (! $fallbackName) {
            return null;
        }

        return (int) \Spatie\Permission\Models\Role::query()
            ->where('company_id', $this->company_id)
            ->where('guard_name', 'web')
            ->where('name', $fallbackName)
            ->value('id');
    }

    /** Bootstrap badge class for employee list / grid role column. */
    public function employeeRoleBadgeClass(): string
    {
        if ($this->is_super_admin) {
            return 'bg-warning text-dark';
        }
        if ($this->tenant_role && array_key_exists($this->tenant_role, self::HIERARCHY_ROLE_LABELS)) {
            return match ($this->tenant_role) {
                'tenant_admin' => 'bg-primary',
                'branch_manager' => 'bg-info text-dark',
                'supervisor' => 'bg-success',
                'cashier' => 'bg-secondary',
                default => 'bg-secondary',
            };
        }
        if ($this->tenant_role === 'officer') {
            return 'bg-dark';
        }

        return match ((int) $this->is_admin) {
            1 => 'bg-success',
            2 => 'bg-secondary',
            3 => 'bg-info text-dark',
            default => 'bg-secondary',
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

    /**
     * True when a real upload exists (not the default user.png placeholder).
     */
    public function hasProfilePhoto(): bool
    {
        $img = $this->user_img ?? '';

        return $img !== '' && $img !== 'user.png';
    }

    /**
     * Public URL for the profile image, or null to show a silhouette placeholder in the UI.
     * Uses an application route so images load without requiring the public/storage symlink.
     */
    public function profilePhotoUrl(): ?string
    {
        if (! $this->hasProfilePhoto()) {
            return null;
        }

        return $this->avatarUrl();
    }

    /**
     * Image URL for avatar (custom upload or default user.png) — always goes through {@see route('public.user-avatar')}.
     */
    public function avatarUrl(): string
    {
        $name = $this->user_img;
        if ($name === null || $name === '') {
            $name = 'user.png';
        }

        return route('public.user-avatar', ['filename' => basename($name)]);
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
