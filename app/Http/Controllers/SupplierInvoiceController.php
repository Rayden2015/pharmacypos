<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierInvoiceController extends Controller
{
    /** @var list<string> */
    public const PAYMENT_METHODS = ['Cash', 'Card', 'UPI', 'Bank transfer', 'Mobile money', 'Cheque', 'Other'];

    public function __construct()
    {
        $this->middleware(['auth', 'can:suppliers.manage']);
    }

    public function index(Request $request): View
    {
        $companyId = (int) auth()->user()->company_id;

        $query = SupplierInvoice::query()
            ->forCompany($companyId)
            ->with(['supplier:id,supplier_name'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $t = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($t) {
                $q->where('reference', 'like', $t)
                    ->orWhere('invoice_number', 'like', $t)
                    ->orWhereHas('supplier', function ($s) use ($t) {
                        $s->where('supplier_name', 'like', $t);
                    });
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'paid') {
                $query->whereColumn('paid_amount', '>=', 'total_amount');
            } elseif ($status === 'overdue') {
                $query->whereColumn('paid_amount', '<', 'total_amount')
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString());
            } elseif ($status === 'partially_paid') {
                $query->whereColumn('paid_amount', '<', 'total_amount')
                    ->where('paid_amount', '>', 0);
            } elseif ($status === 'pending') {
                $query->whereColumn('paid_amount', '<', 'total_amount')
                    ->where('paid_amount', '=', 0)
                    ->where(function ($q) {
                        $q->whereNull('due_date')->orWhereDate('due_date', '>=', now()->toDateString());
                    });
            }
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->input('date_to'));
        }

        $invoices = $query->paginate(15)->withQueryString();

        return view('supplier-invoices.index', [
            'invoices' => $invoices,
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function create(): View
    {
        $suppliers = Supplier::query()
            ->forUserTenant(auth()->user())
            ->orderBy('supplier_name')
            ->get(['id', 'supplier_name']);

        return view('supplier-invoices.create', [
            'suppliers' => $suppliers,
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = (int) auth()->user()->company_id;
        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'invoice_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('supplier_invoices')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:48', Rule::in(self::PAYMENT_METHODS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ((float) $data['paid_amount'] > (float) $data['total_amount']) {
            return redirect()->back()->withInput()->withErrors(['paid_amount' => 'Paid amount cannot exceed invoice total.']);
        }

        $invoice = DB::transaction(function () use ($data, $companyId) {
            $inv = SupplierInvoice::query()->create([
                'company_id' => $companyId,
                'supplier_id' => (int) $data['supplier_id'],
                'user_id' => auth()->id(),
                'reference' => null,
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'total_amount' => $data['total_amount'],
                'paid_amount' => $data['paid_amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $inv->reference = sprintf('VP-%d-%06d', $companyId, $inv->id);
            $inv->save();

            return $inv->fresh(['supplier']);
        });

        Log::channel('vendor_payments')->info('supplier_invoice.created', [
            'invoice_id' => $invoice->id,
            'company_id' => $companyId,
            'reference' => $invoice->reference,
            'supplier_id' => $invoice->supplier_id,
            'status' => $invoice->computedStatus(),
        ]);

        return redirect()->route('supplier-invoices.index')->with('success', 'Vendor payment record saved.');
    }

    public function edit(SupplierInvoice $supplierInvoice): View
    {
        $this->authorizeCompany($supplierInvoice);
        $suppliers = Supplier::query()
            ->forUserTenant(auth()->user())
            ->orderBy('supplier_name')
            ->get(['id', 'supplier_name']);

        return view('supplier-invoices.edit', [
            'invoice' => $supplierInvoice->load('supplier'),
            'suppliers' => $suppliers,
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function update(Request $request, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->authorizeCompany($supplierInvoice);
        $companyId = (int) auth()->user()->company_id;

        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'invoice_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('supplier_invoices')->where(fn ($q) => $q->where('company_id', $companyId))->ignore($supplierInvoice->id),
            ],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:48', Rule::in(self::PAYMENT_METHODS)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ((float) $data['paid_amount'] > (float) $data['total_amount']) {
            return redirect()->back()->withInput()->withErrors(['paid_amount' => 'Paid amount cannot exceed invoice total.']);
        }

        $supplierInvoice->update([
            'supplier_id' => (int) $data['supplier_id'],
            'invoice_number' => $data['invoice_number'],
            'invoice_date' => $data['invoice_date'],
            'due_date' => $data['due_date'] ?? null,
            'total_amount' => $data['total_amount'],
            'paid_amount' => $data['paid_amount'],
            'payment_method' => $data['payment_method'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $supplierInvoice->refresh();

        Log::channel('vendor_payments')->info('supplier_invoice.updated', [
            'invoice_id' => $supplierInvoice->id,
            'company_id' => $companyId,
            'reference' => $supplierInvoice->reference,
            'status' => $supplierInvoice->computedStatus(),
        ]);

        return redirect()->route('supplier-invoices.index')->with('success', 'Vendor payment updated.');
    }

    public function destroy(SupplierInvoice $supplierInvoice): RedirectResponse
    {
        $this->authorizeCompany($supplierInvoice);
        $id = $supplierInvoice->id;
        $ref = $supplierInvoice->reference;
        $companyId = (int) auth()->user()->company_id;
        $supplierInvoice->delete();

        Log::channel('vendor_payments')->info('supplier_invoice.deleted', [
            'invoice_id' => $id,
            'company_id' => $companyId,
            'reference' => $ref,
        ]);

        return redirect()->route('supplier-invoices.index')->with('success', 'Record removed.');
    }

    private function authorizeCompany(SupplierInvoice $supplierInvoice): void
    {
        if ((int) $supplierInvoice->company_id !== (int) auth()->user()->company_id) {
            abort(403);
        }
    }
}
