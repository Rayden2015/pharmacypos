<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $prescriptions = Prescription::query()
            ->with(['user:id,name'])
            ->latest()
            ->paginate(15);

        return view('pharmacy.prescriptions', compact('prescriptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_name' => 'required|string|max:255',
            'patient_phone' => 'nullable|string|max:50',
            'rx_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';
        $data['site_id'] = CurrentSite::id();

        Prescription::create($data);

        return redirect()->route('pharmacy.prescriptions')->with('success', 'Prescription added.');
    }

    public function update(Request $request, Prescription $prescription): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $prescription->status = $request->input('status');
        if ($prescription->status === 'completed') {
            $prescription->dispensed_at = now();
        } elseif ($prescription->status !== 'completed') {
            $prescription->dispensed_at = null;
        }
        $prescription->save();

        return redirect()->route('pharmacy.prescriptions')->with('success', 'Status updated.');
    }
}
