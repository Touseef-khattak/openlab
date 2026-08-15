<?php

namespace App\Actions;

use App\Enums\SampleStatus;
use App\Models\Visit;

class BuildSummaryPayload
{
    private const SYSTEM_PROMPT = <<<'TXT'
        You are a clinical laboratory assistant. Write a concise, plain-language summary of
        these verified lab results for a clinician. Group findings logically, call out abnormal
        and critical values explicitly, and state clearly when results are within the normal range.
        Do NOT provide a diagnosis, treatment, or medication advice, and do NOT invent any values
        that are not listed. End with the line: "AI-generated summary — for clinician review."
        TXT;

    public function __construct(private bool $includeIdentifiers) {}

    /**
     * Build the system + user prompts from a visit's verified results.
     *
     * @return array{system: string, user: string}
     */
    public function handle(Visit $visit): array
    {
        $visit->loadMissing([
            'patient',
            'samples' => fn ($q) => $q->whereIn('status', [SampleStatus::Verified, SampleStatus::Reported]),
            'samples.visitTests.test:id,code,name',
            'samples.visitTests.test.parameters.referenceRanges',
            'samples.visitTests.results',
        ]);

        $patient = $visit->patient;

        $lines = [];
        if ($this->includeIdentifiers) {
            $lines[] = "Patient: {$patient->name}" . ($patient->mrn ? " (MRN {$patient->mrn})" : '');
        }
        $lines[] = 'Age: ' . ($this->age($visit) ?? 'unknown') . ', Gender: ' . ($patient->gender->value ?? $patient->gender);
        $lines[] = '';

        foreach ($visit->samples as $sample) {
            foreach ($sample->visitTests as $visitTest) {
                $rows = [];
                foreach ($visitTest->test->parameters as $parameter) {
                    $result = $visitTest->results->firstWhere('test_parameter_id', $parameter->id);
                    if ($result === null || blank($result->value)) {
                        continue;
                    }

                    $range = $parameter->rangeFor($patient);
                    $ref = $range && $range->low !== null && $range->high !== null
                        ? " (ref {$range->low}-{$range->high})"
                        : '';
                    $flag = $result->flag && $result->flag->value !== 'normal'
                        ? ' [' . strtoupper(str_replace('_', ' ', $result->flag->value)) . ']'
                        : '';
                    $unit = $parameter->unit ? " {$parameter->unit}" : '';

                    $rows[] = "- {$parameter->name}: {$result->value}{$unit}{$flag}{$ref}";
                }

                if ($rows !== []) {
                    $lines[] = $visitTest->test->name;
                    $lines = array_merge($lines, $rows);
                    $lines[] = '';
                }
            }
        }

        return [
            'system' => self::SYSTEM_PROMPT,
            'user' => trim(implode("\n", $lines)),
        ];
    }

    private function age(Visit $visit): ?int
    {
        $patient = $visit->patient;

        return $patient->dob?->age ?? $patient->age_years;
    }
}
