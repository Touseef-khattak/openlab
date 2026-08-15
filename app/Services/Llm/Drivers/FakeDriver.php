<?php

namespace App\Services\Llm\Drivers;

use App\Services\Llm\LlmProvider;

/**
 * Deterministic offline driver for tests and local development — makes no
 * network call. Selected when the active provider is "fake".
 */
class FakeDriver implements LlmProvider
{
    public function name(): string
    {
        return 'fake';
    }

    public function generate(string $systemPrompt, string $userPrompt): string
    {
        return "Test summary of the provided results.\n\nAI-generated summary — for clinician review.";
    }
}
