<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPackage extends Model
{
    protected $fillable = [
        'name',
        'billing_cycle',
        'price',
        'billing_days',
        'subscriber_count',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subscriber_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenantSubscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'subscription_package_id');
    }

    public function displayLabel(): string
    {
        return $this->name.' ('.ucfirst($this->billing_cycle).')';
    }
}
