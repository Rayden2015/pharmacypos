<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Order_detail;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Default date range: today only, until the user picks other dates.
     */
    public function periodic(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $start_date = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->toDateString()
            : $today;
        $end_date = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->toDateString()
            : $today;

        $debt = Order_detail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereDate('order_details.created_at', '>=', $start_date)
            ->whereDate('order_details.created_at', '<=', $end_date)
            ->select('order_details.*')
            ->with('product')
            ->get();

        $total = (float) Order_detail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereDate('order_details.created_at', '>=', $start_date)
            ->whereDate('order_details.created_at', '<=', $end_date)
            ->sum('order_details.amount');

        return view('reports.index', compact('start_date', 'end_date', 'debt', 'total'));
    }

    public function periodicprint(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $start_date = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->toDateString()
            : $today;
        $end_date = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->toDateString()
            : $today;

        $debt = Order_detail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereDate('order_details.created_at', '>=', $start_date)
            ->whereDate('order_details.created_at', '<=', $end_date)
            ->select('order_details.*')
            ->with('product')
            ->get();

        $total = (float) Order_detail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereDate('order_details.created_at', '>=', $start_date)
            ->whereDate('order_details.created_at', '<=', $end_date)
            ->sum('order_details.amount');

        return view('reports.periodic_print', compact('start_date', 'end_date', 'debt', 'total'));
    }

}
