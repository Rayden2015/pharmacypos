<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\Order_detail;
use App\Http\Controllers\Controller;

class HomeController extends Controller
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

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $today_sales = Order_detail::whereDate('created_at','=',Carbon::today())->sum('amount');
        $total_products = Product::count();
        return view('dashboard', compact('today_sales','total_products'));
}
}