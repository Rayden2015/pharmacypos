<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use Auditable;

    protected $table = 'orders';
   

    protected $fillable = ['name', 'mobile', 'site_id'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function orderdetail()
    {
        return $this->hasMany(Order_detail::class, 'order_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'order_id');
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class, 'order_id');
    }

    public function product(){
        return $this->belongsTo('App\Models\Product');
    }
}
