<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Custom domain mapping per tenant (Dreams POS “Domain” menu) — placeholder for future DNS / host binding.
 */
class DomainPlaceholderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'superadmin']);
    }

    public function __invoke(): View
    {
        return view('super-admin.domain');
    }
}
