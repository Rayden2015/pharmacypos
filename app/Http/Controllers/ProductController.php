<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Manufacturer;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::query()
            ->forTenantCatalog()
            ->with(['manufacturer', 'preferredSupplier'])
            ->paginate(5);

        return view('products.index', array_merge(
            ['products' => $products],
            $this->catalogSelects()
        ));
    }

    /**
     * @return array{manufacturers: \Illuminate\Support\Collection, suppliers: \Illuminate\Support\Collection}
     */
    private function catalogSelects(): array
    {
        return [
            'manufacturers' => Manufacturer::query()->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(['id', 'supplier_name']),
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! $request->filled('sku') || trim((string) $request->input('sku')) === '') {
            $request->merge(['sku' => null]);
        }

        $featureExpiry = (string) $request->input('feature_expiry') === '1';

        $request->validate([
            'site_id' => 'required|exists:sites,id',
        ]);

        $site = Site::query()->findOrFail((int) $request->input('site_id'));
        $this->authorizeSiteForUser($request->user(), $site);
        $companyId = (int) $site->company_id;

        $request->validate([
            'product_name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'sku' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'item_code' => 'nullable|string|max:64',
            'selling_type' => 'required|in:retail,wholesale',
            'category' => 'nullable|string|max:128',
            'sub_category' => 'nullable|string|max:128',
            'barcode_symbology' => 'nullable|string|max:32',
            'tax_type' => 'nullable|string|max:32',
            'discount_type' => 'required|in:none,percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'product_type' => 'required|in:single,variable',
            'warranty_term' => 'nullable|string|max:128',
            'manufactured_date' => 'nullable|date',
            'warehouse_note' => 'nullable|string|max:255',
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
            'alias' => 'nullable|string|max:255',
            'description' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));
                    $words = $plain === '' ? [] : preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
                    if (count($words) > 60) {
                        $fail('Description must be at most 60 words.');
                    }
                },
            ],
            'price' => 'required|numeric|min:0',
            'supplierprice' => 'nullable|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'stock_alert' => 'nullable|integer|min:0',
            'form' => 'required|string|max:100',
            'unit_of_measure' => 'nullable|string|max:64',
            'volume' => 'nullable|string|max:128',
            'expiredate' => [
                Rule::requiredIf(fn () => $featureExpiry),
                'nullable',
                'date',
            ],
            'product_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'product_img.image' => 'The product image must be a valid image file.',
            'product_img.mimes' => 'Use JPG, PNG, GIF, or WebP for the product image.',
            'product_img.max' => 'The product image may not be greater than 5 MB.',
        ]);

        if ($request->hasFile('product_img')) {
            //Get file name
            $fileNameWithExt = $request->file('product_img')->getClientOriginalName();
            //File name
            $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);

            $extension = $request->file('product_img')->getClientOriginalExtension();

            $fileNameToStore = $filename. '_' .time(). '.' .$extension;

            $path = $request->file('product_img')->storeAs('public/products', $fileNameToStore);
        } else {
            $fileNameToStore = 'product.png';
        }

        $slugBase = Str::slug((string) ($request->input('slug') ?: $request->input('product_name')));
        $slug = $this->uniqueProductSlug($slugBase !== '' ? $slugBase : 'item', $companyId);

        $skuInput = $request->input('sku');
        $sku = $this->uniqueProductSku($skuInput !== null && trim((string) $skuInput) !== '' ? trim((string) $skuInput) : null, $companyId);

        $expire = $featureExpiry
            ? (string) $request->input('expiredate')
            : '2099-12-31';

        $products = new Product;
        $products->initial_site_id = (int) $request->input('site_id');
        $products->company_id = $companyId;
        $products->product_name = $request->product_name;
        $products->slug = $slug;
        $products->sku = $sku;
        $products->item_code = $request->filled('item_code') ? trim((string) $request->item_code) : null;
        $products->selling_type = $request->input('selling_type', 'retail');
        $products->category = $request->input('category');
        $products->sub_category = $request->input('sub_category');
        $products->barcode_symbology = $request->input('barcode_symbology') ?: null;
        $products->tax_type = $request->input('tax_type') ?: null;
        $products->discount_type = $request->input('discount_type', 'none');
        $products->discount_value = $request->input('discount_type') !== 'none'
            ? $request->input('discount_value')
            : null;
        $products->product_type = $request->input('product_type', 'single');
        $products->warranty_term = (string) $request->input('feature_warranty') === '1'
            ? ($request->input('warranty_term') ?: null)
            : null;
        $products->manufactured_date = $request->input('manufactured_date');
        $products->warehouse_note = $request->input('warehouse_note');
        $products->alias = $request->input('alias');
        $products->description = $request->description;
        $products->manufacturer_id = (int) $request->manufacturer_id;
        $products->preferred_supplier_id = $request->filled('preferred_supplier_id')
            ? (int) $request->preferred_supplier_id
            : null;
        $products->price = $request->price;
        $products->quantity = $request->quantity;
        $products->supplierprice = $request->supplierprice;
        $products->stock_alert = $this->normalizedStockAlert($request->input('stock_alert'));
        $products->form = $request->form;
        $products->unit_of_measure = $request->filled('unit_of_measure') ? $request->unit_of_measure : null;
        $products->volume = $request->filled('volume') ? trim($request->volume) : null;
        $products->expiredate = $expire;
        $products->product_img = $fileNameToStore;

        $products->save();

        $siteId = $products->initial_site_id ?? Site::defaultId();
        $this->recordInventoryMovement(
            $products,
            null,
            (int) $products->quantity,
            'initial',
            null,
            $siteId
        );

        $msg = 'Product Added Successfully';
        if ($products->product_type === 'variable') {
            $msg .= ' — Variable product saved as a single SKU; add variants in a future update.';
        }

        return redirect('/products')->with('success', $msg);
    }

    private function uniqueProductSlug(string $base, int $companyId): string
    {
        $slug = $base !== '' ? $base : 'item';
        $candidate = $slug;
        $i = 0;
        while (Product::query()->where('company_id', $companyId)->where('slug', $candidate)->exists()) {
            $i++;
            $candidate = $slug.'-'.$i;
        }

        return $candidate;
    }

    private function uniqueProductSku(?string $sku, int $companyId): ?string
    {
        if ($sku === null || $sku === '') {
            return null;
        }
        $candidate = $sku;
        $i = 0;
        while (Product::query()->where('company_id', $companyId)->where('sku', $candidate)->exists()) {
            $i++;
            $candidate = $sku.'-'.$i;
        }

        return $candidate;
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

    private function authorizeProductAccess(Product $product): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if ($user->isSuperAdmin()) {
            return;
        }
        if ((int) $product->company_id !== (int) ($user->company_id ?? 0)) {
            abort(403);
        }
    }

    /**
     * Stock ledger for a product (movements in reverse chronological order).
     */
    public function inventoryHistory(Product $product)
    {
        $this->authorizeProductAccess($product);

        $movements = $product->inventoryMovements()
            ->with(['user:id,name', 'stockReceipt'])
            ->latest()
            ->paginate(50);

        return view('products.inventory-history', compact('product', 'movements'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $id)
    {
        $products = Product::find($id);
        Storage::delete('public/products/'.$products->product_img );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     * @param  int  $id
     */
    public function update(Request $request, $id)
    {
        $products = Product::findOrFail($id);
        $this->authorizeProductAccess($products);

        $request->validate([
            'product_name' => 'required|string|max:255',
            'manufacturer_id' => 'required|exists:manufacturers,id',
            'preferred_supplier_id' => 'nullable|exists:suppliers,id',
            'alias' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'supplierprice' => 'nullable|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'stock_alert' => 'nullable|integer|min:0',
            'form' => 'required|string|max:100',
            'unit_of_measure' => 'nullable|string|max:64',
            'volume' => 'nullable|string|max:128',
            'expiredate' => 'required|date',
            'product_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'inventory_note' => 'nullable|string|max:500',
        ], [
            'product_img.image' => 'The product image must be a valid image file.',
            'product_img.mimes' => 'Use JPG, PNG, GIF, or WebP for the product image.',
            'product_img.max' => 'The product image may not be greater than 5 MB.',
        ]);

        if ($request->hasFile('product_img')) {
            //Get file name
            $fileNameWithExt = $request->file('product_img')->getClientOriginalName();
            //File name
            $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);

            $extension = $request->file('product_img')->getClientOriginalExtension();

            $fileNameToStore = $filename. '_' .time(). '.' .$extension;

            $path = $request->file('product_img')->storeAs('public/products', $fileNameToStore);
        } else {
            $fileNameToStore = $products->product_img ?: 'product.png';
        }

        $data = $request->input();
        $quantityAfter = (int) $data['quantity'];
        $productTotalBefore = (int) $products->quantity;
        $delta = $quantityAfter - $productTotalBefore;
        $defaultSiteId = Site::defaultId();

        DB::transaction(function () use ($data, $products, $delta, $defaultSiteId, $fileNameToStore, $request) {
            if ($delta !== 0) {
                $pss = ProductSiteStock::query()
                    ->where('product_id', $products->id)
                    ->where('site_id', $defaultSiteId)
                    ->lockForUpdate()
                    ->first();

                if (! $pss) {
                    $pss = ProductSiteStock::create([
                        'product_id' => $products->id,
                        'site_id' => $defaultSiteId,
                        'quantity' => 0,
                    ]);
                }

                $beforeSite = (int) $pss->quantity;
                $afterSite = $beforeSite + $delta;

                if ($afterSite < 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Cannot reduce total below what other branches hold. Adjust the default branch stock or transfer stock first.',
                    ]);
                }

                $pss->quantity = $afterSite;
                $pss->save();

                Product::syncQuantityFromSiteStocks($products->id);
                $products->refresh();

                $this->recordInventoryMovement(
                    $products,
                    $beforeSite,
                    $afterSite,
                    'adjustment',
                    $request->input('inventory_note'),
                    $defaultSiteId
                );
            }

            $products->product_name = $data['product_name'];
            $products->alias = $data['alias'] ?? null;
            $products->description = $data['description'];
            $products->manufacturer_id = (int) $data['manufacturer_id'];
            $products->preferred_supplier_id = ! empty($data['preferred_supplier_id'])
                ? (int) $data['preferred_supplier_id']
                : null;
            $products->price = $data['price'];
            $products->supplierprice = $data['supplierprice'];
            $products->stock_alert = $this->normalizedStockAlert($data['stock_alert'] ?? null, $products->stock_alert);
            $products->form = $data['form'];
            $products->unit_of_measure = ! empty($data['unit_of_measure']) ? $data['unit_of_measure'] : null;
            $products->volume = ! empty($data['volume']) ? trim($data['volume']) : null;
            $products->expiredate = $data['expiredate'];
            $products->product_img = $fileNameToStore;

            $products->save();
        });

        return redirect()->back()->with('success', 'Product Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $this->authorizeProductAccess($product);

        if($product->product_img != 'product.png'){
            Storage::delete('public/products/'.$product->product_img );
        }
        $product->delete();
        return redirect()->back()->with('success', 'Product Successfully Deleted');

    }

    /**
     * Stock alert threshold: DB column is NOT NULL; empty request values become null via middleware.
     */
    private function normalizedStockAlert($value, ?int $fallbackWhenEmpty = null): int
    {
        $fallback = $fallbackWhenEmpty ?? 100;
        if ($value === null || $value === '') {
            return $fallback;
        }

        return (int) $value;
    }

    private function recordInventoryMovement(
        Product $product,
        ?int $quantityBefore,
        int $quantityAfter,
        string $changeType,
        ?string $note = null,
        ?int $siteId = null,
        ?int $stockTransferId = null
    ): void {
        $delta = $quantityBefore === null
            ? $quantityAfter
            : $quantityAfter - $quantityBefore;

        InventoryMovement::create([
            'product_id' => $product->id,
            'site_id' => $siteId,
            'user_id' => auth()->id(),
            'quantity_before' => $quantityBefore,
            'quantity_delta' => $delta,
            'quantity_after' => $quantityAfter,
            'change_type' => $changeType,
            'note' => $note !== null && trim($note) !== ''
                ? Str::limit(trim($note), 500)
                : null,
            'stock_transfer_id' => $stockTransferId,
        ]);
    }
}
