<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\StockTransfer;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Support\CurrentSite;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Products at or below their low-stock alert level.
     */
    public function lowStock()
    {
        $products = Product::query()
            ->forTenantCatalog()
            ->whereNotNull('stock_alert')
            ->whereColumn('quantity', '<=', 'stock_alert')
            ->orderBy('quantity')
            ->paginate(20);

        return view('inventory.low-stock', compact('products'));
    }

    /**
     * Stock-focused view: on-hand levels and quick actions.
     */
    public function manageStock()
    {
        $products = Product::query()
            ->forTenantCatalog()
            ->orderBy('product_name')
            ->paginate(15);

        return view('inventory.manage-stock', compact('products'));
    }

    public function createStockAdjustment()
    {
        $products = Product::query()
            ->forTenantCatalog()
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'alias', 'quantity', 'stock_alert']);

        $sites = Site::query()->forUserTenant(auth()->user())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $defaultSiteId = CurrentSite::id();

        return view('inventory.stock-adjustment', compact('products', 'sites', 'defaultSiteId'));
    }

    public function storeStockAdjustment(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'direction' => ['required', 'in:add,remove'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $siteId = isset($data['site_id']) ? (int) $data['site_id'] : CurrentSite::id();

        $delta = $data['direction'] === 'add'
            ? (int) $data['quantity']
            : -(int) $data['quantity'];

        DB::transaction(function () use ($data, $delta, $siteId) {
            $product = Product::query()->forTenantCatalog()->lockForUpdate()->findOrFail($data['product_id']);
            $site = Site::query()->findOrFail($siteId);
            if ((int) $product->company_id !== (int) $site->company_id) {
                throw ValidationException::withMessages([
                    'product_id' => 'Product does not belong to this branch’s organization.',
                ]);
            }

            $pss = ProductSiteStock::query()
                ->where('product_id', $product->id)
                ->where('site_id', $siteId)
                ->lockForUpdate()
                ->first();

            if (! $pss) {
                ProductSiteStock::create([
                    'product_id' => $product->id,
                    'site_id' => $siteId,
                    'quantity' => 0,
                ]);
                $pss = ProductSiteStock::query()
                    ->where('product_id', $product->id)
                    ->where('site_id', $siteId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $before = (int) $pss->quantity;
            $after = $before + $delta;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock to remove this amount at this site. Current on-hand: '.$before.'.',
                ]);
            }

            $pss->quantity = $after;
            $pss->save();

            Product::syncQuantityFromSiteStocks($product->id);

            InventoryMovement::create([
                'product_id' => $product->id,
                'site_id' => $siteId,
                'user_id' => auth()->id(),
                'quantity_before' => $before,
                'quantity_delta' => $delta,
                'quantity_after' => $after,
                'change_type' => 'adjustment',
                'note' => $data['reason'],
                'stock_receipt_id' => null,
            ]);
        });

        return redirect()
            ->route('inventory.stock-adjustment.create')
            ->with('success', 'Stock adjustment saved.');
    }

    public function stockTransfer()
    {
        $products = Product::query()
            ->forTenantCatalog()
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'alias', 'quantity']);

        $sites = Site::query()->forUserTenant(auth()->user())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('inventory.stock-transfer', compact('products', 'sites'));
    }

    public function storeStockTransfer(Request $request)
    {
        $data = $request->validate([
            'from_site_id' => ['required', 'exists:sites,id'],
            'to_site_id' => ['required', 'exists:sites,id', 'different:from_site_id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $fromSiteId = (int) $data['from_site_id'];
        $toSiteId = (int) $data['to_site_id'];
        $qty = (int) $data['quantity'];
        $productId = (int) $data['product_id'];

        DB::transaction(function () use ($fromSiteId, $toSiteId, $qty, $productId, $data) {
            $product = Product::query()->forTenantCatalog()->lockForUpdate()->findOrFail($productId);
            $fromSite = Site::query()->findOrFail($fromSiteId);
            $toSite = Site::query()->findOrFail($toSiteId);
            if ((int) $product->company_id !== (int) $fromSite->company_id
                || (int) $fromSite->company_id !== (int) $toSite->company_id) {
                throw ValidationException::withMessages([
                    'product_id' => 'Transfer only between branches of the same organization, for that tenant’s products.',
                ]);
            }

            $from = ProductSiteStock::query()
                ->where('product_id', $product->id)
                ->where('site_id', $fromSiteId)
                ->lockForUpdate()
                ->first();

            if (! $from) {
                ProductSiteStock::create([
                    'product_id' => $product->id,
                    'site_id' => $fromSiteId,
                    'quantity' => 0,
                ]);
                $from = ProductSiteStock::query()
                    ->where('product_id', $product->id)
                    ->where('site_id', $fromSiteId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $beforeFrom = (int) $from->quantity;
            if ($beforeFrom < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock at the source site (available: '.$beforeFrom.').',
                ]);
            }

            $to = ProductSiteStock::query()
                ->where('product_id', $product->id)
                ->where('site_id', $toSiteId)
                ->lockForUpdate()
                ->first();

            if (! $to) {
                ProductSiteStock::create([
                    'product_id' => $product->id,
                    'site_id' => $toSiteId,
                    'quantity' => 0,
                ]);
                $to = ProductSiteStock::query()
                    ->where('product_id', $product->id)
                    ->where('site_id', $toSiteId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $beforeTo = (int) $to->quantity;

            $from->quantity = $beforeFrom - $qty;
            $from->save();

            $to->quantity = $beforeTo + $qty;
            $to->save();

            Product::syncQuantityFromSiteStocks($product->id);

            $transfer = StockTransfer::create([
                'from_site_id' => $fromSiteId,
                'to_site_id' => $toSiteId,
                'product_id' => $product->id,
                'quantity' => $qty,
                'user_id' => auth()->id(),
                'note' => $data['note'] ?? null,
            ]);

            $noteOut = trim((string) ($data['note'] ?? ''));
            $ledgerNote = $noteOut !== '' ? $noteOut : 'Transfer #'.$transfer->id;

            InventoryMovement::create([
                'product_id' => $product->id,
                'site_id' => $fromSiteId,
                'user_id' => auth()->id(),
                'quantity_before' => $beforeFrom,
                'quantity_delta' => -$qty,
                'quantity_after' => $beforeFrom - $qty,
                'change_type' => 'transfer_out',
                'note' => $ledgerNote,
                'stock_transfer_id' => $transfer->id,
            ]);

            InventoryMovement::create([
                'product_id' => $product->id,
                'site_id' => $toSiteId,
                'user_id' => auth()->id(),
                'quantity_before' => $beforeTo,
                'quantity_delta' => $qty,
                'quantity_after' => $beforeTo + $qty,
                'change_type' => 'transfer_in',
                'note' => $ledgerNote,
                'stock_transfer_id' => $transfer->id,
            ]);
        });

        return redirect()
            ->route('inventory.stock-transfer')
            ->with('success', 'Stock transfer completed.');
    }

    public function catalogCategories()
    {
        return view('inventory.catalog.categories');
    }

    public function catalogBrands()
    {
        return redirect()->route('manufacturers.index');
    }

    public function catalogUnits()
    {
        $units = UnitOfMeasure::query()->ordered()->paginate(40);

        return view('inventory.catalog.units', compact('units'));
    }

    /**
     * Expired and near-expiry medicines (batch expiry on product master).
     */
    public function expiryTracking()
    {
        $today = now()->toDateString();
        $expired = Product::query()
            ->forTenantCatalog()
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '<', $today)
            ->orderBy('expiredate', 'desc')
            ->paginate(15, ['*'], 'expired_page');

        $nearExpiry = Product::query()
            ->forTenantCatalog()
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '>=', $today)
            ->whereDate('expiredate', '<=', now()->addDays(90)->toDateString())
            ->orderBy('expiredate')
            ->paginate(15, ['*'], 'near_page');

        return view('inventory.expiry-tracking', compact('expired', 'nearExpiry'));
    }

    /**
     * Inbound batches from stock receipts (lot / expiry / supplier / branch).
     */
    public function batchManagement(Request $request)
    {
        $viewer = $request->user();
        $expiry = $request->input('expiry', 'all');
        if (! in_array($expiry, ['all', 'expired', 'expiring_90', 'ok', 'no_expiry'], true)) {
            $expiry = 'all';
        }

        $base = $this->batchReceiptBaseQuery($request, $viewer);
        $stats = $this->batchReceiptStats(clone $base);

        $tableQuery = clone $base;
        $this->applyBatchExpiryFilter($tableQuery, $expiry);

        $batches = $tableQuery->paginate(20)->withQueryString();
        $batches->getCollection()->transform(function (StockReceipt $b) {
            $b->setAttribute('lot_status', $this->batchLotStatus($b));

            return $b;
        });

        $sites = $viewer->isSuperAdmin()
            ? Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])
            : Site::query()->forUserTenant($viewer)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return view('inventory.batches', compact('batches', 'stats', 'sites', 'expiry'));
    }

    /**
     * CSV of filtered batch lines (same query string as the grid).
     */
    public function batchExport(Request $request): StreamedResponse
    {
        $viewer = $request->user();
        $expiry = $request->input('expiry', 'all');
        if (! in_array($expiry, ['all', 'expired', 'expiring_90', 'ok', 'no_expiry'], true)) {
            $expiry = 'all';
        }

        Log::channel('audit')->info('inventory.batches.export', [
            'user_id' => $viewer->id,
            'filters' => $request->only(['q', 'site_id', 'received_from', 'received_to', 'expiry']),
        ]);

        $base = $this->batchReceiptBaseQuery($request, $viewer);
        $tableQuery = clone $base;
        $this->applyBatchExpiryFilter($tableQuery, $expiry);

        $filename = 'batch-lots-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($tableQuery) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, [
                'Received',
                'Branch',
                'Product',
                'Qty',
                'Lot',
                'Batch expiry',
                'Status',
                'Supplier',
                'Receipt ID',
                'Document ref',
                'Received by',
            ]);

            $tableQuery->chunk(250, function ($rows) use ($out) {
                    foreach ($rows as $b) {
                        $st = $this->batchLotStatus($b);
                        fputcsv($out, [
                            $b->received_at->format('Y-m-d'),
                            $b->site ? $b->site->name : '—',
                            $b->product ? $b->product->product_name : '—',
                            $b->quantity,
                            $b->batch_number ?? '',
                            $b->expiry_date ? $b->expiry_date->format('Y-m-d') : '',
                            $st['label'],
                            $b->supplier ? $b->supplier->supplier_name : '',
                            (string) $b->id,
                            $b->document_reference ?? '',
                            $b->user ? $b->user->name : '',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function batchReceiptBaseQuery(Request $request, User $viewer): Builder
    {
        $q = StockReceipt::query()
            ->with(['product:id,product_name', 'supplier:id,supplier_name', 'user:id,name', 'site:id,name,code']);

        if (! $viewer->isSuperAdmin()) {
            $companyId = (int) ($viewer->company_id ?? 0);
            if ($companyId > 0) {
                $q->whereIn(
                    'stock_receipts.site_id',
                    Site::query()->where('company_id', $companyId)->select('id')
                );
            }
        }

        if ($request->filled('site_id')) {
            $sid = (int) $request->input('site_id');
            if ($sid > 0) {
                $q->where('stock_receipts.site_id', $sid);
            }
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            if ($term !== '') {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->whereHas('product', function (Builder $pq) use ($like) {
                    $pq->where('product_name', 'like', $like);
                });
            }
        }

        if ($request->filled('received_from')) {
            $q->whereDate('stock_receipts.received_at', '>=', Carbon::parse($request->input('received_from'))->toDateString());
        }
        if ($request->filled('received_to')) {
            $q->whereDate('stock_receipts.received_at', '<=', Carbon::parse($request->input('received_to'))->toDateString());
        }

        return $q->latest('stock_receipts.received_at')->latest('stock_receipts.id');
    }

    /**
     * @return array{total: int, expired: int, expiring_90: int, ok: int, no_expiry: int}
     */
    private function batchReceiptStats(Builder $base): array
    {
        $today = Carbon::today()->toDateString();
        $d90 = Carbon::today()->addDays(90)->toDateString();

        return [
            'total' => (clone $base)->count(),
            'expired' => (clone $base)->whereNotNull('stock_receipts.expiry_date')->whereDate('stock_receipts.expiry_date', '<', $today)->count(),
            'expiring_90' => (clone $base)->whereNotNull('stock_receipts.expiry_date')
                ->whereDate('stock_receipts.expiry_date', '>=', $today)
                ->whereDate('stock_receipts.expiry_date', '<=', $d90)
                ->count(),
            'ok' => (clone $base)->whereNotNull('stock_receipts.expiry_date')->whereDate('stock_receipts.expiry_date', '>', $d90)->count(),
            'no_expiry' => (clone $base)->whereNull('stock_receipts.expiry_date')->count(),
        ];
    }

    private function applyBatchExpiryFilter(Builder $q, string $expiry): void
    {
        if ($expiry === 'all') {
            return;
        }

        $today = Carbon::today()->toDateString();
        $d90 = Carbon::today()->addDays(90)->toDateString();

        if ($expiry === 'expired') {
            $q->whereNotNull('stock_receipts.expiry_date')->whereDate('stock_receipts.expiry_date', '<', $today);
        } elseif ($expiry === 'expiring_90') {
            $q->whereNotNull('stock_receipts.expiry_date')
                ->whereDate('stock_receipts.expiry_date', '>=', $today)
                ->whereDate('stock_receipts.expiry_date', '<=', $d90);
        } elseif ($expiry === 'ok') {
            $q->whereNotNull('stock_receipts.expiry_date')->whereDate('stock_receipts.expiry_date', '>', $d90);
        } elseif ($expiry === 'no_expiry') {
            $q->whereNull('stock_receipts.expiry_date');
        }
    }

    /**
     * @return array{key: string, label: string, badge: string}
     */
    private function batchLotStatus(StockReceipt $b): array
    {
        if (! $b->expiry_date) {
            return ['key' => 'no_expiry', 'label' => 'No lot expiry', 'badge' => 'bg-secondary'];
        }
        $today = Carbon::today()->startOfDay();
        $exp = $b->expiry_date->copy()->startOfDay();
        if ($exp->lt($today)) {
            return ['key' => 'expired', 'label' => 'Expired', 'badge' => 'bg-danger'];
        }
        if ($exp->lte($today->copy()->addDays(90))) {
            return ['key' => 'expiring', 'label' => 'Expiring ≤90d', 'badge' => 'bg-warning text-dark'];
        }

        return ['key' => 'ok', 'label' => 'OK', 'badge' => 'bg-success'];
    }

    /**
     * Cross-product inventory ledger (SKU, lot, qty in/out, balance, reference) with filters and CSV export.
     */
    public function inventoryLogs(Request $request)
    {
        $viewer = $request->user();
        $sites = Site::query()->forUserTenant($viewer)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $base = $this->inventoryLogsBaseQuery($request, $viewer);
        $movements = (clone $base)->paginate(20)->withQueryString();

        return view('inventory.inventory-logs', compact('movements', 'sites'));
    }

    public function inventoryLogsExport(Request $request): StreamedResponse
    {
        $viewer = $request->user();
        Log::channel('audit')->info('inventory.logs.export', [
            'user_id' => $viewer->id,
            'filters' => $request->only(['q', 'site_id', 'date_from', 'date_to', 'type', 'sort']),
        ]);

        $base = $this->inventoryLogsBaseQuery($request, $viewer);
        $filename = 'inventory-logs-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($base) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, [
                'Date & time',
                'SKU',
                'Item',
                'Batch / lot',
                'Storage / rack',
                'Branch',
                'Transaction type',
                'Quantity in',
                'Quantity out',
                'Balance stock',
                'Reference ID',
                'Note',
                'User',
            ]);

            foreach ((clone $base)->lazy(200) as $m) {
                /** @var InventoryMovement $m */
                fputcsv($out, [
                    $m->created_at->format('Y-m-d H:i:s'),
                    $m->product?->sku ?? $m->product?->item_code ?? '',
                    $m->product?->product_name ?? '',
                    $m->batchDisplay(),
                    $m->product?->rack_location ?? '',
                    $m->site?->name ?? '',
                    $m->transactionTypeLabel(),
                    $m->quantityInDisplay(),
                    $m->quantityOutDisplay(),
                    $m->quantity_after,
                    $m->referenceDisplay(),
                    $m->note ?? '',
                    $m->user?->name ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function inventoryLogsBaseQuery(Request $request, User $viewer): Builder
    {
        $q = InventoryMovement::query()
            ->with([
                'product:id,product_name,sku,item_code,company_id,rack_location',
                'stockReceipt:id,batch_number',
                'stockTransfer:id',
                'site:id,name,code',
                'user:id,name',
            ]);

        $q->whereHas('product', function (Builder $pq) use ($viewer) {
            if (! $viewer->isSuperAdmin()) {
                $pq->forTenantCatalog($viewer);
            }
        });

        if ($request->filled('site_id')) {
            $sid = (int) $request->input('site_id');
            if ($sid > 0) {
                $q->where('inventory_movements.site_id', $sid);
            }
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            if ($term !== '') {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->whereHas('product', function (Builder $pq) use ($like) {
                    $pq->where(function (Builder $p2) use ($like) {
                        $p2->where('product_name', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('item_code', 'like', $like)
                            ->orWhere('alias', 'like', $like)
                            ->orWhere('rack_location', 'like', $like);
                    });
                });
            }
        }

        if ($request->filled('date_from')) {
            $q->whereDate('inventory_movements.created_at', '>=', Carbon::parse($request->input('date_from'))->toDateString());
        }
        if ($request->filled('date_to')) {
            $q->whereDate('inventory_movements.created_at', '<=', Carbon::parse($request->input('date_to'))->toDateString());
        }

        $type = $request->input('type', 'all');
        if ($type === 'purchase') {
            $q->where('inventory_movements.change_type', 'receipt');
        } elseif ($type === 'sales') {
            $q->where('inventory_movements.change_type', 'sale');
        } elseif ($type === 'adjustment') {
            $q->where('inventory_movements.change_type', 'adjustment');
        } elseif ($type === 'transfer') {
            $q->whereIn('inventory_movements.change_type', ['transfer_in', 'transfer_out']);
        } elseif ($type === 'opening') {
            $q->where('inventory_movements.change_type', 'initial');
        } elseif ($type === 'return') {
            $q->where('inventory_movements.change_type', 'sale_return');
        }

        $sort = $request->input('sort', 'date_desc');
        if ($sort === 'date_asc') {
            $q->orderBy('inventory_movements.created_at')->orderBy('inventory_movements.id');
        } elseif ($sort === 'sku_asc') {
            $q->orderBy(Product::select('sku')
                ->whereColumn('products.id', 'inventory_movements.product_id')
                ->limit(1))
                ->orderByDesc('inventory_movements.created_at')
                ->orderByDesc('inventory_movements.id');
        } elseif ($sort === 'product_asc') {
            $q->orderBy(Product::select('product_name')
                ->whereColumn('products.id', 'inventory_movements.product_id')
                ->limit(1))
                ->orderByDesc('inventory_movements.created_at')
                ->orderByDesc('inventory_movements.id');
        } else {
            $q->orderByDesc('inventory_movements.created_at')->orderByDesc('inventory_movements.id');
        }

        return $q;
    }

}
