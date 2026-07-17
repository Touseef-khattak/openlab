<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DoctorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('doctors/index', [
            'doctors' => Doctor::withCount('visits')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
    {
        Doctor::create($request->validated());

        return back()->with('success', 'Doctor added.');
    }

    public function update(StoreDoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $doctor->update($request->validated());

        return back()->with('success', 'Doctor updated.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        $doctor->delete();

        return back()->with('success', 'Doctor removed.');
    }
}
