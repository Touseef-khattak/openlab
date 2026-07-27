<?php

use App\Actions\CreateVisit;
use App\Models\Patient;
use App\Models\Test as LabTest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
});

test('dashboard reports live counts and recent visits', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Ali Raza', 'gender' => 'male', 'age_years' => 50],
        'doctor_id' => null,
        'test_ids' => LabTest::where('code', 'ESR')->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => 200,
    ], $receptionist);

    $this->actingAs($receptionist)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('stats.visits_today', 1)
            ->where('stats.pending_collection', 1)
            ->where('stats.patients_total', 1)
            ->has('recentVisits', 1));
});

test('clinical writes are recorded in the activity log', function () {
    $patient = Patient::factory()->create();
    $patient->assignMrn();

    $entry = Activity::where('subject_type', Patient::class)
        ->where('subject_id', $patient->id)
        ->where('event', 'created')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->log_name)->toBe('Patient');
});
