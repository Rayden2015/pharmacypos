<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;

use App\Models\Manufacturer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;

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
        return view('products.addproduct', [
            'manufacturers' => Manufacturer::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(['id', 'supplier_name']),
        ]);
    }

    public function grid()
    {
        $products = Product::with(['manufacturer', 'preferredSupplier'])->paginate(5);

        return view('products.grid')->with('products', $products);
    }

    public function report(Request $request){
        $start_date = Carbon::parse(request()->start_date)->toDateString();
        $end_date = Carbon::parse(request()->end_date)->toDateString();
        $debt = Order::where('id', '<=',0)->get();
        // $total =  Payment::where('id', '<=',0)->sum('amt_paid');
        if($request->start_date && $request->end_date){
            $debt = Order::join('order_details','orders.id','=','order_details.order_id')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->get();
        $total =  Order::join('order_details','orders.id','=','order_details.order_id')->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)->sum('amt_paid');
        }
        return view('reports.index', compact('start_date', 'end_date', 'debt', 'total'));
}
}