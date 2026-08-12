<?php

namespace App\Http\Controllers;

use App\Actions\CreateVisit;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Doctor;
use App\Models\LabSetting;
use App\Models\Test;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    public function index(Request $request): Response
    {
        $visits = Visit::query()
            ->with(['patient:id,name,mrn,phone', 'doctor:id,name'])
            ->withCount(['samples as verified_samples_count' => fn ($q) => $q->whereIn('status', ['verified', 'reported'])])
            ->withCount('samples')
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $q = $request->string('q')->trim();
                $query->where(fn ($w) => $w
                    ->where('visit_number', 'like', "%{$q}%")
                    ->orWhereHas('patient', fn ($p) => $p
                        ->where('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('mrn', 'like', "%{$q}%")));
            })
            ->latest('visited_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('visits/index', [
            'visits' => $visits,
            'filters' => ['q' => $request->string('q')->trim()->value()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('visits/create', [
            'doctors' => Doctor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'specialty']),
            'tests' => Test::with('category:id,name')
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get(['id', 'test_category_id', 'code', 'name', 'price', 'specimen_type']),
        ]);
    }

    public function store(StoreVisitRequest $request, CreateVisit $createVisit): RedirectResponse
    {
        $visit = $createVisit->handle($request->validated(), $request->user());

        return redirect()
            ->route('visits.show', $visit)
            ->with('success', "Visit {$visit->visit_number} registered.");
    }

    public function show(Visit $visit): Response
    {
        $visit->load([
            'patient',
            'doctor:id,name,specialty',
            'creator:id,name',
            'samples',
            'visitTests.test:id,code,name,specimen_type',
            'visitTests.sample:id,sample_number,specimen_type,status',
        ]);

        return Inertia::render('visits/show', [
            'visit' => $visit,
            'branding' => LabSetting::current(),
        ]);
    }
}
