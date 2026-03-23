<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\Site;
use App\Models\Transaction;
use App\Support\CurrentSite;
use Illuminate\Http\JsonResponse;
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
        $this->middleware('pos_staff');
        $this->middleware('can:pos.access');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::query()->forTenantCatalog()->orderBy('product_name')->get();
        $orders = Order::all();
         //Display Last Order details
         $lastId = Order_detail::max('order_id');
         $order_receipt = Order_detail::where('order_id', $lastId)->get();

        return view('orders.index', ['products' => $products, 'order' => $orders, 'order_receipt' => $order_receipt]);
    }

    /**
     * JSON lookup for POS: fill customer name when mobile matches a registered customer (same company / branch sites).
     */
    public function lookupCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:32',
        ]);

        $site = Site::query()->findOrFail(CurrentSite::id());
        $companyId = (int) $site->company_id;

        $customer = Customer::findForCompanyByNormalizedMobile($companyId, $request->input('phone'));
        if (! $customer) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'name' => $customer->name,
            'mobile' => $customer->mobile,
        ]);
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
            $siteId = CurrentSite::id();
            $site = Site::query()->findOrFail($siteId);

            $this->upsertCustomerFromPos($request, (int) $site->company_id);

            $orders = new Order;
            $orders->name = $request->customerName;
            $orders->mobile = $request->customerMobile;
            $orders->site_id = $siteId;
            $orders->save();
            $order_id = $orders->id;

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
                if ((int) $product->company_id !== (int) $site->company_id) {
                    throw ValidationException::withMessages([
                        'product_id' => 'Invalid product for this branch.',
                    ]);
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
     * Register or update customer directory when POS checkout includes both name and mobile (same company).
     */
    private function upsertCustomerFromPos(Request $request, int $companyId): void
    {
        $name = trim((string) $request->customerName);
        $mobileRaw = trim((string) $request->customerMobile);
        if ($name === '' || $mobileRaw === '') {
            return;
        }

        $norm = Customer::normalizeMobile($mobileRaw);
        if (strlen($norm) < 7) {
            return;
        }

        $existing = Customer::findForCompanyByNormalizedMobile($companyId, $mobileRaw);
        if ($existing) {
            if ($existing->name !== $name) {
                $existing->name = $name;
                $existing->saveQuietly();
            }

            return;
        }

        Customer::create([
            'name' => $name,
            'mobile' => $mobileRaw,
            'site_id' => CurrentSite::id(),
            'is_active' => true,
        ]);
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
