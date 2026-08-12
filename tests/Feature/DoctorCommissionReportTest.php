<?php

use App\Actions\CreateVisit;
use App\Models\Doctor;
use App\Models\Test as LabTest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
});

function commissionAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function referredVisit(?int $doctorId, array $codes): float
{
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $visit = app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Referred Patient', 'gender' => 'female', 'age_years' => 44],
        'doctor_id' => $doctorId,
        'test_ids' => LabTest::whereIn('code', $codes)->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => 0,
    ], $receptionist);

    return (float) $visit->net_amount;
}

test('the commission report is admin-only', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $this->actingAs($receptionist)->get('/reports/doctor-commission')->assertForbidden();
});

test('commission is computed from referred net and the doctor percentage', function () {
    $doctor = Doctor::factory()->create(['name' => 'Dr. Ayesha', 'commission_percent' => 10]);

    $net = referredVisit($doctor->id, ['ESR', 'CBC']); // 10% of this
    referredVisit(null, ['ESR']);                       // self-referral, excluded

    $this->actingAs(commissionAdmin())
        ->get('/reports/doctor-commission?from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->toDateString())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/doctor-commission')
            ->where('totals.doctors', 1)
            ->where('totals.visits', 1)
            ->where('rows.0.doctor', 'Dr. Ayesha')
            ->where('rows.0.commission', fn ($v) => (float) $v === round($net * 0.10, 2))
            ->where('totals.commission', fn ($v) => (float) $v === round($net * 0.10, 2)));
});

test('the commission report exports csv', function () {
    $doctor = Doctor::factory()->create(['commission_percent' => 15]);
    referredVisit($doctor->id, ['ESR']);

    $response = $this->actingAs(commissionAdmin())
        ->get('/reports/doctor-commission?from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->toDateString() . '&format=csv');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    // Headers with spaces are quoted by fputcsv, which is valid CSV.
    expect($response->streamedContent())->toContain('Doctor,Specialty,"Commission %",Visits,"Referred Net",Commission');
});
