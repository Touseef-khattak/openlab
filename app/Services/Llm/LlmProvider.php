<?php

namespace App\Services\Llm;

interface LlmProvider
{
    /**
     * Generate a completion from a system instruction and a user prompt.
     * Implementations throw LlmException on any transport or API failure.
     */
    public function generate(string $systemPrompt, string $userPrompt): string;

    /** Machine name of the provider, e.g. "gemini". */
    public function name(): string;
}
