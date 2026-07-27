<?php

namespace App\Actions;

use App\Models\Result;
use App\Models\Sample;
use App\Models\TestParameter;

/**
 * Assembles the per-parameter payload (patient-specific range, current
 * value/flag, previous result) shared by the entry and verification screens.
 */
class BuildSampleResultsPayload
{
    public function handle(Sample $sample): array
    {
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

        return [
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
        ];
    }
}
