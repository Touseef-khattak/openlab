# Plan: AI report summaries with pluggable LLM providers

## Context
Pathologists verify results, but turning a page of numbers into a short, plain-language interpretation is manual. This feature adds an **AI-generated summary** of a visit's verified results, produced by a large language model. It must be **provider-agnostic**: the LLM that runs is decided by whichever API key is configured in ENV/config (Gemini, OpenAI, or Claude), so a lab can use any provider — or none. **Gemini (free tier) is implemented first**; OpenAI and Claude drivers plug into the same interface later.

## Confirmed decisions
- **Provider chosen by configured key** — an explicit `LLM_PROVIDER` override, else auto-detect by which `*_API_KEY` is present (precedence Gemini → OpenAI → Claude). Implement **Gemini first**.
- **Trigger:** on-demand — a "Generate AI summary" button on the verified visit page. No auto-generation, no per-visit token spend unless requested.
- **Placement:** visit page only for now. **Not** embedded in the patient PDF report (deferred).
- **Privacy (default, overridable):** de-identified payload — age, gender, and the result values/flags/ranges only; no name, CNIC, phone, or MRN. Toggle `LLM_SUMMARY_INCLUDE_IDENTIFIERS=false` by default.

## Architecture — a driver abstraction (the core of the feature)
One interface, one driver per provider, a manager that selects the driver from config. No provider-specific code leaks into controllers.

- `app/Services/Llm/LlmProvider.php` (interface): `generate(string $systemPrompt, string $userPrompt): string` + `name(): string`.
- Drivers in `app/Services/Llm/Drivers/`:
  - `GeminiDriver` — **implemented now.** Laravel `Http` POST to `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key=…`, body `{contents:[{parts:[{text}]}], systemInstruction:{parts:[{text}]}}`; read `candidates.0.content.parts.0.text`. Default model config `gemini-1.5-flash` (free tier; overridable — verify the current free model name in ENV).
  - `OpenAiDriver` — same interface; `Http` POST to `/v1/chat/completions`, model from config (e.g. `gpt-4o-mini`). Implemented when a key is provided.
  - `AnthropicDriver` — same interface; use the official `anthropic-ai/sdk` (`$client->messages->create(model: config, maxTokens: …, system: …, messages: […])`); default model config `claude-haiku-4-5` (cheap, fine for summaries) or `claude-opus-5`.
  - `FakeDriver` — returns a canned string; used in tests so no network call is made.
- `app/Services/Llm/LlmManager.php`: resolves the active driver — explicit `config('llm.provider')` if set, else first provider whose key is configured; throws a clear "no LLM configured" error otherwise. Bound as a singleton in a small `LlmServiceProvider` (or `AppServiceProvider`).
- `config/llm.php` (new): `provider` (nullable), per-provider `{ key, model, endpoint }`, `timeout`, `include_identifiers` (bool), `max_output_tokens`.
- `.env.example`: `LLM_PROVIDER=`, `GEMINI_API_KEY=`, `GEMINI_MODEL=gemini-1.5-flash`, `OPENAI_API_KEY=`, `OPENAI_MODEL=`, `ANTHROPIC_API_KEY=`, `ANTHROPIC_MODEL=`, `LLM_SUMMARY_INCLUDE_IDENTIFIERS=false`, `LLM_TIMEOUT=30`.

## Payload + prompt
- `app/Actions/BuildSummaryPayload.php`: reuse the verified-results shape (mirror `BuildSampleResultsPayload`) → a compact text block: per test, each parameter `name: value unit (flag, ref low–high)`. Prepend `Age: N, Gender: X`. Names/CNIC/MRN included only when `include_identifiers` is true.
- **System prompt (guardrails baked in):** "You are a clinical laboratory assistant. Write a concise, plain-language summary of these verified lab results for a clinician. Group by system, call out abnormal and critical values explicitly, and note when everything is within range. Do NOT diagnose, prescribe, or invent values not present. End with: 'AI-generated summary — for clinician review.'"
- Only verified/reported results are ever sent (same gate as the PDF report).

## Backend
- `SummaryController@store(Visit $visit)` (or a method on `ReportController`): `abort` unless the visit has verified results; build payload → `LlmManager->driver()->generate(...)`; persist; return back with the summary. Wrap the call in try/catch → friendly flash on API/timeout/no-key errors.
- **Storage:** columns on `visits` via a migration — `ai_summary` (text, nullable), `ai_summary_provider`, `ai_summary_model`, `ai_summary_generated_at`, `ai_summary_by` (user id). Regeneration overwrites; the write is captured by the existing `RecordsActivity` audit trait.
- **Permission:** new `results.summarize` (granted to admin + pathologist), gating the route. Route `POST visits/{visit}/summary` under it.
- Synchronous for v1 (button shows a spinner). Queue/job is a later option if latency is an issue.

## Frontend
- `visits/show.tsx`: an **AI Summary** card (shown when the visit has verified results and the user holds `results.summarize`). Empty state → "Generate AI summary" button (`router.post('/visits/{id}/summary')`, loading state); filled state → the summary text, provider/model + timestamp footer, and a "Regenerate" button. Share `results.summarize` via the existing `auth.permissions` prop so the `use-can` hook gates the button.

## Verification
- **Unit/feature tests** (no network — bind `FakeDriver`): manager picks the driver from the configured key and honors an explicit `LLM_PROVIDER`; de-identified payload excludes name/CNIC/MRN (and includes them when the toggle is on); `store` is gated by `results.summarize` and 409s without verified results; a generated summary persists to the visit and is shown via Inertia.
- **Manual (real Gemini):** set `GEMINI_API_KEY`, verify a visit, click Generate, confirm a sensible summary appears and is stored; unset the key → friendly "no LLM configured" message.
- Then `npm run build` + `php artisan test` (sequentially), reseed.

## Deferred
- OpenAI and Claude drivers (interface ready; implement when keys are used).
- Embedding the summary in the patient PDF report (with disclaimer).
- Auto-generate on verification; streaming; response caching; per-tenant provider config (arrives naturally with Phase 2 multi-tenancy).

## Key files
- `config/llm.php`, `.env.example` (config + keys)
- `app/Services/Llm/{LlmProvider.php, LlmManager.php, Drivers/GeminiDriver.php, Drivers/FakeDriver.php}` (abstraction + Gemini)
- `app/Actions/BuildSummaryPayload.php` (de-identified payload + prompt)
- `app/Http/Controllers/SummaryController.php`, `routes/web.php` (route under `results.summarize`)
- migration `..._add_ai_summary_to_visits_table.php`, `app/Models/Visit.php` (fillable/casts)
- `database/seeders/RolePermissionSeeder.php` (add `results.summarize`)
- `resources/js/pages/visits/show.tsx` (AI Summary card)
