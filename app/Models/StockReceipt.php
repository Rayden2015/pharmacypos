<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class StockReceipt extends Model
{
    use Auditable;

    protected $fillable = [
        'product_id',
        'user_id',
        'site_id',
        'quantity',
        'batch_number',
        'expiry_date',
        'supplier_id',
        'document_reference',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'expiry_date' => 'date',
        'received_at' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function inventoryMovement(): HasOne
    {
        return $this->hasOne(InventoryMovement::class);
    }

    /**
     * Short line for the stock ledger (fits movement.note limit).
     */
    public function ledgerNote(): string
    {
        $parts = [];
        if ($this->batch_number) {
            $parts[] = 'Lot: '.$this->batch_number;
        }
        if ($this->expiry_date) {
            $parts[] = 'Exp: '.$this->expiry_date->format('Y-m-d');
        }
        if ($this->relationLoaded('supplier') && $this->supplier) {
            $parts[] = 'Supplier: '.$this->supplier->supplier_name;
        } elseif ($this->supplier_id) {
            $name = Supplier::query()->whereKey($this->supplier_id)->value('supplier_name');
            if ($name) {
                $parts[] = 'Supplier: '.$name;
            }
        }
        if ($this->document_reference) {
            $parts[] = 'Ref: '.$this->document_reference;
        }

        $summary = count($parts) ? implode(' · ', $parts) : 'Stock receipt';
        if ($this->notes) {
            $summary .= ' | '.$this->notes;
        }

        return Str::limit($summary, 500);
    }
}
