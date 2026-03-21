<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $first_low_stock = Product::query()
            ->whereNotNull('stock_alert')
            ->whereColumn('quantity', '<=', 'stock_alert')
            ->orderBy('quantity')
            ->first(['id', 'product_name', 'quantity', 'stock_alert']);

        $low_stock_table = Product::query()
            ->whereNotNull('stock_alert')
            ->whereColumn('quantity', '<=', 'stock_alert')
            ->orderBy('quantity')
            ->limit(10)
            ->get(['id', 'product_name', 'alias', 'quantity', 'stock_alert']);

        $total_sales_return = 0.0;
        $total_purchase_return = 0.0;

        $purchase_mtd = (float) DB::table('stock_receipts')
            ->join('products', 'products.id', '=', 'stock_receipts.product_id')
            ->where('stock_receipts.received_at', '>=', $startOfMonth)
            ->sum(DB::raw('stock_receipts.quantity * COALESCE(products.supplierprice, 0)'));

        $recent_orders = Order::query()->latest()->limit(8)->get();
        foreach ($recent_orders as $o) {
            $o->order_total = (float) Order_detail::where('order_id', $o->id)->sum('amount');
        }

        $recent_receipts = StockReceipt::query()
            ->with(['product:id,product_name,supplierprice', 'supplier:id,supplier_name'])
            ->latest('received_at')
            ->latest('id')
            ->limit(8)
            ->get();

        foreach ($recent_receipts as $r) {
            $r->line_value = $r->quantity * (float) ($r->product->supplierprice ?? 0);
        }

        $chart_labels = [];
        $chart_sales = [];
        $chart_purchases = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $chart_labels[] = $d->format('M j');
            $chart_sales[] = round((float) Order_detail::whereDate('created_at', $d)->sum('amount'), 2);
            $p = (float) DB::table('stock_receipts')
                ->join('products', 'products.id', '=', 'stock_receipts.product_id')
                ->whereDate('stock_receipts.received_at', $d)
                ->sum(DB::raw('stock_receipts.quantity * COALESCE(products.supplierprice, 0)'));
            $chart_purchases[] = round($p, 2);
        }

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
            'first_low_stock',
            'low_stock_table',
            'total_sales_return',
            'total_purchase_return',
            'purchase_mtd',
            'recent_orders',
            'recent_receipts',
            'chart_labels',
            'chart_sales',
            'chart_purchases',
        );
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('dashboard', array_merge(self::dashboardViewData(), [
            'welcome_name' => auth()->user()->name ?? 'Admin',
        ]));
    }
}
