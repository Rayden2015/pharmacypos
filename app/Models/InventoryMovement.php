<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InventoryMovement extends Model
{
    use Auditable;

    protected $fillable = [
        'product_id',
        'site_id',
        'user_id',
        'quantity_before',
        'quantity_delta',
        'quantity_after',
        'change_type',
        'note',
        'stock_receipt_id',
        'stock_transfer_id',
        'sale_return_id',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_delta' => 'integer',
        'quantity_after' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockReceipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    /**
     * Label aligned with pharmacy inventory ledger / POS dashboards (Purchase, Sales, Transfer, …).
     */
    public function transactionTypeLabel(): string
    {
        return match ($this->change_type) {
            'receipt' => 'Purchase',
            'sale' => 'Sales',
            'sale_return' => 'Return',
            'adjustment' => 'Adjustment',
            'transfer_in', 'transfer_out' => 'Transfer',
            'initial' => 'Opening balance',
            default => Str::title(str_replace('_', ' ', (string) $this->change_type)),
        };
    }

    public function quantityInDisplay(): int
    {
        return max(0, (int) $this->quantity_delta);
    }

    public function quantityOutDisplay(): int
    {
        return max(0, -(int) $this->quantity_delta);
    }

    /**
     * Batch / lot: from linked receipt when present; otherwise parses movement note (e.g. "Lot: …").
     */
    public function batchDisplay(): string
    {
        if ($this->relationLoaded('stockReceipt') && $this->stockReceipt?->batch_number) {
            return (string) $this->stockReceipt->batch_number;
        }
        if ($this->note && preg_match('/Lot:\s*([^·|]+)/u', (string) $this->note, $m)) {
            return trim($m[1]);
        }

        return '—';
    }

    /**
     * Human-readable reference like #PUR016, #SAL007 (matches template-style ledgers).
     */
    public function referenceDisplay(): string
    {
        $pad = static fn (int $id): string => str_pad((string) $id, 3, '0', STR_PAD_LEFT);

        return match ($this->change_type) {
            'receipt' => '#PUR'.$pad($this->stock_receipt_id ? (int) $this->stock_receipt_id : (int) $this->id),
            'sale' => '#SAL'.$pad($this->parsedPosOrderId() ?? (int) $this->id),
            'sale_return' => '#RTN'.$pad($this->sale_return_id ? (int) $this->sale_return_id : (int) $this->id),
            'adjustment' => '#ADJ'.$pad((int) $this->id),
            'transfer_in', 'transfer_out' => '#TRA'.$pad($this->stock_transfer_id ? (int) $this->stock_transfer_id : (int) $this->id),
            'initial' => '#OB'.$pad((int) $this->id),
            default => '#MOV'.$pad((int) $this->id),
        };
    }

    private function parsedPosOrderId(): ?int
    {
        if (! $this->note) {
            return null;
        }
        if (preg_match('/POS order #(\d+)/i', (string) $this->note, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
