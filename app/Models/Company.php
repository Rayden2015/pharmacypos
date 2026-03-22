<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use Auditable;

    protected $table = 'companies';

    protected $fillable = [
        'company_name',
        'company_email',
        'company_mobile',
        'company_address',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tenantSubscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Primary tenant record for bootstrapping (single-tenant installs and tests).
     */
    public static function defaultId(): int
    {
        $id = (int) static::query()->orderBy('id')->value('id');
        if ($id > 0) {
            return $id;
        }

        $c = static::query()->create([
            'company_name' => 'Default Pharmacy',
            'company_email' => 'tenant@example.test',
            'company_mobile' => '',
            'company_address' => '',
            'slug' => 'default-pharmacy',
            'is_active' => true,
        ]);

        return (int) $c->id;
    }
}
