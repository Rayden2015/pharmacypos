<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Prescription;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class PrescriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $prescriptions = Prescription::query()
            ->with(['user:id,name', 'doctor:id,name,specialty'])
            ->latest()
            ->paginate(15);

        $doctors = Doctor::query()
            ->forCurrentSiteContext()
            ->orderBy('name')
            ->get(['id', 'name', 'specialty']);

        return view('pharmacy.prescriptions', compact('prescriptions', 'doctors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_name' => 'required|string|max:255',
            'patient_phone' => 'nullable|string|max:50',
            'rx_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:5000',
            'doctor_id' => [
                'nullable',
                'integer',
                Rule::exists('doctors', 'id')->where(function ($query) {
                    $query->where('site_id', CurrentSite::id());
                }),
            ],
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
