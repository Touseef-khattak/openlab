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

        $response = Http::timeout($this->timeout)
            ->withQueryParameters(['key' => $this->config['key']])
            ->acceptJson()
            ->post($url, [
                'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => $this->maxOutputTokens,
                ],
            ]);

        if ($response->failed()) {
            $message = data_get($response->json(), 'error.message', "HTTP {$response->status()}");
            throw new LlmException("Gemini request failed: {$message}");
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (blank($text)) {
            throw new LlmException('Gemini returned an empty response.');
        }

        return trim($text);
    }
}
