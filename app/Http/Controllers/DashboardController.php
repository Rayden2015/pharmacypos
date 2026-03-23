<?php

namespace App\Http\Controllers;

use App\Support\CurrentSite;
use App\Support\DashboardMetrics;
use App\Support\ReportAuditLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
     * Percent change vs prior period (avoids div-by-zero).
     */
    public static function percentChange(float $current, float $previous): ?float
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public static function dashboardViewData(): array
    {
        return DashboardMetrics::build();
    }

    /**
     * CSV export of dashboard summary (POS / inventory snapshot).
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        ReportAuditLogger::log($request, 'dashboard.export', [
            'dashboard_all_sites' => CurrentSite::dashboardAllSites(),
            'site_id' => CurrentSite::id(),
        ]);

        $data = self::dashboardViewData();
        $filename = 'pharmacy-dashboard-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Metric', 'Value']);
            fputcsv($out, ['Generated at', now()->toDateTimeString()]);
            fputcsv($out, ['Today sales', $data['today_sales']]);
            fputcsv($out, ['Month sales (MTD)', $data['month_sales']]);
            fputcsv($out, ['Purchase MTD (cost)', $data['purchase_mtd']]);
            fputcsv($out, ['Products in catalog', $data['total_products']]);
            fputcsv($out, ['Low stock SKUs', $data['low_stock_count']]);
            fputcsv($out, ['Prescriptions (last 30 days)', $data['prescriptions_last_30'] ?? 0]);
            fputcsv($out, ['Open AR (all balances)', $data['ar_open_total'] ?? 0]);
            fputcsv($out, ['Out of stock', $data['stock_out_count']]);
            fputcsv($out, ['Expired batches (products)', $data['expired_count']]);
            fputcsv($out, ['Est. inventory retail value', $data['inventory_retail_value']]);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
            'dashboard_all_sites' => CurrentSite::dashboardAllSites(),
            'dashboard_site_label' => CurrentSite::dashboardAllSites()
                ? 'All sites'
                : optional(\App\Models\Site::find(CurrentSite::id()))->name ?? 'This site',
        ]));
    }
}
