<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Site;
use App\Support\ReportAuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $debt = $this->periodicOrderLinesBase($request, $start_date, $end_date)
            ->select('order_details.*')
            ->with('product')
            ->get();

        $total = (float) $this->periodicOrderLinesBase($request, $start_date, $end_date)
            ->sum('order_details.amount');

        ReportAuditLogger::log($request, 'periodic.index', [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);

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

        $debt = $this->periodicOrderLinesBase($request, $start_date, $end_date)
            ->select('order_details.*')
            ->with('product')
            ->get();

        $total = (float) $this->periodicOrderLinesBase($request, $start_date, $end_date)
            ->sum('order_details.amount');

        ReportAuditLogger::log($request, 'periodic.print', [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ]);

        return view('reports.periodic_print', compact('start_date', 'end_date', 'debt', 'total'));
    }

    /**
     * Invoice-style sales list: order #, date, branch, customer, discount %, total, payment status.
     */
    public function sales(Request $request)
    {
        [$startDate, $endDate] = $this->resolveSalesDateRange($request);
        $viewer = $request->user();
        $siteFilter = $request->filled('site_id') ? (int) $request->input('site_id') : null;

        $orders = $this->salesOrdersQuery($request, $startDate, $endDate)
            ->paginate(25)
            ->withQueryString();

        $orders->getCollection()->transform(function (Order $order) {
            return $this->applySalesMetrics($order);
        });

        $sites = $viewer->isSuperAdmin()
            ? Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])
            : Site::query()->forUserTenant($viewer)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $salesKpis = $this->buildSalesKpis($request, $startDate, $endDate);

        ReportAuditLogger::log($request, 'sales.view', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'site_id' => $siteFilter,
            'q' => $request->filled('q') ? $request->input('q') : null,
        ]);

        return view('reports.sales', compact(
            'orders',
            'sites',
            'startDate',
            'endDate',
            'siteFilter',
            'salesKpis'
        ));
    }

    /**
     * Same filters as the on-screen report; UTF-8 CSV for Excel / sheets.
     */
    public function salesExport(Request $request): StreamedResponse
    {
        [$startDate, $endDate] = $this->resolveSalesDateRange($request);
        $siteFilter = $request->filled('site_id') ? (int) $request->input('site_id') : null;

        ReportAuditLogger::log($request, 'sales.export', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'site_id' => $siteFilter,
            'q' => $request->filled('q') ? $request->input('q') : null,
        ]);

        $filename = sprintf(
            'sales-report-%s-to-%s.csv',
            preg_replace('/[^0-9-]/', '', $startDate),
            preg_replace('/[^0-9-]/', '', $endDate)
        );

        return response()->streamDownload(function () use ($request, $startDate, $endDate) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, [
                'Invoice no.',
                'Date',
                'Branch',
                'Customer',
                'Mobile',
                'Sales amount',
                'Disc. %',
                'Net revenue',
                'Payment method',
                'Paid',
                'Status',
            ]);

            $this->salesOrdersQuery($request, $startDate, $endDate)
                ->chunk(500, function ($orders) use ($out) {
                    foreach ($orders as $order) {
                        $this->applySalesMetrics($order);
                        $status = $order->sales_payment_status;
                        $statusLabel = $this->salesPaymentStatusCsvLabel($status);
                        $siteName = $order->site ? $order->site->name : '';
                        $payMethod = $order->transaction ? $order->transaction->payment_method : '';
                        $paidAmt = $order->transaction ? (float) $order->transaction->paid_amount : 0.0;
                        fputcsv($out, [
                            '#ORD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                            $order->created_at->format('Y-m-d H:i:s'),
                            $siteName,
                            $order->name ?? '',
                            $order->mobile ?? '',
                            number_format((float) $order->sales_gross, 2, '.', ''),
                            number_format((float) $order->sales_disc_pct, 1, '.', ''),
                            number_format((float) $order->sales_net, 2, '.', ''),
                            $payMethod,
                            number_format($paidAmt, 2, '.', ''),
                            $statusLabel,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Printable list (all rows in range, no pagination).
     */
    public function salesPrint(Request $request)
    {
        [$startDate, $endDate] = $this->resolveSalesDateRange($request);
        $siteFilter = $request->filled('site_id') ? (int) $request->input('site_id') : null;

        $orders = $this->salesOrdersQuery($request, $startDate, $endDate)->get();

        foreach ($orders as $order) {
            $this->applySalesMetrics($order);
        }

        $branchLabel = null;
        if ($siteFilter) {
            $branchLabel = Site::query()->whereKey($siteFilter)->value('name');
        }

        $salesKpis = $this->buildSalesKpis($request, $startDate, $endDate);
        $totalNet = $salesKpis['net'];

        ReportAuditLogger::log($request, 'sales.print', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'site_id' => $siteFilter,
            'q' => $request->filled('q') ? $request->input('q') : null,
        ]);

        return view('reports.sales_print', compact(
            'orders',
            'startDate',
            'endDate',
            'siteFilter',
            'branchLabel',
            'totalNet',
            'salesKpis'
        ));
    }

    /**
     * Today's sales-style report lines: tenant staff only see their organization's branches.
     */
    private function periodicOrderLinesBase(Request $request, string $startDate, string $endDate): Builder
    {
        $q = Order_detail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->whereDate('order_details.created_at', '>=', $startDate)
            ->whereDate('order_details.created_at', '<=', $endDate);

        $viewer = $request->user();
        if ($viewer && ! $viewer->isSuperAdmin()) {
            $companyId = (int) ($viewer->company_id ?? 0);
            if ($companyId > 0) {
                $q->whereIn(
                    'orders.site_id',
                    Site::query()->where('company_id', $companyId)->select('id')
                );
            }
        }

        return $q;
    }

    private function resolveSalesDateRange(Request $request): array
    {
        $today = Carbon::today()->toDateString();
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->toDateString()
            : $today;
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->toDateString()
            : $today;

        return [$startDate, $endDate];
    }

    private function salesOrdersQuery(Request $request, string $startDate, string $endDate): Builder
    {
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

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            if ($term !== '') {
                $this->applySalesSearchFilter($ordersQuery, $term);
            }
        }

        return $ordersQuery;
    }

    /**
     * @return array{
     *   invoice_count: int,
     *   gross: float,
     *   net: float,
     *   deductions: float,
     *   prev_invoice_count: int,
     *   prev_gross: float,
     *   prev_net: float,
     *   prev_deductions: float,
     *   pct_invoices: float|null,
     *   pct_gross: float|null,
     *   pct_net: float|null,
     *   pct_deductions: float|null
     * }
     */
    private function buildSalesKpis(Request $request, string $startDate, string $endDate): array
    {
        $curr = $this->salesRangeTotals($request, $startDate, $endDate);
        $startC = Carbon::parse($startDate)->startOfDay();
        $endC = Carbon::parse($endDate)->startOfDay();
        $days = max(1, $startC->diffInDays($endC) + 1);
        $prevEnd = $startC->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);
        $prev = $this->salesRangeTotals($request, $prevStart->toDateString(), $prevEnd->toDateString());

        return [
            'invoice_count' => $curr['invoice_count'],
            'gross' => $curr['gross'],
            'net' => $curr['net'],
            'deductions' => $curr['deductions'],
            'prev_invoice_count' => $prev['invoice_count'],
            'prev_gross' => $prev['gross'],
            'prev_net' => $prev['net'],
            'prev_deductions' => $prev['deductions'],
            'pct_invoices' => $this->percentChangeFloat((float) $curr['invoice_count'], (float) $prev['invoice_count']),
            'pct_gross' => $this->percentChangeFloat($curr['gross'], $prev['gross']),
            'pct_net' => $this->percentChangeFloat($curr['net'], $prev['net']),
            'pct_deductions' => $this->percentChangeFloat($curr['deductions'], $prev['deductions']),
        ];
    }

    /**
     * @return array{invoice_count: int, gross: float, net: float, deductions: float}
     */
    private function salesRangeTotals(Request $request, string $startDate, string $endDate): array
    {
        $base = $this->salesOrdersQuery($request, $startDate, $endDate);
        $invoiceCount = (int) (clone $base)->count();
        $idSubquery = (clone $base)->select('orders.id');

        $net = (float) Order_detail::query()
            ->whereIn('order_id', $idSubquery)
            ->sum('amount');

        $gross = (float) Order_detail::query()
            ->whereIn('order_id', $idSubquery)
            ->sum(DB::raw('(quantity * unitprice)'));

        $deductions = max(0.0, $gross - $net);

        return [
            'invoice_count' => $invoiceCount,
            'gross' => $gross,
            'net' => $net,
            'deductions' => $deductions,
        ];
    }

    private function percentChangeFloat(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.000001) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function applySalesSearchFilter(Builder $ordersQuery, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';
        $ordersQuery->where(function (Builder $oq) use ($term, $like) {
            $oq->where('orders.name', 'like', $like)
                ->orWhere('orders.mobile', 'like', $like);

            if (preg_match('/#?ORD-?0*(\d+)/i', $term, $m)) {
                $oq->orWhere('orders.id', (int) $m[1]);
            }

            $digitsOnly = preg_replace('/\D+/', '', $term);
            if ($digitsOnly !== '' && ctype_digit($digitsOnly)) {
                $id = (int) $digitsOnly;
                if ($id > 0 && $id <= 2147483647) {
                    $oq->orWhere('orders.id', $id);
                }
                if (strlen($digitsOnly) <= 15) {
                    $oq->orWhereRaw('CAST(orders.id AS CHAR) LIKE ?', ['%'.$digitsOnly.'%']);
                }
            }
        });
    }

    private function applySalesMetrics(Order $order): Order
    {
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

    private function salesPaymentStatusCsvLabel(string $status): string
    {
        if ($status === 'paid') {
            return 'Paid';
        }
        if ($status === 'partial') {
            return 'Partial';
        }
        if ($status === 'pending') {
            return 'Pending';
        }

        return '';
    }
}
