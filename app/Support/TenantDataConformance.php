<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Order_detail;
use App\Models\Site;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;

/**
 * Detects and repairs common multi-tenant / multi-branch data drift for existing installs.
 */
final class TenantDataConformance
{
    /**
     * @return list<array{type: string, message: string, meta?: array<string, mixed>}>
     */
    public static function violations(?int $onlyCompanyId = null): array
    {
        $out = [];

        $userQuery = User::query()
            ->where('is_super_admin', false)
            ->whereNotNull('company_id');

        if ($onlyCompanyId !== null) {
            $userQuery->where('company_id', $onlyCompanyId);
        }

        foreach ($userQuery->cursor() as $user) {
            if ($user->site_id === null) {
                $out[] = [
                    'type' => 'user_missing_site',
                    'message' => 'Tenant user has null site_id',
                    'meta' => ['user_id' => $user->id, 'company_id' => $user->company_id],
                ];

                continue;
            }

            $siteCompany = Site::query()->whereKey($user->site_id)->value('company_id');
            if ($siteCompany === null || (int) $siteCompany !== (int) $user->company_id) {
                $out[] = [
                    'type' => 'user_site_company_mismatch',
                    'message' => 'User site_id does not belong to the same company as user.company_id',
                    'meta' => [
                        'user_id' => $user->id,
                        'company_id' => $user->company_id,
                        'site_id' => $user->site_id,
                    ],
                ];
            }
        }

        $supplierQuery = Supplier::query()->whereNull('company_id');
        foreach ($supplierQuery->cursor() as $supplier) {
            $out[] = [
                'type' => 'supplier_missing_company',
                'message' => 'Supplier has null company_id',
                'meta' => ['supplier_id' => $supplier->id],
            ];
        }

        $txBad = Transaction::query()
            ->whereHas('order', static function ($oq) use ($onlyCompanyId): void {
                $oq->whereNotNull('site_id');
                if ($onlyCompanyId !== null) {
                    $oq->whereHas('site', static fn ($s) => $s->where('company_id', $onlyCompanyId));
                }
            })
            ->where(function ($q): void {
                $q->whereNull('site_id')
                    ->orWhereNull('company_id')
                    ->orWhereRaw('site_id <> (SELECT o.site_id FROM orders o WHERE o.id = transactions.order_id)')
                    ->orWhereRaw(
                        'COALESCE(company_id, 0) <> COALESCE((SELECT s.company_id FROM orders o INNER JOIN sites s ON s.id = o.site_id WHERE o.id = transactions.order_id), 0)'
                    );
            })
            ->count();

        if ($txBad > 0) {
            $out[] = [
                'type' => 'transaction_scope_drift',
                'message' => $txBad.' transaction row(s) are not aligned with order branch (site_id / company_id)',
                'meta' => ['count' => $txBad],
            ];
        }

        $lineQuery = Order_detail::query()
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->join('products', 'products.id', '=', 'order_details.product_id')
            ->join('sites', 'sites.id', '=', 'orders.site_id')
            ->whereColumn('products.company_id', '!=', 'sites.company_id');

        if ($onlyCompanyId !== null) {
            $lineQuery->where('sites.company_id', $onlyCompanyId);
        }

        $badLines = (int) $lineQuery->count();
        if ($badLines > 0) {
            $out[] = [
                'type' => 'order_line_product_company_mismatch',
                'message' => $badLines.' order line(s) reference a product from a different organization than the order branch',
                'meta' => ['count' => $badLines],
            ];
        }

        return $out;
    }

    /**
     * @return array{users_site_aligned: int, transactions_scoped: int, suppliers_company_set: int}
     */
    public static function repair(?int $onlyCompanyId = null): array
    {
        $stats = [
            'users_site_aligned' => 0,
            'transactions_scoped' => 0,
            'suppliers_company_set' => 0,
        ];

        $userQuery = User::query()
            ->where('is_super_admin', false)
            ->whereNotNull('company_id');

        if ($onlyCompanyId !== null) {
            $userQuery->where('company_id', $onlyCompanyId);
        }

        foreach ($userQuery->cursor() as $user) {
            $homeId = Site::homeSiteIdForUser($user);
            if ($homeId === null) {
                continue;
            }
            if ((int) $user->site_id === $homeId) {
                continue;
            }
            User::query()->whereKey($user->id)->update(['site_id' => $homeId]);
            $stats['users_site_aligned']++;
        }

        $txQuery = Transaction::query()->with(['order.site:id,company_id']);
        if ($onlyCompanyId !== null) {
            $txQuery->whereHas('order.site', static fn ($q) => $q->where('company_id', $onlyCompanyId));
        }

        $txQuery->chunkById(200, static function ($transactions) use (&$stats): void {
            foreach ($transactions as $tx) {
                $order = $tx->order;
                if (! $order || ! $order->site_id) {
                    continue;
                }
                $site = $order->site;
                if (! $site) {
                    continue;
                }
                $sid = (int) $order->site_id;
                $cid = (int) $site->company_id;
                if ((int) $tx->site_id === $sid && (int) $tx->company_id === $cid) {
                    continue;
                }
                Transaction::query()->whereKey($tx->id)->update([
                    'site_id' => $sid,
                    'company_id' => $cid,
                ]);
                $stats['transactions_scoped']++;
            }
        });

        if ($onlyCompanyId === null) {
            $stats['suppliers_company_set'] += (int) Supplier::query()
                ->whereNull('company_id')
                ->update(['company_id' => Company::defaultId()]);
        }

        return $stats;
    }

    /**
     * True when there are no conformance violations (optionally for one tenant).
     */
    public static function isClean(?int $onlyCompanyId = null): bool
    {
        return self::violations($onlyCompanyId) === [];
    }
}
