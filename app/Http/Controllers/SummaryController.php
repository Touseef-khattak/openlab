<?php

namespace App\Http\Controllers;

use App\Actions\BuildSummaryPayload;
use App\Enums\SampleStatus;
use App\Models\Visit;
use App\Services\Llm\LlmException;
use App\Services\Llm\LlmManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function store(Request $request, Visit $visit, LlmManager $llm): RedirectResponse
    {
        $hasVerified = $visit->samples()
            ->whereIn('status', [SampleStatus::Verified, SampleStatus::Reported])
            ->exists();

        abort_unless($hasVerified, 409, 'The visit has no verified results to summarize.');

        try {
            $provider = $llm->provider();
            $payload = (new BuildSummaryPayload((bool) config('llm.include_identifiers')))->handle($visit);
            $summary = $provider->generate($payload['system'], $payload['user']);
        } catch (LlmException $e) {
            return back()->with('error', "Could not generate summary: {$e->getMessage()}");
        }

        $visit->update([
            'ai_summary' => $summary,
            'ai_summary_provider' => $provider->name(),
            'ai_summary_model' => config("llm.{$provider->name()}.model"),
            'ai_summary_generated_at' => now(),
            'ai_summary_by' => $request->user()->id,
        ]);

        return back()->with('success', 'AI summary generated.');
    }
}
