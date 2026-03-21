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
        $products = Product::with(['manufacturer', 'preferredSupplier'])->paginate(5);

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

        $products = new Product;
        $products->product_name = $request->product_name;
        $products->alias = $request->input('alias');
        $products->description = $request->description;
        $products->manufacturer_id = (int) $request->manufacturer_id;
        $products->preferred_supplier_id = $request->filled('preferred_supplier_id')
            ? (int) $request->preferred_supplier_id
            : null;
        $products->price =$request->price;
        $products->quantity =$request->quantity;
        $products->supplierprice = $request->supplierprice;
        $products->stock_alert = $this->normalizedStockAlert($request->input('stock_alert'));
        $products->form = $request->form;
        $products->unit_of_measure = $request->filled('unit_of_measure') ? $request->unit_of_measure : null;
        $products->volume = $request->filled('volume') ? trim($request->volume) : null;
        $products->expiredate = $request->expiredate;
        $products->product_img = $fileNameToStore;
        
        $products->save();

        $this->recordInventoryMovement(
            $products,
            null,
            (int) $products->quantity,
            'initial',
            null,
            Site::defaultId()
        );

        return redirect('/products')->with('success', 'Product Added Successfully');
    }

    /**
     * Stock ledger for a product (movements in reverse chronological order).
     */
    public function inventoryHistory(Product $product)
    {
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

        $products = Product::findOrFail($id);

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
