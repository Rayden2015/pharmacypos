<?php

namespace App\Support;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Per-site aggregates for the cross-site comparison dashboard (super admins).
 */
class CrossSiteMetrics
{
    public static function build(): array
    {
        $sites = Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $siteIds = $sites->pluck('id')->all();

        $today = Carbon::today();
        $endToday = Carbon::now()->endOfDay();
        $rolling30Start = Carbon::today()->subDays(29)->startOfDay();
        $rolling7Start = Carbon::today()->subDays(6)->startOfDay();

        if ($siteIds === []) {
            return [
                'site_rows' => [],
                'chart_labels' => [],
                'chart_sales_30d' => [],
                'chart_payments_30d' => [],
                'network_sales_30d' => 0.0,
                'network_avg_sales_30d' => 0.0,
                'median_sales_30d' => 0.0,
                'period_30_label' => 'Last 30 days (rolling)',
                'period_7_label' => 'Last 7 days (rolling)',
            ];
        }

        $sales30 = self::sumOrderDetailsBySite($siteIds, $rolling30Start, $endToday);
        $sales7 = self::sumOrderDetailsBySite($siteIds, $rolling7Start, $endToday);
        $salesToday = self::sumOrderDetailsForDate($siteIds, $today);

        $orders30 = self::countOrdersBySiteBetween($siteIds, $rolling30Start, $endToday);
        $ordersToday = self::countOrdersBySiteOnDate($siteIds, $today);

        $tx = self::transactionsBySiteBetween(
            $siteIds,
            $rolling30Start->toDateString(),
            Carbon::now()->toDateString()
        );

        $rx30 = self::countPrescriptionsBySiteBetween($siteIds, $rolling30Start, $endToday);

        $rows = [];
        foreach ($sites as $site) {
            $sid = (int) $site->id;
            $s30 = (float) ($sales30[$sid] ?? 0.0);
            $o30 = (int) ($orders30[$sid] ?? 0);
            $avg30 = $o30 > 0 ? $s30 / $o30 : 0.0;

            $rows[] = [
                'site' => $site,
                'sales_30d' => $s30,
                'sales_7d' => (float) ($sales7[$sid] ?? 0.0),
                'sales_today' => (float) ($salesToday[$sid] ?? 0.0),
                'orders_30d' => $o30,
                'orders_today' => (int) ($ordersToday[$sid] ?? 0),
                'payments_30d' => (float) ($tx['paid'][$sid] ?? 0.0),
                'transactions_30d' => (int) ($tx['count'][$sid] ?? 0),
                'avg_order_30d' => $avg30,
                'rx_30d' => (int) ($rx30[$sid] ?? 0),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['sales_30d'] <=> $a['sales_30d']);

        $networkSales = array_sum(array_column($rows, 'sales_30d'));
        $n = count($rows);
        $avgPerSite = $n > 0 ? $networkSales / $n : 0.0;
        $salesValues = array_column($rows, 'sales_30d');
        $median = self::medianFloat($salesValues);

        $rank = 1;
        foreach ($rows as &$row) {
            $row['rank'] = $rank++;
            $row['share_pct'] = $networkSales > 0.0
                ? round(($row['sales_30d'] / $networkSales) * 100, 1)
                : 0.0;
            $row['vs_median_pct'] = $median > 0.0
                ? round((($row['sales_30d'] - $median) / $median) * 100, 1)
                : 0.0;
            $row['perf'] = self::performanceTier($row['sales_30d'], $median);
        }
        unset($row);

        $chartLabels = array_map(function (array $r) {
            $s = $r['site'];
            $label = $s->name;
            if ($s->code) {
                $label .= ' · '.$s->code;
            }

            return \Illuminate\Support\Str::limit($label, 32);
        }, $rows);

        return [
            'site_rows' => $rows,
            'chart_labels' => $chartLabels,
            'chart_sales_30d' => array_column($rows, 'sales_30d'),
            'chart_payments_30d' => array_column($rows, 'payments_30d'),
            'network_sales_30d' => $networkSales,
            'network_avg_sales_30d' => $avgPerSite,
            'median_sales_30d' => $median,
            'period_30_label' => 'Last 30 days (rolling)',
            'period_7_label' => 'Last 7 days (rolling)',
        ];
    }

    private static function performanceTier(float $sales, float $median): string
    {
        if ($median <= 0.0) {
            return 'typical';
        }
        if ($sales >= $median * 1.1) {
            return 'strong';
        }
        if ($sales <= $median * 0.9) {
            return 'slow';
        }

        return 'typical';
    }

    /**
     * @param  float[]  $values
     */
    private static function medianFloat(array $values): float
    {
        $values = array_values(array_filter($values, fn ($v) => is_numeric($v)));
        $n = count($values);
        if ($n === 0) {
            return 0.0;
        }
        sort($values, SORT_NUMERIC);
        $mid = (int) floor($n / 2);
        if ($n % 2 === 1) {
            return (float) $values[$mid];
        }

        return ((float) $values[$mid - 1] + (float) $values[$mid]) / 2.0;
    }

    /**
     * @param  int[]  $siteIds
     * @return array<int, float>
     */
    private static function sumOrderDetailsBySite(array $siteIds, Carbon $start, Carbon $end): array
    {
        return DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->whereIn('o.site_id', $siteIds)
            ->whereBetween('od.created_at', [$start, $end])
            ->groupBy('o.site_id')
            ->selectRaw('o.site_id as site_id, SUM(od.amount) as total')
            ->pluck('total', 'site_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * @param  int[]  $siteIds
     * @return array<int, float>
     */
    private static function sumOrderDetailsForDate(array $siteIds, Carbon $date): array
    {
        return DB::table('order_details as od')
            ->join('orders as o', 'o.id', '=', 'od.order_id')
            ->whereIn('o.site_id', $siteIds)
            ->whereDate('od.created_at', $date)
            ->groupBy('o.site_id')
            ->selectRaw('o.site_id as site_id, SUM(od.amount) as total')
            ->pluck('total', 'site_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * @param  int[]  $siteIds
     * @return array<int, int>
     */
    private static function countOrdersBySiteBetween(array $siteIds, Carbon $start, Carbon $end): array
    {
        return DB::table('orders')
            ->whereIn('site_id', $siteIds)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('site_id')
            ->selectRaw('site_id, COUNT(*) as c')
            ->pluck('c', 'site_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @param  int[]  $siteIds
     * @return array<int, int>
     */
    private static function countOrdersBySiteOnDate(array $siteIds, Carbon $date): array
    {
        return DB::table('orders')
            ->whereIn('site_id', $siteIds)
            ->whereDate('created_at', $date)
            ->groupBy('site_id')
            ->selectRaw('site_id, COUNT(*) as c')
            ->pluck('c', 'site_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * @param  int[]  $siteIds
     * @return array{paid: array<int, float>, count: array<int, int>}
     */
    private static function transactionsBySiteBetween(array $siteIds, string $startDate, string $endDate): array
    {
        $rows = DB::table('transactions as t')
            ->join('orders as o', 'o.id', '=', 't.order_id')
            ->whereIn('o.site_id', $siteIds)
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->groupBy('o.site_id')
            ->selectRaw('o.site_id as site_id, SUM(t.paid_amount) as paid, COUNT(*) as c')
            ->get();

        $paid = [];
        $count = [];
        foreach ($rows as $row) {
            $sid = (int) $row->site_id;
            $paid[$sid] = (float) $row->paid;
            $count[$sid] = (int) $row->c;
        }

        return ['paid' => $paid, 'count' => $count];
    }

    /**
     * @param  int[]  $siteIds
     * @return array<int, int>
     */
    private static function countPrescriptionsBySiteBetween(array $siteIds, Carbon $start, Carbon $end): array
    {
        return DB::table('prescriptions')
            ->whereIn('site_id', $siteIds)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('site_id')
            ->selectRaw('site_id, COUNT(*) as c')
            ->pluck('c', 'site_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
