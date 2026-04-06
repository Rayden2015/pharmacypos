<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\Site;
use App\Models\Supplier;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PagesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showusers()
    {
        return redirect()->route('pharmacy.showuser');
    }

    public function addproduct()
    {
        $viewer = Auth::user();

        $sitesQuery = Site::query()
            ->forUserTenant($viewer)
            ->where('is_active', true)
            ->orderBy('name');

        $stockSite = Site::query()
            ->whereKey(CurrentSite::id())
            ->first(['id', 'name', 'code', 'company_id']);

        return view('products.addproduct', [
            'manufacturers' => Manufacturer::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()
                ->forUserTenant($viewer)
                ->orderBy('supplier_name')
                ->get(['id', 'supplier_name']),
            'sites' => $viewer->isSuperAdmin()
                ? $sitesQuery->get(['id', 'name', 'code'])
                : collect(),
            'default_site_id' => CurrentSite::id(),
            'stockSiteHint' => $stockSite,
            'formCatalog' => config('product_form'),
        ]);
    }

    public function grid()
    {
        $products = Product::query()
            ->forTenantCatalog()
            ->with(['manufacturer', 'preferredSupplier'])
            ->paginate(5);

        return view('products.grid')->with('products', $products);
    }
}
