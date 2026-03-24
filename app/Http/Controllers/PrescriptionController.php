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

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'doctor_id' => $request->query('doctor_id'),
        ];

        $query = Prescription::query()
            ->forCurrentSiteContext()
            ->with(['user:id,name', 'doctor:id,name,specialty']);

        if ($filters['status'] !== null && $filters['status'] !== '' && in_array($filters['status'], ['pending', 'completed', 'cancelled'], true)) {
            $query->where('status', $filters['status']);
        }

        if ($filters['doctor_id'] !== null && $filters['doctor_id'] !== '') {
            $query->where('doctor_id', (int) $filters['doctor_id']);
        }

        if ($filters['q'] !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('patient_name', 'like', $term)
                    ->orWhere('rx_number', 'like', $term)
                    ->orWhere('patient_phone', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        $prescriptions = $query->latest()->paginate(15)->withQueryString();

        $doctors = Doctor::query()
            ->forCurrentSiteContext()
            ->orderBy('name')
            ->get(['id', 'name', 'specialty']);

        $doctorCount = $doctors->count();

        return view('pharmacy.prescriptions', compact('prescriptions', 'doctors', 'filters', 'doctorCount'));
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
