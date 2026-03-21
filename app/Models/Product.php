<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $fillable = [
        'product_name', 'alias', 'description', 'brand', 'price', 'quantity', 'product_img',
        'supplierprice', 'stock_alert', 'form', 'unit_of_measure', 'volume', 'expiredate',
    ];

    /**
     * Human-readable pack / strength line for POS, receipts, and listings.
     */
    public function getPackagingLabelAttribute(): ?string
    {
        $vol = trim((string) ($this->attributes['volume'] ?? ''));
        $uom = trim((string) ($this->attributes['unit_of_measure'] ?? ''));
        $parts = array_values(array_filter([$vol, $uom]));

        return count($parts) ? implode(' · ', $parts) : null;
    }

    public function orderdetail()
    {
        return $this->hasMany('App\Models\Order_detail');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stockReceipts()
    {
        return $this->hasMany(StockReceipt::class);
    }

}
