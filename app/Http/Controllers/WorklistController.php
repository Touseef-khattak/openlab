<?php

namespace App\Http\Controllers;

use App\Enums\SampleStatus;
use App\Models\Sample;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorklistController extends Controller
{
    public function index(Request $request): Response
    {
        $status = SampleStatus::tryFrom($request->string('status')->value()) ?? SampleStatus::Registered;

        $samples = Sample::query()
            ->where('status', $status)
            ->with([
                'visit:id,visit_number,visited_at,patient_id',
                'visit.patient:id,name,mrn,gender,dob,age_years',
                'visitTests.test:id,code,name',
            ])
            ->oldest('created_at')
            ->paginate(20)
            ->withQueryString();

        $counts = Sample::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('worklist/index', [
            'samples' => $samples,
            'counts' => $counts,
            'activeStatus' => $status->value,
        ]);
    }
}
