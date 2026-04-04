<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\StockReceipt;
use App\Models\Supplier;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StockReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $viewer = auth()->user();

        $receiptsQuery = StockReceipt::query()
            ->with(['product:id,product_name,alias', 'supplier:id,supplier_name', 'user:id,name'])
            ->when($viewer && ! $viewer->isSuperAdmin() && $viewer->company_id, function ($q) use ($viewer) {
                $q->whereHas('site', function ($sq) use ($viewer) {
                    $sq->where('company_id', $viewer->company_id);
                });
            });

        $receipts = $receiptsQuery
            ->latest('received_at')
            ->latest('id')
            ->paginate(50);

        return view('stock-receipts.index', compact('receipts'));
    }

    public function create(Request $request)
    {
        $products = Product::query()
            ->forTenantCatalog()
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'alias', 'unit_of_measure', 'volume', 'quantity']);

        $suppliers = Supplier::query()
            ->forUserTenant(auth()->user())
            ->orderBy('supplier_name')
            ->get(['id', 'supplier_name']);

        $prefillProductId = $request->query('product_id');

        $sites = Site::query()->forUserTenant(auth()->user())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);
        $defaultSiteId = CurrentSite::id();

        return view('stock-receipts.create', compact('products', 'suppliers', 'prefillProductId', 'sites', 'defaultSiteId'));
    }

    public function store(Request $request)
    {
        $siteId = $request->filled('site_id') ? (int) $request->input('site_id') : CurrentSite::id();
        $site = Site::query()->findOrFail($siteId);
        $this->authorizeSiteForUser($request->user(), $site);
        $companyId = (int) $site->company_id;

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'site_id' => ['nullable', 'exists:sites,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'batch_number' => ['nullable', 'string', 'max:128'],
            'expiry_date' => ['nullable', 'date'],
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'document_reference' => ['nullable', 'string', 'max:128'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $receipt = DB::transaction(function () use ($data, $siteId) {
            $site = Site::query()->findOrFail($siteId);
            $product = Product::query()->forTenantCatalog()->lockForUpdate()->findOrFail((int) $data['product_id']);
            if ((int) $product->company_id !== (int) $site->company_id) {
                throw ValidationException::withMessages([
                    'product_id' => 'Product and receiving site must belong to the same organization.',
                ]);
            }

            $receipt = StockReceipt::query()->create([
                'product_id' => (int) $data['product_id'],
                'user_id' => auth()->id(),
                'site_id' => $siteId,
                'quantity' => (int) $data['quantity'],
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'supplier_id' => ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null,
                'document_reference' => $data['document_reference'] ?? null,
                'received_at' => $data['received_at'],
                'notes' => $data['notes'] ?? null,
            ]);

            $receipt->load('supplier');

            $product = Product::query()->lockForUpdate()->findOrFail($receipt->product_id);

            $pss = ProductSiteStock::query()
                ->where('product_id', $product->id)
                ->where('site_id', $siteId)
                ->lockForUpdate()
                ->first();

            if (! $pss) {
                $pss = ProductSiteStock::create([
                    'product_id' => $product->id,
                    'site_id' => $siteId,
                    'quantity' => 0,
                ]);
                $pss = ProductSiteStock::query()
                    ->whereKey($pss->id)
                    ->lockForUpdate()
                    ->first();
            }

            $before = (int) $pss->quantity;
            $add = (int) $receipt->quantity;
            $after = $before + $add;

            $pss->quantity = $after;
            $pss->save();

            Product::syncQuantityFromSiteStocks($product->id);

            InventoryMovement::create([
                'product_id' => $product->id,
                'site_id' => $siteId,
                'user_id' => auth()->id(),
                'quantity_before' => $before,
                'quantity_delta' => $add,
                'quantity_after' => $after,
                'change_type' => 'receipt',
                'note' => $receipt->ledgerNote(),
                'stock_receipt_id' => $receipt->id,
            ]);

            return $receipt;
        });

        return redirect()
            ->route('inventory.receipts.show', $receipt)
            ->with('success', 'Stock received and on-hand quantity updated.');
    }

    public function show(StockReceipt $stockReceipt)
    {
        $this->authorize('view', $stockReceipt);

        $stockReceipt->load(['product', 'supplier', 'user', 'inventoryMovement', 'site']);

        return view('stock-receipts.show', compact('stockReceipt'));
    }

    private function authorizeSiteForUser(\App\Models\User $user, Site $site): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }
        if ((int) $site->company_id !== (int) ($user->company_id ?? 0)) {
            abort(403, 'That branch does not belong to your organization.');
        }
    }
}
