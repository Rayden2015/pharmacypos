<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManufacturerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $manufacturers = Manufacturer::query()->orderBy('name')->paginate(20);

        return view('manufacturers.index', compact('manufacturers'));
    }

    public function create(): View
    {
        return view('manufacturers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:manufacturers,name',
            'address' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        Manufacturer::create($data);

        return redirect()->route('manufacturers.index')->with('success', 'Manufacturer created.');
    }

    public function edit(Manufacturer $manufacturer): View
    {
        return view('manufacturers.edit', compact('manufacturer'));
    }

    public function update(Request $request, Manufacturer $manufacturer): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:manufacturers,name,'.$manufacturer->id,
            'address' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $manufacturer->update($data);

        return redirect()->route('manufacturers.index')->with('success', 'Manufacturer updated.');
    }

    public function destroy(Manufacturer $manufacturer): RedirectResponse
    {
        if ($manufacturer->products()->exists()) {
            return redirect()->route('manufacturers.index')
                ->with('error', 'Cannot delete: medicines are still linked to this manufacturer.');
        }

        $manufacturer->delete();

        return redirect()->route('manufacturers.index')->with('success', 'Manufacturer deleted.');
    }
}
