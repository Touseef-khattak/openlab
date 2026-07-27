<?php

use App\Actions\CreateVisit;
use App\Enums\SampleStatus;
use App\Models\Result;
use App\Models\Sample;
use App\Models\Test as LabTest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
});

function pathologist(): User
{
    $user = User::factory()->create();
    $user->assignRole('pathologist');

    return $user;
}

/** Creates a visit whose sample already has values and sits in `resulted`. */
function resultedSample(array $codes = ['ESR']): Sample
{
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $visit = app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Kiran Fatima', 'gender' => 'female', 'age_years' => 40],
        'doctor_id' => null,
        'test_ids' => LabTest::whereIn('code', $codes)->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => 0,
    ], $receptionist);

    $sample = $visit->samples->first();

    $tech = User::factory()->create();
    $tech->assignRole('technician');
    Result::whereHas('visitTest', fn ($q) => $q->where('sample_id', $sample->id))
        ->get()
        ->each(fn (Result $result) => $result->update([
            'value' => '15',
            'value_numeric' => 15,
            'entered_by' => $tech->id,
            'entered_at' => now(),
        ]));

    $sample->update(['status' => SampleStatus::Resulted]);

    return $sample->refresh();
}

test('technician cannot access verification', function () {
    $tech = User::factory()->create();
    $tech->assignRole('technician');
    $sample = resultedSample();

    $this->actingAs($tech)->get('/verification')->assertForbidden();
    $this->actingAs($tech)->post("/samples/{$sample->id}/verify")->assertForbidden();
});

test('pathologist sees the queue and verifies a sample', function () {
    $sample = resultedSample();
    $doctor = pathologist();

    $this->actingAs($doctor)->get('/verification')->assertOk();
    $this->actingAs($doctor)->get("/samples/{$sample->id}/verify")->assertOk();

    $this->actingAs($doctor)
        ->post("/samples/{$sample->id}/verify")
        ->assertRedirect('/verification');

    expect($sample->refresh()->status)->toBe(SampleStatus::Verified);

    $result = Result::whereHas('visitTest', fn ($q) => $q->where('sample_id', $sample->id))->first();
    expect($result->verified_by)->toBe($doctor->id)
        ->and($result->verified_at)->not->toBeNull();
});

test('a sample that is not resulted cannot be verified', function () {
    $sample = resultedSample();
    $sample->update(['status' => SampleStatus::InProgress]);

    $this->actingAs(pathologist())
        ->post("/samples/{$sample->id}/verify")
        ->assertStatus(409);
});

test('rejecting returns the sample to the bench', function () {
    $sample = resultedSample();

    $this->actingAs(pathologist())
        ->post("/samples/{$sample->id}/reject")
        ->assertRedirect('/verification');

    expect($sample->refresh()->status)->toBe(SampleStatus::InProgress);
});

test('report is unavailable before verification', function () {
    $sample = resultedSample();

    $this->actingAs(pathologist())
        ->get("/visits/{$sample->visit_id}/report")
        ->assertStatus(409);
});

test('report streams a pdf after verification and marks samples reported', function () {
    $sample = resultedSample();
    $doctor = pathologist();

    $this->actingAs($doctor)->post("/samples/{$sample->id}/verify");

    $response = $this->actingAs($doctor)->get("/visits/{$sample->visit_id}/report");
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');

    expect($sample->refresh()->status)->toBe(SampleStatus::Reported)
        ->and($sample->reported_at)->not->toBeNull();
});

test('receptionist can download the report but cannot verify', function () {
    $sample = resultedSample();
    $this->actingAs(pathologist())->post("/samples/{$sample->id}/verify");

    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $this->actingAs($receptionist)->post("/samples/{$sample->id}/verify")->assertForbidden();
    $this->actingAs($receptionist)->get("/visits/{$sample->visit_id}/report")->assertOk();
});
