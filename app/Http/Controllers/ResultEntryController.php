<?php

namespace App\Http\Controllers;

use App\Enums\ResultType;
use App\Enums\SampleStatus;
use App\Models\Result;
use App\Models\Sample;
use App\Models\TestParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ResultEntryController extends Controller
{
    /** Statuses whose results may still be edited (verification locks them). */
    private const EDITABLE = [SampleStatus::Collected, SampleStatus::InProgress, SampleStatus::Resulted];

    public function show(Sample $sample): Response
    {
        abort_unless(in_array($sample->status, self::EDITABLE, true), 409,
            "Results for a '{$sample->status->value}' sample cannot be edited.");

        $sample->load([
            'visit:id,visit_number,visited_at,patient_id',
            'visit.patient',
            'visitTests.test:id,code,name',
            'visitTests.test.parameters.referenceRanges',
            'visitTests.results',
        ]);

        $patient = $sample->visit->patient;

        $tests = $sample->visitTests->map(function ($visitTest) use ($patient, $sample) {
            return [
                'visit_test_id' => $visitTest->id,
                'code' => $visitTest->test->code,
                'name' => $visitTest->test->name,
                'parameters' => $visitTest->test->parameters->map(function (TestParameter $parameter) use ($visitTest, $patient, $sample) {
                    $range = $parameter->rangeFor($patient);
                    $result = $visitTest->results->firstWhere('test_parameter_id', $parameter->id);
                    $previous = Result::query()
                        ->where('test_parameter_id', $parameter->id)
                        ->whereNotNull('value')
                        ->whereHas('visitTest.visit', fn ($visit) => $visit
                            ->where('patient_id', $patient->id)
                            ->where('id', '!=', $sample->visit_id))
                        ->latest('entered_at')
                        ->first();

                    return [
                        'result_id' => $result?->id,
                        'name' => $parameter->name,
                        'unit' => $parameter->unit,
                        'result_type' => $parameter->result_type->value,
                        'decimals' => $parameter->decimals,
                        'options' => $parameter->options,
                        'range' => $range === null ? null : [
                            'low' => $range->low,
                            'high' => $range->high,
                            'critical_low' => $range->critical_low,
                            'critical_high' => $range->critical_high,
                            'normal_text' => $range->normal_text,
                        ],
                        'value' => $result?->value,
                        'flag' => $result?->flag?->value,
                        'previous' => $previous === null ? null : [
                            'value' => $previous->value,
                            'flag' => $previous->flag?->value,
                            'entered_at' => $previous->entered_at?->toDateTimeString(),
                        ],
                    ];
                })->values(),
            ];
        })->values();

        return Inertia::render('worklist/entry', [
            'sample' => [
                'id' => $sample->id,
                'sample_number' => $sample->sample_number,
                'specimen_type' => $sample->specimen_type,
                'status' => $sample->status->value,
                'visit_number' => $sample->visit->visit_number,
                'visited_at' => $sample->visit->visited_at->toDateTimeString(),
            ],
            'patient' => [
                'name' => $patient->name,
                'mrn' => $patient->mrn,
                'gender' => $patient->gender,
                'dob' => $patient->dob?->toDateString(),
                'age_years' => $patient->age_years,
            ],
            'tests' => $tests,
        ]);
    }

    public function update(Request $request, Sample $sample): RedirectResponse
    {
        abort_unless(in_array($sample->status, self::EDITABLE, true), 409,
            "Results for a '{$sample->status->value}' sample cannot be edited.");

        $validated = $request->validate([
            'action' => ['required', Rule::in(['draft', 'submit'])],
            'results' => ['required', 'array'],
            'results.*.id' => ['required', 'integer'],
            'results.*.value' => ['nullable', 'string', 'max:255'],
        ]);

        $results = Result::query()
            ->whereIn('id', collect($validated['results'])->pluck('id'))
            ->whereHas('visitTest', fn ($q) => $q->where('sample_id', $sample->id))
            ->with('parameter.referenceRanges')
            ->get()
            ->keyBy('id');

        if ($results->count() !== count($validated['results'])) {
            throw ValidationException::withMessages(['results' => 'One or more results do not belong to this sample.']);
        }

        $patient = $sample->visit->patient;
        $filled = 0;

        foreach ($validated['results'] as $entry) {
            $result = $results[$entry['id']];
            $value = trim((string) ($entry['value'] ?? ''));

            if ($value === '') {
                $result->update([
                    'value' => null, 'value_numeric' => null, 'flag' => null,
                    'entered_by' => null, 'entered_at' => null,
                ]);
                continue;
            }

            $numeric = null;
            $flag = null;
            if ($result->parameter->result_type === ResultType::Numeric && is_numeric($value)) {
                $numeric = (float) $value;
                $flag = $result->parameter->rangeFor($patient)?->flagFor($numeric);
            }

            $result->update([
                'value' => $value,
                'value_numeric' => $numeric,
                'flag' => $flag,
                'entered_by' => $request->user()->id,
                'entered_at' => now(),
            ]);
            $filled++;
        }

        if ($validated['action'] === 'submit') {
            $pending = Result::query()
                ->whereHas('visitTest', fn ($q) => $q->where('sample_id', $sample->id))
                ->whereNull('value')
                ->count();

            if ($pending > 0) {
                throw ValidationException::withMessages([
                    'results' => "Cannot submit: {$pending} parameter(s) still have no value.",
                ]);
            }

            $sample->update(['status' => SampleStatus::Resulted]);

            return redirect()->route('worklist.index', ['status' => 'in_progress'])
                ->with('success', "Sample {$sample->sample_number} submitted for verification.");
        }

        if ($sample->status === SampleStatus::Collected && $filled > 0) {
            $sample->update(['status' => SampleStatus::InProgress]);
        }

        return back()->with('success', 'Results saved.');
    }
}
