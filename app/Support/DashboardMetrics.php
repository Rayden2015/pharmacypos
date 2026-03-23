<?php

namespace App\Support;

use App\Http\Controllers\DashboardController;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard KPIs scoped by CurrentSite::dashboardSiteId() (null = all sites for super admins).
 */
class DashboardMetrics
{
    public static function build(): array
    {
        $siteId = CurrentSite::dashboardSiteId();
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endToday = Carbon::now()->endOfDay();

        $odBase = Order_detail::query()
            ->when($siteId !== null, function ($q) use ($siteId) {
                $q->whereHas('order', function ($oq) use ($siteId) {
                    $oq->where('site_id', $siteId);
                });
            });

        $orderBase = Order::query()
            ->when($siteId !== null, fn ($q) => $q->where('site_id', $siteId));

        $txBase = Transaction::query()
            ->when($siteId !== null, function ($q) use ($siteId) {
                $q->whereHas('order', function ($oq) use ($siteId) {
                    $oq->where('site_id', $siteId);
                });
            });

        $rxBase = Prescription::query()
            ->when($siteId !== null, fn ($q) => $q->where('site_id', $siteId));

        $receiptsBase = StockReceipt::query()
            ->when($siteId !== null, fn ($q) => $q->where('site_id', $siteId));

        $today_sales = (float) (clone $odBase)->whereDate('created_at', $today)->sum('amount');
        $orders_today = (int) (clone $orderBase)->whereDate('created_at', $today)->count();
        $total_products = (int) Product::visibleForDashboard($siteId)->count();

        $low_stock_count = self::lowStockCount($siteId);
        $month_sales = (float) (clone $odBase)->where('created_at', '>=', $startOfMonth)->sum('amount');
        $payments_today = (float) (clone $txBase)->whereDate('transaction_date', $today)->sum('paid_amount');

        $expiring_soon_count = self::expiringSoonCount($siteId, $today);
        $inventory_retail_value = self::inventoryRetailValue($siteId);
        $avg_order_value_today = $orders_today > 0
            ? $today_sales / $orders_today
            : 0.0;

        $first_low_stock = self::firstLowStock($siteId);
        $low_stock_table = self::lowStockTable($siteId);

        $total_sales_return = 0.0;
        $total_purchase_return = 0.0;

        $purchase_mtd = self::purchaseSumBetween(
            $siteId,
            $startOfMonth->toDateString(),
            Carbon::now()->toDateString()
        );

        $lastMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonthNoOverflow()->endOfMonth();
        $last_month_sales = (float) (clone $odBase)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->sum('amount');
        $last_month_purchase = self::purchaseSumBetween(
            $siteId,
            $lastMonthStart->toDateString(),
            $lastMonthEnd->toDateString()
        );

        $rolling30Start = Carbon::today()->subDays(29)->startOfDay();
        $prev30Start = Carbon::today()->subDays(59)->startOfDay();
        $prev30End = Carbon::today()->subDays(30)->endOfDay();
        $sales_last_30 = (float) (clone $odBase)->whereBetween('created_at', [$rolling30Start, $endToday])->sum('amount');
        $sales_prev_30 = (float) (clone $odBase)->whereBetween('created_at', [$prev30Start, $prev30End])->sum('amount');
        $purchase_last_30 = self::purchaseSumBetween(
            $siteId,
            $rolling30Start->toDateString(),
            $today->toDateString()
        );
        $purchase_prev_30 = self::purchaseSumBetween(
            $siteId,
            $prev30Start->toDateString(),
            $prev30End->toDateString()
        );

        $sales_30d_pct = DashboardController::percentChange($sales_last_30, $sales_prev_30);
        $purchase_30d_pct = DashboardController::percentChange($purchase_last_30, $purchase_prev_30);
        $sales_mom_pct = DashboardController::percentChange($month_sales, $last_month_sales);
        $purchase_mom_pct = DashboardController::percentChange($purchase_mtd, $last_month_purchase);

        $stock_out_count = self::stockOutCount($siteId);
        $stock_low_only_count = self::stockLowOnlyCount($siteId);
        $stock_available_count = self::stockAvailableCount($siteId);
        $stock_low_count = $stock_low_only_count;

        $expired_count = self::expiredCount($siteId, $today);
        $expired_products = self::expiredProducts($siteId, $today);

        $near_expiry_labels = [];
        $near_expiry_counts = [];
        for ($m = 0; $m < 6; $m++) {
            $monthStart = Carbon::today()->copy()->addMonthsNoOverflow($m)->startOfMonth();
            $monthEnd = Carbon::today()->copy()->addMonthsNoOverflow($m)->endOfMonth();
            if ($monthStart->lt($today)) {
                $monthStart = $today->copy();
            }
            $near_expiry_labels[] = $monthStart->format('M Y');
            $near_expiry_counts[] = self::nearExpiryCountInRange($siteId, $monthStart, $monthEnd, $today);
        }

        $inventory_by_form = self::inventoryByForm($siteId);

        $payment_methods = (clone $txBase)
            ->select('payment_method', DB::raw('SUM(paid_amount) as total'))
            ->whereYear('transaction_date', $today->year)
            ->whereMonth('transaction_date', $today->month)
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();
        $payment_total_month = (float) $payment_methods->sum('total');
        $payment_methods_pct = $payment_methods->map(function ($row) use ($payment_total_month) {
            $t = (float) $row->total;
            $row->pct = $payment_total_month > 0 ? round(($t / $payment_total_month) * 100, 1) : 0.0;

            return $row;
        });

        $month_tx_paid = (float) (clone $txBase)
            ->where('transaction_date', '>=', $startOfMonth->toDateString())
            ->sum('paid_amount');
        $invoice_due = (float) (clone $txBase)
            ->where('transaction_date', '>=', $startOfMonth->toDateString())
            ->where('balance', '>', 0)
            ->sum('balance');
        $ar_open_total = (float) (clone $txBase)->where('balance', '>', 0)->sum('balance');

        $orders_mtd_count = (int) (clone $orderBase)->where('created_at', '>=', $startOfMonth)->count();
        $transactions_paid_mtd_count = (int) (clone $txBase)
            ->where('transaction_date', '>=', $startOfMonth->toDateString())
            ->where('paid_amount', '>', 0)
            ->count();
        $transactions_with_balance_mtd = (int) (clone $txBase)
            ->where('transaction_date', '>=', $startOfMonth->toDateString())
            ->where('balance', '>', 0)
            ->count();

        $prescriptions_last_30 = (int) (clone $rxBase)->where('created_at', '>=', $rolling30Start)->count();
        $rx_completed = (int) (clone $rxBase)->where('status', 'completed')->count();
        $rx_pending = (int) (clone $rxBase)->where('status', 'pending')->count();
        $rx_cancelled = (int) (clone $rxBase)->where('status', 'cancelled')->count();

        $weekly_sales_labels = [];
        $weekly_sales_values = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $weekly_sales_labels[] = $d->format('D');
            $weekly_sales_values[] = round((float) (clone $odBase)->whereDate('created_at', $d)->sum('amount'), 2);
        }

        $recent_orders = (clone $orderBase)->latest()->limit(8)->get();
        foreach ($recent_orders as $o) {
            $o->order_total = (float) Order_detail::where('order_id', $o->id)->sum('amount');
            $tx = Transaction::query()->where('order_id', $o->id)->first();
            $o->payment_label = $tx->payment_method ?? '—';
        }

        $recent_receipts = (clone $receiptsBase)
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
            $chart_sales[] = round((float) (clone $odBase)->whereDate('created_at', $d)->sum('amount'), 2);
            $p = self::purchaseSumForDate($siteId, $d);
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
            'last_month_sales',
            'last_month_purchase',
            'sales_mom_pct',
            'purchase_mom_pct',
            'sales_30d_pct',
            'purchase_30d_pct',
            'sales_last_30',
            'purchase_last_30',
            'stock_out_count',
            'stock_available_count',
            'stock_low_count',
            'stock_low_only_count',
            'expired_count',
            'expired_products',
            'near_expiry_labels',
            'near_expiry_counts',
            'inventory_by_form',
            'payment_methods_pct',
            'payment_total_month',
            'month_tx_paid',
            'invoice_due',
            'ar_open_total',
            'orders_mtd_count',
            'transactions_paid_mtd_count',
            'transactions_with_balance_mtd',
            'prescriptions_last_30',
            'rx_completed',
            'rx_pending',
            'rx_cancelled',
            'weekly_sales_labels',
            'weekly_sales_values',
        );
    }

    private static function purchaseQuery(?int $siteId)
    {
        $q = DB::table('stock_receipts')
            ->join('products', 'products.id', '=', 'stock_receipts.product_id');

        if ($siteId !== null) {
            $q->where('stock_receipts.site_id', $siteId);
        }

        return $q;
    }

    private static function purchaseSumBetween(?int $siteId, string $from, string $to): float
    {
        return (float) (self::purchaseQuery($siteId))
            ->whereBetween('stock_receipts.received_at', [$from, $to])
            ->sum(DB::raw('stock_receipts.quantity * COALESCE(products.supplierprice, 0)'));
    }

    private static function purchaseSumForDate(?int $siteId, Carbon $d): float
    {
        return (float) (self::purchaseQuery($siteId))
            ->whereDate('stock_receipts.received_at', $d)
            ->sum(DB::raw('stock_receipts.quantity * COALESCE(products.supplierprice, 0)'));
    }

    private static function inventoryRetailValue(?int $siteId): float
    {
        if ($siteId === null) {
            return (float) (Product::visibleForDashboard(null)
                ->selectRaw('COALESCE(SUM(COALESCE(quantity, 0) * COALESCE(price, 0)), 0) as v')
                ->value('v') ?? 0);
        }

        return (float) DB::table('product_site_stock')
            ->join('products', 'products.id', '=', 'product_site_stock.product_id')
            ->where('product_site_stock.site_id', $siteId)
            ->sum(DB::raw('product_site_stock.quantity * COALESCE(products.price, 0)'));
    }

    private static function lowStockCount(?int $siteId): int
    {
        if ($siteId === null) {
            return (int) Product::visibleForDashboard(null)
                ->whereNotNull('stock_alert')
                ->whereColumn('quantity', '<=', 'stock_alert')
                ->count();
        }

        return (int) DB::table('products')
            ->join('product_site_stock as pss', 'products.id', '=', 'pss.product_id')
            ->where('pss.site_id', $siteId)
            ->whereNotNull('products.stock_alert')
            ->whereColumn('pss.quantity', '<=', 'products.stock_alert')
            ->count();
    }

    private static function firstLowStock(?int $siteId): ?Product
    {
        if ($siteId === null) {
            return Product::visibleForDashboard($siteId)
                ->whereNotNull('stock_alert')
                ->whereColumn('quantity', '<=', 'stock_alert')
                ->orderBy('quantity')
                ->first(['id', 'product_name', 'quantity', 'stock_alert']);
        }

        return Product::visibleForDashboard($siteId)
            ->join('product_site_stock as pss', 'products.id', '=', 'pss.product_id')
            ->where('pss.site_id', $siteId)
            ->whereNotNull('products.stock_alert')
            ->whereColumn('pss.quantity', '<=', 'products.stock_alert')
            ->orderBy('pss.quantity')
            ->select('products.id', 'products.product_name', 'pss.quantity as quantity', 'products.stock_alert')
            ->first();
    }

    private static function lowStockTable(?int $siteId)
    {
        if ($siteId === null) {
            return Product::visibleForDashboard($siteId)
                ->whereNotNull('stock_alert')
                ->whereColumn('quantity', '<=', 'stock_alert')
                ->orderBy('quantity')
                ->limit(10)
                ->get(['id', 'product_name', 'alias', 'quantity', 'stock_alert']);
        }

        return Product::visibleForDashboard($siteId)
            ->join('product_site_stock as pss', 'products.id', '=', 'pss.product_id')
            ->where('pss.site_id', $siteId)
            ->whereNotNull('products.stock_alert')
            ->whereColumn('pss.quantity', '<=', 'products.stock_alert')
            ->orderBy('pss.quantity')
            ->limit(10)
            ->select(
                'products.id',
                'products.product_name',
                'products.alias',
                'pss.quantity as quantity',
                'products.stock_alert'
            )
            ->get();
    }

    private static function stockOutCount(?int $siteId): int
    {
        if ($siteId === null) {
            return (int) Product::visibleForDashboard($siteId)->where('quantity', '<=', 0)->count();
        }

        return (int) DB::table('product_site_stock')
            ->where('site_id', $siteId)
            ->where('quantity', '<=', 0)
            ->count();
    }

    private static function stockLowOnlyCount(?int $siteId): int
    {
        if ($siteId === null) {
            return (int) Product::visibleForDashboard($siteId)
                ->whereNotNull('stock_alert')
                ->where('quantity', '>', 0)
                ->whereColumn('quantity', '<=', 'stock_alert')
                ->count();
        }

        return (int) DB::table('products')
            ->join('product_site_stock as pss', 'products.id', '=', 'pss.product_id')
            ->where('pss.site_id', $siteId)
            ->whereNotNull('products.stock_alert')
            ->where('pss.quantity', '>', 0)
            ->whereColumn('pss.quantity', '<=', 'products.stock_alert')
            ->count();
    }

    private static function stockAvailableCount(?int $siteId): int
    {
        if ($siteId === null) {
            return (int) Product::visibleForDashboard($siteId)
                ->where('quantity', '>', 0)
                ->where(function ($q) {
                    $q->whereColumn('quantity', '>', 'stock_alert')
                        ->orWhereNull('stock_alert');
                })
                ->count();
        }

        return (int) DB::table('products')
            ->join('product_site_stock as pss', 'products.id', '=', 'pss.product_id')
            ->where('pss.site_id', $siteId)
            ->where('pss.quantity', '>', 0)
            ->where(function ($q) {
                $q->whereColumn('pss.quantity', '>', 'products.stock_alert')
                    ->orWhereNull('products.stock_alert');
            })
            ->count();
    }

    private static function expiringSoonCount(?int $siteId, Carbon $today): int
    {
        $q = Product::visibleForDashboard($siteId)
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '>', $today)
            ->whereDate('expiredate', '<=', $today->copy()->addDays(90));

        if ($siteId !== null) {
            $q->whereHas('siteStocks', function ($sq) use ($siteId) {
                $sq->where('site_id', $siteId)->where('quantity', '>', 0);
            });
        }

        return (int) $q->count();
    }

    private static function expiredCount(?int $siteId, Carbon $today): int
    {
        $q = Product::visibleForDashboard($siteId)
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '<', $today);

        if ($siteId !== null) {
            $q->whereHas('siteStocks', function ($sq) use ($siteId) {
                $sq->where('site_id', $siteId)->where('quantity', '>', 0);
            });
        }

        return (int) $q->count();
    }

    private static function expiredProducts(?int $siteId, Carbon $today)
    {
        $q = Product::visibleForDashboard($siteId)
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '<', $today)
            ->orderBy('expiredate', 'desc')
            ->limit(6);

        if ($siteId !== null) {
            $q->whereHas('siteStocks', function ($sq) use ($siteId) {
                $sq->where('site_id', $siteId)->where('quantity', '>', 0);
            });
        }

        return $q->get(['id', 'product_name', 'quantity', 'expiredate']);
    }

    private static function nearExpiryCountInRange(?int $siteId, Carbon $monthStart, Carbon $monthEnd, Carbon $today): int
    {
        $q = Product::visibleForDashboard($siteId)
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '>=', $monthStart->toDateString())
            ->whereDate('expiredate', '<=', $monthEnd->toDateString());

        if ($siteId !== null) {
            $q->whereHas('siteStocks', function ($sq) use ($siteId) {
                $sq->where('site_id', $siteId)->where('quantity', '>', 0);
            });
        }

        return (int) $q->count();
    }

    private static function inventoryByForm(?int $siteId)
    {
        if ($siteId === null) {
            return Product::visibleForDashboard($siteId)
                ->select('form', DB::raw('COUNT(*) as c'))
                ->whereNotNull('form')
                ->where('form', '!=', '')
                ->groupBy('form')
                ->orderByDesc('c')
                ->limit(8)
                ->get();
        }

        return Product::visibleForDashboard($siteId)
            ->join('product_site_stock as pss', 'products.id', '=', 'pss.product_id')
            ->where('pss.site_id', $siteId)
            ->where('pss.quantity', '>', 0)
            ->select('products.form', DB::raw('COUNT(*) as c'))
            ->whereNotNull('products.form')
            ->where('products.form', '!=', '')
            ->groupBy('products.form')
            ->orderByDesc('c')
            ->limit(8)
            ->get();
    }
}
