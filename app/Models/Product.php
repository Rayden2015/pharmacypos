<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use Auditable, HasFactory;

    protected $table = 'products';

    /**
     * Not persisted: branch where initial stock is posted (see {@see static::created}).
     */
    public ?int $initial_site_id = null;

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Product $product) {
            if ($product->company_id === null) {
                $sid = $product->initial_site_id ?? Site::defaultId();
                $site = Site::query()->find($sid);
                $product->company_id = $site?->company_id ?? Company::defaultId();
            }
        });

        static::created(function (Product $product) {
            $siteId = $product->initial_site_id ?? Site::defaultId();
            ProductSiteStock::updateOrCreate(
                ['product_id' => $product->id, 'site_id' => $siteId],
                ['quantity' => max(0, (int) $product->quantity)]
            );
            static::syncQuantityFromSiteStocks($product->id);
        });
    }

    protected $fillable = [
        'company_id',
        'product_name', 'slug', 'sku', 'item_code', 'selling_type', 'category', 'sub_category',
        'barcode_symbology', 'tax_type', 'discount_type', 'discount_value', 'product_type',
        'warranty_term', 'manufactured_date', 'warehouse_note',
        'alias', 'description', 'manufacturer_id', 'preferred_supplier_id',
        'price', 'quantity', 'product_img',
        'supplierprice', 'stock_alert', 'form', 'unit_of_measure', 'volume', 'expiredate',
    ];

    protected $casts = [
        'manufactured_date' => 'date',
        'discount_value' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Catalog is shared across all branches of a tenant; stock is per-site via {@see ProductSiteStock}.
     */
    public function scopeForTenantCatalog(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if ($user && ! $user->isSuperAdmin() && $user->company_id) {
            return $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    /**
     * Dashboard KPIs: scope products to the tenant for the active site, or the logged-in tenant for branch users.
     */
    public static function visibleForDashboard(?int $siteId = null): Builder
    {
        $q = static::query();
        if ($siteId !== null) {
            $cid = Site::query()->whereKey($siteId)->value('company_id');
            if ($cid) {
                return $q->where('company_id', (int) $cid);
            }
        }
        $u = auth()->user();
        if ($u && ! $u->isSuperAdmin() && $u->company_id) {
            return $q->where('company_id', $u->company_id);
        }

        return $q;
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
