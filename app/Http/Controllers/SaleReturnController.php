<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Order_detail;
use App\Models\Product;
use App\Models\ProductSiteStock;
use App\Models\SaleReturn;
use App\Models\SaleReturnLine;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('pos_staff');
        $this->middleware('can:pos.refund');
    }

    public function index(Request $request): View
    {
        $viewer = $request->user();
        $orders = Order::query()
            ->with(['site:id,name,code'])
            ->withCount('saleReturns')
            ->when(! $viewer->isSuperAdmin(), function ($q) use ($viewer) {
                $q->whereIn('orders.site_id', Site::query()->forUserTenant($viewer)->select('id'));
            })
            ->orderByDesc('orders.id')
            ->paginate(25)
            ->withQueryString();

        return view('sales-returns.index', compact('orders'));
    }

    public function create(Request $request, Order $order): View
    {
        $this->authorizeOrder($request->user(), $order);

        $order->load(['orderdetail.product', 'site']);

        $lines = $order->orderdetail->map(function (Order_detail $d) {
            return [
                'detail' => $d,
                'returned' => $d->quantityReturned(),
                'returnable' => $d->quantityReturnable(),
            ];
        });

        return view('sales-returns.create', compact('order', 'lines'));
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request->user(), $order);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array'],
            'lines.*.order_detail_id' => ['required', 'integer', 'exists:order_details,id'],
            'lines.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $siteId = (int) $order->site_id;
        if ($siteId < 1) {
            throw ValidationException::withMessages(['order' => 'This order has no branch; returns cannot be recorded.']);
        }

        $order->load('orderdetail');

        $payloadLines = [];
        foreach ($data['lines'] as $row) {
            $qty = (int) $row['quantity'];
            if ($qty < 1) {
                continue;
            }
            $detailId = (int) $row['order_detail_id'];
            /** @var Order_detail|null $detail */
            $detail = $order->orderdetail->firstWhere('id', $detailId);
            if (! $detail || (int) $detail->order_id !== (int) $order->id) {
                throw ValidationException::withMessages([
                    "lines.$detailId.order_detail_id" => 'Line does not belong to this order.',
                ]);
            }
            $cap = $detail->quantityReturnable();
            if ($qty > $cap) {
                throw ValidationException::withMessages([
                    "lines.$detailId.quantity" => "Cannot return more than {$cap} for this line (already returned ".($detail->quantity - $cap).' of '.$detail->quantity.').',
                ]);
            }
            $payloadLines[] = ['detail' => $detail, 'quantity' => $qty];
        }

        if (count($payloadLines) === 0) {
            throw ValidationException::withMessages([
                'lines' => 'Enter a return quantity of at least one line.',
            ]);
        }

        DB::transaction(function () use ($order, $siteId, $data, $payloadLines, $request) {
            $saleReturn = SaleReturn::query()->create([
                'order_id' => $order->id,
                'site_id' => $siteId,
                'user_id' => $request->user()->id,
                'note' => $data['note'] ?? null,
            ]);

            $noteBase = 'Sales return #'.$saleReturn->id.' · Original POS order #'.$order->id;

            foreach ($payloadLines as $payload) {
                /** @var Order_detail $detail */
                $detail = $payload['detail'];
                $qty = $payload['quantity'];
                $product = Product::query()->lockForUpdate()->findOrFail($detail->product_id);

                SaleReturnLine::query()->create([
                    'sale_return_id' => $saleReturn->id,
                    'order_detail_id' => $detail->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                ]);

                $pss = ProductSiteStock::query()
                    ->where('product_id', $product->id)
                    ->where('site_id', $siteId)
                    ->lockForUpdate()
                    ->first();

                if (! $pss) {
                    ProductSiteStock::query()->create([
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
                $after = $before + $qty;
                $pss->quantity = $after;
                $pss->save();

                Product::syncQuantityFromSiteStocks($product->id);

                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'site_id' => $siteId,
                    'user_id' => $request->user()->id,
                    'quantity_before' => $before,
                    'quantity_delta' => $qty,
                    'quantity_after' => $after,
                    'change_type' => 'sale_return',
                    'note' => $noteBase,
                    'sale_return_id' => $saleReturn->id,
                ]);
            }
        });

        return redirect()
            ->route('sales.returns.index')
            ->with('success', 'Return recorded and stock updated for order #ORD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT).'.');
    }

    private function authorizeOrder(?User $user, Order $order): void
    {
        if (! $user) {
            abort(403);
        }
        if ($user->isSuperAdmin()) {
            abort(403);
        }
        $order->loadMissing('site');
        $companyId = (int) ($user->company_id ?? 0);
        if ($companyId > 0 && (int) ($order->site?->company_id ?? 0) !== $companyId) {
            abort(403, 'This order belongs to another organization.');
        }
    }
}
