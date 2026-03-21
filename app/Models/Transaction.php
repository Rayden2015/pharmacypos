<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use Auditable;

    protected $table = 'transactions';
    protected $fillable = ['order_id', 'paid_amount', 'balance', 'payment_method', 'user_id', 'transaction_amount', 'transaction_date'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
