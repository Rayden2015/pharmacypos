<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'supplier_id',
        'user_id',
        'reference',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function outstandingAmount(): string
    {
        $t = (float) $this->total_amount;
        $p = (float) $this->paid_amount;

        return number_format(max(0, $t - $p), 2, '.', '');
    }

    /**
     * paid | partially_paid | overdue | pending
     */
    public function computedStatus(): string
    {
        $outstanding = (float) $this->total_amount - (float) $this->paid_amount;
        if ($outstanding <= 0.00001) {
            return 'paid';
        }
        $due = $this->due_date;
        if ($due instanceof Carbon && $due->copy()->startOfDay()->lt(now()->copy()->startOfDay())) {
            return 'overdue';
        }
        if ((float) $this->paid_amount > 0) {
            return 'partially_paid';
        }

        return 'pending';
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
