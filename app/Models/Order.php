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
        return $this->hasMany('App\Models\Order_detail');
    }
    public function product(){
        return $this->belongsTo('App\Models\Product');
    }
}
