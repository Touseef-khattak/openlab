<?php

use App\Models\Patient;
use App\Models\Test as LabTest;
use App\Models\User;
use App\Models\Visit;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('receptionist can open all front desk pages', function () {
    $receptionist = userWithRole('receptionist');

    $this->actingAs($receptionist)->get('/visits')->assertOk();
    $this->actingAs($receptionist)->get('/visits/create')->assertOk();
    $this->actingAs($receptionist)->get('/patients')->assertOk();
    $this->actingAs($receptionist)->get('/doctors')->assertOk();
});

test('technician cannot open the new visit page', function () {
    $this->actingAs(userWithRole('technician'))
        ->get('/visits/create')
        ->assertForbidden();
});

test('registering a visit creates patient, snapshot prices, samples and pending results', function () {
    $receptionist = userWithRole('receptionist');
    $cbc = LabTest::where('code', 'CBC')->first();
    $glucose = LabTest::where('code', 'GLU-F')->first();

    $response = $this->actingAs($receptionist)->post('/visits', [
        'patient' => [
            'id' => null,
            'name' => 'Sana Khan',
            'gender' => 'female',
            'phone' => '03001234567',
            'age_years' => 32,
        ],
        'doctor_id' => null,
        'test_ids' => [$cbc->id, $glucose->id],
        'discount' => 100,
        'paid_amount' => 950,
    ]);

    $visit = Visit::latest('id')->first();
    $response->assertRedirect("/visits/{$visit->id}");

    $patient = Patient::where('phone', '03001234567')->first();
    expect($patient)->not->toBeNull()
        ->and($patient->mrn)->toStartWith('P')
        ->and($visit->patient_id)->toBe($patient->id)
        ->and((float) $visit->total_amount)->toBe(1050.0)
        ->and((float) $visit->discount)->toBe(100.0)
        ->and((float) $visit->net_amount)->toBe(950.0)
        ->and($visit->payment_status->value)->toBe('paid')
        ->and($visit->visit_number)->toStartWith('V-');

    // CBC is EDTA whole blood, glucose is serum: two samples.
    expect($visit->samples)->toHaveCount(2)
        ->and($visit->visitTests)->toHaveCount(2);

    // Every parameter of both tests has a pending, empty result row.
    $expectedResults = $cbc->parameters()->count() + $glucose->parameters()->count();
    $actualResults = $visit->visitTests->sum(fn ($visitTest) => $visitTest->results()->count());
    expect($actualResults)->toBe($expectedResults);
});

test('an existing patient is reused instead of duplicated', function () {
    $receptionist = userWithRole('receptionist');
    $patient = Patient::factory()->create();
    $test = LabTest::where('code', 'ESR')->first();

    $this->actingAs($receptionist)->post('/visits', [
        'patient' => ['id' => $patient->id],
        'test_ids' => [$test->id],
    ])->assertRedirect();

    expect(Patient::count())->toBe(1)
        ->and(Visit::first()->patient_id)->toBe($patient->id);
});

test('patient search matches by phone and returns history', function () {
    $receptionist = userWithRole('receptionist');
    Patient::factory()->create(['name' => 'Bilal Ahmed', 'phone' => '03219876543']);

    $this->actingAs($receptionist)
        ->getJson('/patients/search?q=0321987')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.name', 'Bilal Ahmed');
});

test('visit numbers sequence within the same day', function () {
    $receptionist = userWithRole('receptionist');
    $test = LabTest::where('code', 'ESR')->first();

    foreach (range(1, 2) as $i) {
        $this->actingAs($receptionist)->post('/visits', [
            'patient' => ['id' => null, 'name' => "Patient {$i}", 'gender' => 'male', 'age_years' => 40],
            'test_ids' => [$test->id],
        ]);
    }

    $numbers = Visit::orderBy('id')->pluck('visit_number');
    expect($numbers[0])->toEndWith('-001')
        ->and($numbers[1])->toEndWith('-002');
});
