<?php

namespace App\Services\Llm;

use App\Services\Llm\Drivers\FakeDriver;
use App\Services\Llm\Drivers\GeminiDriver;

class LlmManager
{
    /** Providers probed for a configured key, in precedence order. */
    private const AUTODETECT_ORDER = ['gemini', 'openai', 'anthropic'];

    public function __construct(private array $config) {}

    /**
     * Resolve the active provider: an explicit config('llm.provider') wins,
     * otherwise the first provider whose API key is configured.
     */
    public function provider(): LlmProvider
    {
        $name = $this->config['provider'] ?: $this->autodetect();

        if (blank($name)) {
            throw new LlmException('No LLM provider is configured. Set an API key (e.g. GEMINI_API_KEY) or LLM_PROVIDER.');
        }

        return $this->make($name);
    }

    public function configured(): bool
    {
        return filled($this->config['provider'] ?: $this->autodetect());
    }

    private function autodetect(): ?string
    {
        foreach (self::AUTODETECT_ORDER as $provider) {
            if (filled($this->config[$provider]['key'] ?? null)) {
                return $provider;
            }
        }

        return null;
    }

    private function make(string $name): LlmProvider
    {
        return match ($name) {
            'gemini' => new GeminiDriver(
                $this->config['gemini'],
                $this->config['timeout'],
                $this->config['max_output_tokens'],
                $this->config['verify_ssl'] ?? true,
            ),
            'fake' => new FakeDriver(),
            // 'openai' and 'anthropic' drivers plug in here (see plan) once their keys are used.
            default => throw new LlmException("Unsupported LLM provider: {$name}."),
        };
    }
}
