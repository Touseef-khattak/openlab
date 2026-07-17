<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $patients = Patient::query()
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $q = $request->string('q')->trim();
                $query->where(fn ($w) => $w
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('cnic', 'like', "%{$q}%")
                    ->orWhere('mrn', 'like', "%{$q}%"));
            })
            ->withCount('visits')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('patients/index', [
            'patients' => $patients,
            'filters' => ['q' => $request->string('q')->trim()->value()],
        ]);
    }

    /**
     * Autocomplete for the front-desk visit screen: match by phone,
     * CNIC, MRN, or name and include the visit history summary.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->string('q')->trim();

        if ($q->length() < 3) {
            return response()->json([]);
        }

        $patients = Patient::query()
            ->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('cnic', 'like', "%{$q}%")
                ->orWhere('mrn', 'like', "%{$q}%"))
            ->withCount('visits')
            ->with(['visits' => fn ($visits) => $visits->latest('visited_at')->limit(1)])
            ->limit(10)
            ->get()
            ->map(fn (Patient $patient) => [
                'id' => $patient->id,
                'mrn' => $patient->mrn,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'cnic' => $patient->cnic,
                'gender' => $patient->gender,
                'dob' => $patient->dob?->toDateString(),
                'age_years' => $patient->age_years,
                'visits_count' => $patient->visits_count,
                'last_visit_at' => $patient->visits->first()?->visited_at?->toDateTimeString(),
            ]);

        return response()->json($patients);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = Patient::create($request->validated());
        $patient->assignMrn();

        return back()->with('success', "Patient {$patient->name} registered ({$patient->mrn}).");
    }

    public function update(StorePatientRequest $request, Patient $patient): RedirectResponse
    {
        $patient->update($request->validated());

        return back()->with('success', 'Patient updated.');
    }
}
