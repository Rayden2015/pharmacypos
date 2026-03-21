<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $product = Product::paginate(5);

        return view('products.index')->with('products', $product);
        
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
            'brand' => 'required|string|max:255',
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
        $products->brand =$request->brand;
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

        $this->recordInventoryMovement($products, null, (int) $products->quantity, 'initial');

        return redirect('/products')->with('success', 'Product Added Successfully');
        return redirect()->back()->with('error', 'Product Registration Failed!');
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
            'brand' => 'required|string|max:255',
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
        $quantityBefore = (int) $products->quantity;

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
        $products->product_name = $data['product_name'];
        $products->alias = $data['alias'] ?? null;
        $products->description = $data['description'];
        $products->brand =$data['brand'];
        $products->price =$data['price'];
        $products->quantity =$data['quantity'];
        $products->supplierprice = $data['supplierprice'];
        $products->stock_alert = $this->normalizedStockAlert($data['stock_alert'] ?? null, $products->stock_alert);
        $products->form = $data['form'];
        $products->unit_of_measure = ! empty($data['unit_of_measure']) ? $data['unit_of_measure'] : null;
        $products->volume = ! empty($data['volume']) ? trim($data['volume']) : null;
        $products->expiredate = $data['expiredate'];
        $products->product_img = $fileNameToStore;
        $quantityAfter = (int) $data['quantity'];

        $products->save();

        if ($quantityBefore !== $quantityAfter) {
            $this->recordInventoryMovement(
                $products,
                $quantityBefore,
                $quantityAfter,
                'adjustment',
                $request->input('inventory_note')
            );
        }

        return redirect()->back()->with('success', 'Product Updated Successfully');
        return redirect()->back()->with('error', 'Product Registration Failed!');
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
        ?string $note = null
    ): void {
        $delta = $quantityBefore === null
            ? $quantityAfter
            : $quantityAfter - $quantityBefore;

        InventoryMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'quantity_before' => $quantityBefore,
            'quantity_delta' => $delta,
            'quantity_after' => $quantityAfter,
            'change_type' => $changeType,
            'note' => $note !== null && trim($note) !== ''
                ? Str::limit(trim($note), 500)
                : null,
        ]);
    }
}
