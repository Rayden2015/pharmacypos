<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Auditable;

    protected $table = 'companies';
    protected $fillable = ['company_name', 'company_email', 'company_mobile', 'company_address'];
}
