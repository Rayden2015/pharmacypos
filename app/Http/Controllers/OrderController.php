<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Transaction;
use App\Support\CurrentSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;




class OrderController extends Controller
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
        $products = Product::all();
        $orders = Order::all();
         //Display Last Order details
         $lastId = Order_detail::max('order_id');
         $order_receipt = Order_detail::where('order_id', $lastId)->get();

        return view('orders.index', ['products' => $products, 'order' => $orders, 'order_receipt' => $order_receipt]);
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
        DB::transaction(function () use ($request) {
            $orders = new Order;
            $orders->name = $request->customerName;
            $orders->mobile = $request->customerMobile;
            $orders->site_id = CurrentSite::id();
            $orders->save();
            $order_id = $orders->id;

            $siteId = CurrentSite::id();

            $productIds = $request->product_id ?? [];
            $quantities = $request->quantity ?? [];
            $discounts = $request->discount ?? [];
            $lastLineAmount = 0.0;

            for ($i = 0; $i < count($productIds); $i++) {
                if (empty($productIds[$i])) {
                    continue;
                }
                $product = Product::query()->lockForUpdate()->find($productIds[$i]);
                if (! $product) {
                    continue;
                }
                $unitPrice = (float) $product->price;
                $qty = (int) ($quantities[$i] ?? 0);
                if ($qty < 1) {
                    continue;
                }
                $disc = (float) ($discounts[$i] ?? 0);
                $lineTotal = ($qty * $unitPrice) - (($qty * $unitPrice * $disc) / 100);
                $lastLineAmount = $lineTotal;

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
                if ($before < $qty) {
                    throw ValidationException::withMessages([
                        'product_id' => 'Insufficient stock for '.$product->product_name.' at this site (available: '.$before.').',
                    ]);
                }

                $after = $before - $qty;
                $pss->quantity = $after;
                $pss->save();

                Product::syncQuantityFromSiteStocks($product->id);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'site_id' => $siteId,
                    'user_id' => auth()->id(),
                    'quantity_before' => $before,
                    'quantity_delta' => -$qty,
                    'quantity_after' => $after,
                    'change_type' => 'sale',
                    'note' => 'POS order #'.$order_id,
                ]);

                $order_details = new Order_detail;
                $order_details->order_id = $order_id;
                $order_details->product_id = $product->id;
                $order_details->unitprice = $unitPrice;
                $order_details->quantity = $qty;
                $order_details->discount = $disc;
                $order_details->amount = $lineTotal;
                $order_details->unit_of_measure = $product->unit_of_measure;
                $order_details->volume = $product->volume;
                $order_details->save();
            }

            $transaction = new Transaction();
            $transaction->order_id = $order_id;
            $transaction->user_id = auth()->user()->id;
            $transaction->balance = $request->balance;
            $transaction->paid_amount = $request->paidAmount;
            $transaction->payment_method = $request->paymentMethod;
            $transaction->transaction_amount = $lastLineAmount;
            $transaction->transaction_date = date('Y-m-d');
            $transaction->save();
        });

        return redirect()->back()->with('success', 'Product Order Successfull');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}
