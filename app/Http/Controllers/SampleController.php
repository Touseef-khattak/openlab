<?php

namespace App\Http\Controllers;

use App\Enums\SampleStatus;
use App\Models\Sample;
use Illuminate\Http\RedirectResponse;

class SampleController extends Controller
{
    public function collect(Sample $sample): RedirectResponse
    {
        if (! in_array(SampleStatus::Collected, $sample->status->allowedTransitions(), true)) {
            return back()->with('error', "Sample {$sample->sample_number} cannot be collected from status '{$sample->status->value}'.");
        }

        $sample->update([
            'status' => SampleStatus::Collected,
            'collected_at' => now(),
        ]);

        return back()->with('success', "Sample {$sample->sample_number} marked collected.");
    }
}
