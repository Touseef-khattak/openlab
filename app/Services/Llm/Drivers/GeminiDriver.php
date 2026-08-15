<?php

namespace App\Services\Llm\Drivers;

use App\Services\Llm\LlmException;
use App\Services\Llm\LlmProvider;
use Illuminate\Support\Facades\Http;

class GeminiDriver implements LlmProvider
{
    /**
     * @param array{key: ?string, model: string, endpoint: string} $config
     */
    public function __construct(
        private array $config,
        private int $timeout = 30,
        private int $maxOutputTokens = 800,
        private bool $verifySsl = true,
    ) {}

    public function name(): string
    {
        return 'gemini';
    }

    public function generate(string $systemPrompt, string $userPrompt): string
    {
        if (blank($this->config['key'] ?? null)) {
            throw new LlmException('Gemini API key is not configured.');
        }

        $url = rtrim($this->config['endpoint'], '/') . "/models/{$this->config['model']}:generateContent";

        $request = Http::timeout($this->timeout)
            ->withQueryParameters(['key' => $this->config['key']])
            ->acceptJson();

        if (! $this->verifySsl) {
            $request = $request->withoutVerifying();
        }

        $generationConfig = [
            'temperature' => 0.2,
            'maxOutputTokens' => $this->maxOutputTokens,
        ];

        // Gemini 2.5 models "think" by default and thinking tokens count against
        // maxOutputTokens, which truncates the visible answer. Set
        // GEMINI_THINKING_BUDGET=0 to disable it. Only sent when configured —
        // 1.5-era models reject thinkingConfig.
        if (($this->config['thinking_budget'] ?? null) !== null) {
            $generationConfig['thinkingConfig'] = ['thinkingBudget' => (int) $this->config['thinking_budget']];
        }

        $response = $request->post($url, [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
            'generationConfig' => $generationConfig,
        ]);

        if ($response->failed()) {
            $message = data_get($response->json(), 'error.message', "HTTP {$response->status()}");
            throw new LlmException("Gemini request failed: {$message}");
        }

        $json = $response->json();
        $text = collect(data_get($json, 'candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');
        $finishReason = data_get($json, 'candidates.0.finishReason');

        if (blank($text)) {
            $reason = $finishReason ? " (finishReason: {$finishReason})" : '';
            throw new LlmException(
                "Gemini returned no text{$reason}. If the model is a 2.5 'thinking' model, set GEMINI_THINKING_BUDGET=0 or raise LLM_MAX_OUTPUT_TOKENS."
            );
        }

        return trim($text);
    }
}
