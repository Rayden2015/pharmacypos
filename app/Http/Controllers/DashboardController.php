<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;

class DashboardController extends Controller
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
     * Metrics for the POS dashboard view (shared with /home).
     *
     * @return array<string, float|int>
     */
    public static function dashboardViewData(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $today_sales = (float) Order_detail::whereDate('created_at', $today)->sum('amount');
        $orders_today = Order::whereDate('created_at', $today)->count();
        $total_products = Product::count();
        $low_stock_count = Product::query()
            ->whereNotNull('stock_alert')
            ->whereColumn('quantity', '<=', 'stock_alert')
            ->count();
        $month_sales = (float) Order_detail::where('created_at', '>=', $startOfMonth)->sum('amount');
        $payments_today = (float) Transaction::whereDate('transaction_date', $today)->sum('paid_amount');
        $expiring_soon_count = Product::query()
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '>', $today)
            ->whereDate('expiredate', '<=', $today->copy()->addDays(90))
            ->count();
        $inventory_retail_value = (float) (Product::query()
            ->selectRaw('COALESCE(SUM(COALESCE(quantity, 0) * COALESCE(price, 0)), 0) as v')
            ->value('v') ?? 0);
        $avg_order_value_today = $orders_today > 0
            ? $today_sales / $orders_today
            : 0.0;

        return compact(
            'today_sales',
            'orders_today',
            'total_products',
            'low_stock_count',
            'month_sales',
            'payments_today',
            'expiring_soon_count',
            'inventory_retail_value',
            'avg_order_value_today',
        );
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('dashboard', self::dashboardViewData());
    }
}
