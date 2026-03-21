<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'code',
        'address',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function productSiteStocks(): HasMany
    {
        return $this->hasMany(ProductSiteStock::class, 'site_id');
    }

    public static function defaultId(): int
    {
        $id = static::query()->where('is_default', true)->value('id');

        return (int) ($id ?? static::query()->orderBy('id')->value('id'));
    }
}
