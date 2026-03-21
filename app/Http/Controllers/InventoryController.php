<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\StockTransfer;
use App\Models\UnitOfMeasure;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            ->orderBy('product_name')
            ->paginate(15);

        return view('inventory.manage-stock', compact('products'));
    }

    public function createStockAdjustment()
    {
        $products = Product::query()
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'alias', 'quantity', 'stock_alert']);

        $sites = Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
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
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);

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
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'alias', 'quantity']);

        $sites = Site::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

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
            $product = Product::query()->lockForUpdate()->findOrFail($productId);

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
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '<', $today)
            ->orderBy('expiredate', 'desc')
            ->paginate(15, ['*'], 'expired_page');

        $nearExpiry = Product::query()
            ->whereNotNull('expiredate')
            ->whereDate('expiredate', '>=', $today)
            ->whereDate('expiredate', '<=', now()->addDays(90)->toDateString())
            ->orderBy('expiredate')
            ->paginate(15, ['*'], 'near_page');

        return view('inventory.expiry-tracking', compact('expired', 'nearExpiry'));
    }

    /**
     * Inbound batches from stock receipts (lot / expiry / supplier).
     */
    public function batchManagement()
    {
        $batches = StockReceipt::query()
            ->with(['product:id,product_name', 'supplier:id,supplier_name', 'user:id,name'])
            ->latest('received_at')
            ->latest('id')
            ->paginate(20);

        return view('inventory.batches', compact('batches'));
    }

}
