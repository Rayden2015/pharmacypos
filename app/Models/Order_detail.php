<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order_detail extends Model
{
    use Auditable;

    protected $table = 'order_details';
    protected $fillable = [
        'order_id', 'product_id', 'quantity', 'unitprice', 'amount', 'discount',
        'unit_of_measure', 'volume',
    ];

    public function getPackagingLabelAttribute(): ?string
    {
        $vol = trim((string) ($this->attributes['volume'] ?? ''));
        $uom = trim((string) ($this->attributes['unit_of_measure'] ?? ''));
        $parts = array_values(array_filter([$vol, $uom]));

        return count($parts) ? implode(' · ', $parts) : null;
    }

    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');
    }

    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

    public function saleReturnLines()
    {
        return $this->hasMany(SaleReturnLine::class, 'order_detail_id');
    }

    /**
     * Units already put back to stock for this invoice line.
     */
    public function quantityReturned(): int
    {
        return (int) $this->saleReturnLines()->sum('quantity');
    }

    public function quantityReturnable(): int
    {
        return max(0, (int) $this->quantity - $this->quantityReturned());
    }
}
