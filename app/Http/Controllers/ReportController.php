<?php

namespace App\Http\Controllers;

use App\Enums\SampleStatus;
use App\Models\Sample;
use App\Models\Visit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    /**
     * The lab report only ever contains verified results — an unverified
     * sample simply does not appear, and with none verified there is no
     * report at all.
     */
    public function show(Visit $visit): Response
    {
        $visit->load([
            'patient',
            'doctor:id,name,specialty',
            'samples' => fn ($q) => $q->whereIn('status', [SampleStatus::Verified, SampleStatus::Reported]),
            'samples.visitTests.test:id,code,name',
            'samples.visitTests.test.parameters.referenceRanges',
            'samples.visitTests.results.verifiedBy:id,name',
        ]);

        abort_if($visit->samples->isEmpty(), 409, 'No verified results to report yet.');

        $patient = $visit->patient;

        $sections = $visit->samples->map(fn (Sample $sample) => [
            'sample_number' => $sample->sample_number,
            'specimen_type' => $sample->specimen_type,
            'collected_at' => $sample->collected_at?->format('d M Y H:i'),
            'tests' => $sample->visitTests->map(function ($visitTest) use ($patient) {
                return [
                    'code' => $visitTest->test->code,
                    'name' => $visitTest->test->name,
                    'parameters' => $visitTest->test->parameters->map(function ($parameter) use ($visitTest, $patient) {
                        $range = $parameter->rangeFor($patient);
                        $result = $visitTest->results->firstWhere('test_parameter_id', $parameter->id);

                        return [
                            'name' => $parameter->name,
                            'value' => $result?->value,
                            'flag' => $result?->flag?->value,
                            'unit' => $parameter->unit,
                            'range_low' => $range?->low,
                            'range_high' => $range?->high,
                            'normal_text' => $range?->normal_text,
                        ];
                    })->values(),
                ];
            })->values(),
        ])->values();

        $verifier = $visit->samples
            ->flatMap(fn ($sample) => $sample->visitTests->flatMap->results)
            ->first(fn ($result) => $result->verifiedBy !== null);

        $pdf = Pdf::loadView('reports.lab-report', [
            'visit' => $visit,
            'patient' => $patient,
            'sections' => $sections,
            'verifierName' => $verifier?->verifiedBy?->name,
            'verifiedAt' => $verifier?->verified_at?->format('d M Y H:i'),
        ])->setPaper('a4');

        Sample::query()
            ->where('visit_id', $visit->id)
            ->where('status', SampleStatus::Verified)
            ->update(['status' => SampleStatus::Reported, 'reported_at' => now()]);

        return $pdf->stream("{$visit->visit_number}-report.pdf");
    }
}
