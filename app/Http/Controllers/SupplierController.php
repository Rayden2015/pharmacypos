<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\StockReceipt;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:suppliers.manage']);
    }

    public function index(): View
    {
        $suppliers = Supplier::query()
            ->forUserTenant(auth()->user())
            ->orderBy('supplier_name')
            ->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        $companies = auth()->user()->isSuperAdmin()
            ? Company::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        return view('suppliers.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        $rules = $this->supplierFieldRules();
        if ($viewer->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        } elseif (! $viewer->company_id) {
            abort(403);
        }

        $v = $request->validate($rules);
        $data = $this->payloadFromValidated($v, $viewer);

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        $companies = auth()->user()->isSuperAdmin()
            ? Company::query()->orderBy('company_name')->get(['id', 'company_name'])
            : collect();

        return view('suppliers.edit', compact('supplier', 'companies'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $viewer = $request->user();
        $rules = $this->supplierFieldRules();
        if ($viewer->isSuperAdmin()) {
            $rules['company_id'] = 'required|exists:companies,id';
        }

        $v = $request->validate($rules);
        $data = $this->payloadFromValidated($v, $viewer);
        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('delete', $supplier);

        if (StockReceipt::query()->where('supplier_id', $supplier->id)->exists()) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Cannot delete: stock receipts reference this supplier.');
        }

        if ($supplier->preferredByProducts()->exists()) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Cannot delete: medicines list this supplier as preferred vendor.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    /**
     * @return array<string, string>
     */
    private function supplierFieldRules(): array
    {
        return [
            'supplier_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:2000',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ];
    }

    /**
     * @param  array<string, mixed>  $v
     * @return array{supplier_name: string, address: string, mobile: string, email: string, company_id: int}
     */
    private function payloadFromValidated(array $v, \App\Models\User $viewer): array
    {
        $companyId = $viewer->isSuperAdmin()
            ? (int) $v['company_id']
            : (int) ($viewer->company_id ?? 0);

        return [
            'supplier_name' => $v['supplier_name'],
            'address' => $v['address'] ?? '',
            'mobile' => $v['mobile'] ?? '',
            'email' => $v['email'] ?? '',
            'company_id' => $companyId,
        ];
    }
}
