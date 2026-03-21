<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacturer extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
