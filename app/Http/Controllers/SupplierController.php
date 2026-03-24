<?php

namespace App\Http\Controllers;

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
        $suppliers = Supplier::query()->orderBy('supplier_name')->paginate(20);

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->validated($request);

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
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
     * @return array{supplier_name: string, address: string, mobile: string, email: string}
     */
    private function validated(Request $request): array
    {
        $v = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'address' => 'nullable|string|max:2000',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        return [
            'supplier_name' => $v['supplier_name'],
            'address' => $v['address'] ?? '',
            'mobile' => $v['mobile'] ?? '',
            'email' => $v['email'] ?? '',
        ];
    }
}
