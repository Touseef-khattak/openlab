<?php

namespace App\Http\Controllers;

use App\Actions\BuildSampleResultsPayload;
use App\Enums\SampleStatus;
use App\Models\Sample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function index(): Response
    {
        $samples = Sample::query()
            ->where('status', SampleStatus::Resulted)
            ->with([
                'visit:id,visit_number,visited_at,patient_id',
                'visit.patient:id,name,mrn,gender,dob,age_years',
                'visitTests.test:id,code,name',
                'visitTests.results:id,visit_test_id,flag,entered_at',
            ])
            ->oldest('updated_at')
            ->paginate(20);

        $rows = $samples->through(function (Sample $sample) {
            $results = $sample->visitTests->flatMap->results;

            return [
                'id' => $sample->id,
                'sample_number' => $sample->sample_number,
                'specimen_type' => $sample->specimen_type,
                'visit' => $sample->visit,
                'tests' => $sample->visitTests->map(fn ($visitTest) => $visitTest->test->code)->values(),
                'abnormal_count' => $results->filter(fn ($r) => $r->flag !== null && $r->flag->value !== 'normal')->count(),
                'critical_count' => $results->filter(fn ($r) => $r->flag !== null && str_starts_with($r->flag->value, 'critical'))->count(),
                'entered_at' => $results->max('entered_at')?->toDateTimeString(),
            ];
        });

        return Inertia::render('verification/index', ['samples' => $rows]);
    }

    public function show(Sample $sample, BuildSampleResultsPayload $payload): Response
    {
        abort_unless($sample->status === SampleStatus::Resulted, 409,
            "Sample is '{$sample->status->value}', not awaiting verification.");

        return Inertia::render('verification/show', $payload->handle($sample));
    }

    public function verify(Request $request, Sample $sample): RedirectResponse
    {
        abort_unless($sample->status === SampleStatus::Resulted, 409,
            "Sample is '{$sample->status->value}', not awaiting verification.");

        DB::transaction(function () use ($request, $sample) {
            $sample->visitTests->each(fn ($visitTest) => $visitTest->results()->update([
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]));

            $sample->update(['status' => SampleStatus::Verified]);
        });

        return redirect()->route('verification.index')
            ->with('success', "Sample {$sample->sample_number} verified and signed off.");
    }

    public function reject(Request $request, Sample $sample): RedirectResponse
    {
        abort_unless($sample->status === SampleStatus::Resulted, 409,
            "Sample is '{$sample->status->value}', not awaiting verification.");

        $sample->update(['status' => SampleStatus::InProgress]);

        return redirect()->route('verification.index')
            ->with('success', "Sample {$sample->sample_number} returned to the bench.");
    }
}
