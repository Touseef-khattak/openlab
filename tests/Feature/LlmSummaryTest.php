<?php

use App\Actions\BuildSummaryPayload;
use App\Actions\CreateVisit;
use App\Enums\SampleStatus;
use App\Models\Result;
use App\Models\Test as LabTest;
use App\Models\User;
use App\Models\Visit;
use App\Services\Llm\Drivers\FakeDriver;
use App\Services\Llm\LlmException;
use App\Services\Llm\LlmManager;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
    config()->set('llm.provider', 'fake');
});

function pathologistUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('pathologist');

    return $user;
}

/** A visit with one ESR result entered and the sample verified. */
function verifiedVisit(string $value = '35'): Visit
{
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $visit = app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Nadia Kamal', 'gender' => 'female', 'age_years' => 29],
        'doctor_id' => null,
        'test_ids' => LabTest::whereIn('code', ['ESR'])->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => 0,
    ], $receptionist);

    $sample = $visit->samples->first();
    Result::whereHas('visitTest', fn ($q) => $q->where('sample_id', $sample->id))
        ->get()
        ->each(fn (Result $r) => $r->update(['value' => $value, 'value_numeric' => (float) $value]));
    $sample->update(['status' => SampleStatus::Verified]);

    return $visit->refresh();
}

test('the manager auto-selects a provider by configured key', function () {
    config()->set('llm.provider', null);
    config()->set('llm.gemini.key', 'test-key');
    config()->set('llm.openai.key', null);
    config()->set('llm.anthropic.key', null);

    $manager = new LlmManager(config('llm'));
    expect($manager->configured())->toBeTrue()
        ->and($manager->provider()->name())->toBe('gemini');
});

test('the manager throws when nothing is configured', function () {
    config()->set('llm.provider', null);
    config()->set('llm.gemini.key', null);
    config()->set('llm.openai.key', null);
    config()->set('llm.anthropic.key', null);

    $manager = new LlmManager(config('llm'));
    expect($manager->configured())->toBeFalse();
    $manager->provider();
})->throws(LlmException::class);

test('the payload is de-identified by default', function () {
    $visit = verifiedVisit();
    $payload = (new BuildSummaryPayload(false))->handle($visit);

    expect($payload['user'])->not->toContain('Nadia Kamal')
        ->and($payload['user'])->toContain('Age: 29')
        ->and($payload['user'])->toContain('ESR');
});

test('the payload includes identifiers when enabled', function () {
    $visit = verifiedVisit();
    $payload = (new BuildSummaryPayload(true))->handle($visit);

    expect($payload['user'])->toContain('Nadia Kamal');
});

test('generating a summary stores it on the visit', function () {
    $visit = verifiedVisit();

    $this->actingAs(pathologistUser())
        ->post("/visits/{$visit->id}/summary")
        ->assertRedirect();

    $visit->refresh();
    expect($visit->ai_summary)->toContain('clinician review')
        ->and($visit->ai_summary_provider)->toBe('fake')
        ->and($visit->ai_summary_generated_at)->not->toBeNull();
});

test('summarizing requires the results.summarize permission', function () {
    $visit = verifiedVisit();
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $this->actingAs($receptionist)
        ->post("/visits/{$visit->id}/summary")
        ->assertForbidden();
});

test('a visit with no verified results cannot be summarized', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');
    $visit = app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Unverified Patient', 'gender' => 'male', 'age_years' => 50],
        'doctor_id' => null,
        'test_ids' => LabTest::whereIn('code', ['ESR'])->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => 0,
    ], $receptionist);

    $this->actingAs(pathologistUser())
        ->post("/visits/{$visit->id}/summary")
        ->assertStatus(409);
});
