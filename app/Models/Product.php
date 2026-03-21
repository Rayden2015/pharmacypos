<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use Auditable, HasFactory;

    protected $table = 'products';

    protected static function boot()
    {
        parent::boot();

        static::created(function (Product $product) {
            ProductSiteStock::firstOrCreate(
                ['product_id' => $product->id, 'site_id' => Site::defaultId()],
                ['quantity' => max(0, (int) $product->quantity)]
            );
        });
    }

    protected $fillable = [
        'product_name', 'alias', 'description', 'manufacturer_id', 'preferred_supplier_id',
        'price', 'quantity', 'product_img',
        'supplierprice', 'stock_alert', 'form', 'unit_of_measure', 'volume', 'expiredate',
    ];

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

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

    public function siteStocks()
    {
        return $this->hasMany(ProductSiteStock::class, 'product_id');
    }

    public static function syncQuantityFromSiteStocks(int $productId): void
    {
        $total = (int) ProductSiteStock::query()->where('product_id', $productId)->sum('quantity');
        static::query()->whereKey($productId)->update(['quantity' => $total]);
    }
}
