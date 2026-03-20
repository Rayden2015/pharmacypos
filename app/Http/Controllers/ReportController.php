<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Order_detail;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    //
    public function periodic(Request $request)
    {
        $start_date = Carbon::parse(request()->start_date)->toDateString();
        $end_date = Carbon::parse(request()->end_date)->toDateString();
        $debt = Order_detail::join('orders','orders.id','=','order_details.order_id')->where('orders.id', '<=',0)->get();
        $total =  Order_detail::where('id', '<=',0)->sum('amount');
        if($request->start_date && $request->end_date){
            $debt = Order_detail::join('orders','orders.id','=','order_details.order_id')->whereDate('order_details.created_at', '>=', $start_date)->whereDate('order_details.created_at', '<=', $end_date)->get();
        $total =  Order_detail::join('orders','orders.id','=','order_details.order_id')->whereDate('order_details.created_at', '>=', $start_date)->whereDate('order_details.created_at', '<=', $end_date)->sum('amount');
        }
        return view('reports.index', compact('start_date', 'end_date', 'debt', 'total'));
    }

    public function periodicprint(Request $request)
    {
        $start_date = Carbon::parse(request()->start_date)->toDateString();
        $end_date = Carbon::parse(request()->end_date)->toDateString();
        $debt = Order_detail::join('orders','orders.id','=','order_details.order_id')->where('orders.id', '<=',0)->get();
        $total =  Order_detail::where('id', '<=',0)->sum('amount');
        if($request->start_date && $request->end_date){
            $debt = Order_detail::join('orders','orders.id','=','order_details.order_id')->whereDate('order_details.created_at', '>=', $start_date)->whereDate('order_details.created_at', '<=', $end_date)->get();
        $total =  Order_detail::join('orders','orders.id','=','order_details.order_id')->whereDate('order_details.created_at', '>=', $start_date)->whereDate('order_details.created_at', '<=', $end_date)->sum('amount');
        }
        return view('reports.periodic_print', compact('start_date', 'end_date', 'debt', 'total'));
    }

}
