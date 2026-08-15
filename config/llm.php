<?php

return [
    // Explicit provider override. When null, the first provider below with a
    // configured API key is used (order: gemini, openai, anthropic).
    'provider' => env('LLM_PROVIDER'),

    'timeout' => (int) env('LLM_TIMEOUT', 30),
    'max_output_tokens' => (int) env('LLM_MAX_OUTPUT_TOKENS', 800),

    // Leave true. Set false ONLY for local dev when PHP has no CA bundle
    // (cURL error 60). The correct fix is to configure curl.cainfo in php.ini.
    'verify_ssl' => (bool) env('LLM_VERIFY_SSL', true),

    // When false, report payloads sent to the LLM are de-identified
    // (age + gender + result values only; no name, CNIC, phone or MRN).
    'include_identifiers' => (bool) env('LLM_SUMMARY_INCLUDE_IDENTIFIERS', false),

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'endpoint' => env('OPENAI_ENDPOINT', 'https://api.openai.com/v1'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
    ],
];
