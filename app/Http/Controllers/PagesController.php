<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;

use App\Models\Order;
use App\Models\Product;
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

    public function showusers(){
        $user = User::paginate(5);
        return view('users.showuser', ['users' => $user]);
    }

    public function addproduct(){
        
        return view('products.addproduct');
    }

    public function grid(){
        $product = Product::paginate(5);

        return view('products.grid')->with('products', $product);
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