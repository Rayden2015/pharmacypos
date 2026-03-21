<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
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

        return view('inventory.stock-adjustment', compact('products'));
    }

    public function storeStockAdjustment(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'direction' => ['required', 'in:add,remove'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $delta = $data['direction'] === 'add'
            ? (int) $data['quantity']
            : -(int) $data['quantity'];

        DB::transaction(function () use ($data, $delta) {
            $product = Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            $before = (int) $product->quantity;
            $after = $before + $delta;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock to remove this amount. Current on-hand: '.$before.'.',
                ]);
            }

            $product->quantity = $after;
            $product->save();

            InventoryMovement::create([
                'product_id' => $product->id,
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

    /**
     * Single-location pharmacy: transfers are optional / future multi-branch.
     */
    public function stockTransfer()
    {
        return view('inventory.stock-transfer');
    }

    public function catalogCategories()
    {
        return view('inventory.catalog.categories');
    }

    public function catalogBrands()
    {
        return view('inventory.catalog.brands');
    }

    public function catalogUnits()
    {
        return view('inventory.catalog.units');
    }
}
