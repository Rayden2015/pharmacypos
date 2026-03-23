<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Site;
use Carbon\Carbon;
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

    /**
     * Invoice-style sales list: order #, date, branch, customer, discount %, total, payment status.
     */
    public function sales(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->toDateString()
            : $today;
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->toDateString()
            : $today;

        $viewer = $request->user();
        $siteFilter = $request->filled('site_id') ? (int) $request->input('site_id') : null;

        $ordersQuery = Order::query()
            ->with(['site:id,name,code', 'transaction', 'orderdetail'])
            ->whereDate('orders.created_at', '>=', $startDate)
            ->whereDate('orders.created_at', '<=', $endDate)
            ->orderByDesc('orders.id');

        if ($viewer->isSuperAdmin()) {
            if ($siteFilter) {
                $ordersQuery->where('orders.site_id', $siteFilter);
            }
        } else {
            $companyId = (int) ($viewer->company_id ?? 0);
            if ($companyId > 0) {
                $ordersQuery->whereIn(
                    'orders.site_id',
                    Site::query()->where('company_id', $companyId)->select('id')
                );
            }
            if ($siteFilter) {
                $ordersQuery->where('orders.site_id', $siteFilter);
            }
        }

        $orders = $ordersQuery->paginate(25)->withQueryString();

        $orders->getCollection()->transform(function (Order $order) {
            $details = $order->orderdetail;
            $gross = (float) $details->sum(function (Order_detail $l) {
                return (float) $l->quantity * (float) $l->unitprice;
            });
            $net = (float) $details->sum('amount');
            $discPct = $gross > 0.0001 ? round((1 - $net / $gross) * 100, 1) : 0.0;
            $order->setAttribute('sales_gross', $gross);
            $order->setAttribute('sales_net', $net);
            $order->setAttribute('sales_disc_pct', $discPct);
            $order->setAttribute('sales_payment_status', $this->paymentStatusLabel($order, $net));

            return $order;
        });

        $sites = $viewer->isSuperAdmin()
            ? Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])
            : Site::query()->forUserTenant($viewer)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('reports.sales', compact(
            'orders',
            'sites',
            'startDate',
            'endDate',
            'siteFilter'
        ));
    }

    private function paymentStatusLabel(Order $order, float $net): string
    {
        $t = $order->transaction;
        if (! $t) {
            return '—';
        }
        $paid = (float) $t->paid_amount;
        if ($paid + 0.5 >= $net) {
            return 'paid';
        }
        if ($paid <= 0.01) {
            return 'pending';
        }

        return 'partial';
    }
}
