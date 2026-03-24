<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Support\CurrentSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $doctors = Doctor::query()
            ->forCurrentSiteContext()
            ->when($search !== '', function ($q) use ($search) {
                $term = '%'.$search.'%';
                $q->where(function ($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                        ->orWhere('specialty', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('license_number', 'like', $term)
                        ->orWhere('hospital_or_clinic', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pharmacy.doctors.index', compact('doctors', 'search'));
    }

    public function create(): View
    {
        return view('pharmacy.doctors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedDoctorData($request);
        $data['site_id'] = CurrentSite::id();

        Doctor::create($data);

        return redirect()->route('pharmacy.doctors.index')->with('success', __('Doctor saved.'));
    }

    public function edit(Doctor $doctor): View
    {
        $this->authorizeDoctor($doctor);

        return view('pharmacy.doctors.edit', compact('doctor'));
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $this->authorizeDoctor($doctor);

        $data = $this->validatedDoctorData($request);
        $doctor->update($data);

        return redirect()->route('pharmacy.doctors.index')->with('success', __('Doctor updated.'));
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        $this->authorizeDoctor($doctor);

        if ($doctor->prescriptions()->exists()) {
            return redirect()->route('pharmacy.doctors.index')
                ->with('error', __('Cannot delete: prescriptions are linked to this doctor.'));
        }

        $doctor->delete();

        return redirect()->route('pharmacy.doctors.index')->with('success', __('Doctor removed.'));
    }

    private function validatedDoctorData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'license_number' => 'nullable|string|max:120',
            'hospital_or_clinic' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    private function authorizeDoctor(Doctor $doctor): void
    {
        $query = Doctor::query()->forCurrentSiteContext()->whereKey($doctor->getKey());
        if (! $query->exists()) {
            abort(404);
        }
    }
}
