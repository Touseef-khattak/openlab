<?php

namespace App\Http\Controllers;

use App\Actions\BuildSampleResultsPayload;
use App\Enums\ResultType;
use App\Enums\SampleStatus;
use App\Models\Result;
use App\Models\Sample;
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

    public function show(Sample $sample, BuildSampleResultsPayload $payload): Response
    {
        abort_unless(in_array($sample->status, self::EDITABLE, true), 409,
            "Results for a '{$sample->status->value}' sample cannot be edited.");

        return Inertia::render('worklist/entry', $payload->handle($sample));
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
