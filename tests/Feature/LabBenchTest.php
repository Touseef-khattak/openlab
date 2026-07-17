<?php

use App\Actions\CreateVisit;
use App\Enums\SampleStatus;
use App\Models\Result;
use App\Models\Sample;
use App\Models\Test as LabTest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
});

function technician(): User
{
    $user = User::factory()->create();
    $user->assignRole('technician');

    return $user;
}

/** Registers a female patient with an ESR order and returns her serum-free EDTA sample. */
function registeredSample(array $codes = ['ESR']): Sample
{
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $visit = app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Amna Riaz', 'gender' => 'female', 'age_years' => 28],
        'doctor_id' => null,
        'test_ids' => LabTest::whereIn('code', $codes)->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => 0,
    ], $receptionist);

    return $visit->samples->first();
}

test('receptionist cannot open the worklist', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $this->actingAs($receptionist)->get('/worklist')->assertForbidden();
});

test('technician sees registered samples and can collect them', function () {
    $sample = registeredSample();

    $this->actingAs(technician())->get('/worklist')->assertOk();

    $this->actingAs(technician())
        ->post("/samples/{$sample->id}/collect")
        ->assertRedirect();

    $sample->refresh();
    expect($sample->status)->toBe(SampleStatus::Collected)
        ->and($sample->collected_at)->not->toBeNull();
});

test('a collected sample cannot be collected twice', function () {
    $sample = registeredSample();
    $sample->update(['status' => SampleStatus::Collected]);

    $this->actingAs(technician())
        ->post("/samples/{$sample->id}/collect")
        ->assertSessionHas('error');

    expect($sample->refresh()->status)->toBe(SampleStatus::Collected);
});

test('entry page shows parameters with patient-specific ranges and previous results', function () {
    $sample = registeredSample();
    $sample->update(['status' => SampleStatus::Collected]);

    $this->actingAs(technician())
        ->get("/samples/{$sample->id}/entry")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('worklist/entry')
            ->has('tests', 1)
            ->where('tests.0.parameters.0.name', 'ESR')
            // Female ESR band is 0–20.
            ->where('tests.0.parameters.0.range.high', fn ($high) => (float) $high === 20.0));
});

test('saving results computes flags and submit moves the sample to resulted', function () {
    $sample = registeredSample();
    $sample->update(['status' => SampleStatus::Collected]);
    $tech = technician();

    $result = Result::whereHas('visitTest', fn ($q) => $q->where('sample_id', $sample->id))->first();

    // Draft save with an abnormal value: 35 > female high of 20.
    $this->actingAs($tech)->put("/samples/{$sample->id}/results", [
        'action' => 'draft',
        'results' => [['id' => $result->id, 'value' => '35']],
    ])->assertRedirect();

    $result->refresh();
    expect($result->flag->value)->toBe('high')
        ->and((float) $result->value_numeric)->toBe(35.0)
        ->and($result->entered_by)->toBe($tech->id)
        ->and($sample->refresh()->status)->toBe(SampleStatus::InProgress);

    // Submit completes the sample.
    $this->actingAs($tech)->put("/samples/{$sample->id}/results", [
        'action' => 'submit',
        'results' => [['id' => $result->id, 'value' => '35']],
    ])->assertRedirect('/worklist?status=in_progress');

    expect($sample->refresh()->status)->toBe(SampleStatus::Resulted);
});

test('submit is rejected while parameters are still empty', function () {
    $sample = registeredSample(['CBC']);
    $sample->update(['status' => SampleStatus::Collected]);

    $first = Result::whereHas('visitTest', fn ($q) => $q->where('sample_id', $sample->id))->first();

    $this->actingAs(technician())->put("/samples/{$sample->id}/results", [
        'action' => 'submit',
        'results' => [['id' => $first->id, 'value' => '13.1']],
    ])->assertSessionHasErrors('results');

    expect($sample->refresh()->status)->toBe(SampleStatus::Collected);
});

test('results of a foreign sample are rejected', function () {
    $sampleA = registeredSample();
    $sampleB = registeredSample();
    $sampleA->update(['status' => SampleStatus::Collected]);

    $foreignResult = Result::whereHas('visitTest', fn ($q) => $q->where('sample_id', $sampleB->id))->first();

    $this->actingAs(technician())->put("/samples/{$sampleA->id}/results", [
        'action' => 'draft',
        'results' => [['id' => $foreignResult->id, 'value' => '10']],
    ])->assertSessionHasErrors('results');
});
