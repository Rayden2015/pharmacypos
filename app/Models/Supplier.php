<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use Auditable;

    protected $table = 'suppliers';
    protected $fillable = ['supplier_name', 'address', 'mobile', 'email'];

    public function stockReceipts()
    {
        return $this->hasMany(StockReceipt::class);
    }

    public function preferredByProducts()
    {
        return $this->hasMany(Product::class, 'preferred_supplier_id');
    }
}
