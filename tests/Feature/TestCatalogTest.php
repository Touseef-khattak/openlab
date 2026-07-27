<?php

use App\Actions\CreateVisit;
use App\Models\Test as LabTest;
use App\Models\TestParameter;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
});

function admin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

/** Orders the given test codes on a real visit (pre-creates samples + result rows). */
function orderTests(array $codes): void
{
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Catalog Patient', 'gender' => 'male', 'age_years' => 40],
        'doctor_id' => null,
        'test_ids' => LabTest::whereIn('code', $codes)->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => 0,
    ], $receptionist);
}

test('non-managers cannot reach the test management pages', function () {
    $tech = User::factory()->create();
    $tech->assignRole('technician');

    // technician has catalog.view → can list…
    $this->actingAs($tech)->get('/tests')->assertOk();
    // …but not manage.
    $this->actingAs($tech)->get('/tests/create')->assertForbidden();
    $this->actingAs($tech)->post('/tests', [])->assertForbidden();
});

test('a single-value test creates one parameter and one range with an auto code', function () {
    $this->actingAs(admin())->post('/tests', [
        'name' => 'Vitamin D',
        'code' => '',
        'test_category_id' => null,
        'specimen_type' => 'Serum',
        'price' => 2500,
        'is_active' => true,
        'is_panel' => false,
        'parameters' => [[
            'name' => 'Vitamin D',
            'unit' => 'ng/mL',
            'result_type' => 'numeric',
            'decimals' => 1,
            'options' => [],
            'ranges' => [[
                'gender' => 'all',
                'age_min_years' => '',
                'age_max_years' => '',
                'low' => 30,
                'high' => 100,
                'critical_low' => '',
                'critical_high' => '',
                'normal_text' => '',
            ]],
        ]],
    ])->assertRedirect('/tests');

    $test = LabTest::where('name', 'Vitamin D')->with('parameters.referenceRanges')->first();
    expect($test)->not->toBeNull()
        ->and($test->is_panel)->toBeFalse()
        ->and($test->code)->toBe('VITAMIND')
        ->and($test->specimen_type)->toBe('Serum')
        ->and($test->parameters)->toHaveCount(1);

    $range = $test->parameters->first()->referenceRanges->first();
    expect((float) $range->low)->toBe(30.0)
        ->and((float) $range->high)->toBe(100.0)
        ->and($range->gender)->toBeNull();
});

test('a panel test creates a parameter per row with gender-specific ranges', function () {
    $this->actingAs(admin())->post('/tests', [
        'name' => 'Renal Panel',
        'code' => 'RENAL',
        'test_category_id' => null,
        'specimen_type' => 'Serum',
        'price' => 1200,
        'is_active' => true,
        'is_panel' => true,
        'parameters' => [
            [
                'name' => 'Sodium',
                'unit' => 'mmol/L',
                'result_type' => 'numeric',
                'decimals' => 0,
                'options' => [],
                'ranges' => [['gender' => 'all', 'age_min_years' => '', 'age_max_years' => '', 'low' => 135, 'high' => 145, 'critical_low' => 120, 'critical_high' => 160, 'normal_text' => '']],
            ],
            [
                'name' => 'Creatinine',
                'unit' => 'mg/dL',
                'result_type' => 'numeric',
                'decimals' => 2,
                'options' => [],
                'ranges' => [
                    ['gender' => 'male', 'age_min_years' => '', 'age_max_years' => '', 'low' => 0.7, 'high' => 1.3, 'critical_low' => '', 'critical_high' => '', 'normal_text' => ''],
                    ['gender' => 'female', 'age_min_years' => '', 'age_max_years' => '', 'low' => 0.6, 'high' => 1.1, 'critical_low' => '', 'critical_high' => '', 'normal_text' => ''],
                ],
            ],
        ],
    ])->assertRedirect('/tests');

    $test = LabTest::where('code', 'RENAL')->with('parameters.referenceRanges')->first();
    expect($test->is_panel)->toBeTrue()
        ->and($test->parameters)->toHaveCount(2);

    $creatinine = $test->parameters->firstWhere('name', 'Creatinine');
    expect($creatinine->referenceRanges)->toHaveCount(2)
        ->and($creatinine->referenceRanges->firstWhere('gender', 'male')->high)->toEqual(1.3)
        ->and($creatinine->referenceRanges->firstWhere('gender', 'female')->high)->toEqual(1.1);
});

test('editing a test updates fields and syncs parameters', function () {
    $test = LabTest::where('code', 'LFT')->with('parameters')->first();
    $keep = $test->parameters->first();

    // Send only the first parameter back → the rest should be dropped (none have results).
    $this->actingAs(admin())->put("/tests/{$test->id}", [
        'name' => 'Liver Function Tests',
        'code' => 'LFT',
        'test_category_id' => $test->test_category_id,
        'specimen_type' => $test->specimen_type,
        'price' => 1500,
        'is_active' => true,
        'is_panel' => true,
        'parameters' => [[
            'id' => $keep->id,
            'name' => $keep->name,
            'unit' => $keep->unit,
            'result_type' => 'numeric',
            'decimals' => 1,
            'options' => [],
            'ranges' => [['gender' => 'all', 'age_min_years' => '', 'age_max_years' => '', 'low' => 5, 'high' => 40, 'critical_low' => '', 'critical_high' => '', 'normal_text' => '']],
        ]],
    ])->assertRedirect('/tests');

    $test->refresh()->load('parameters');
    expect((float) $test->price)->toBe(1500.0)
        ->and($test->name)->toBe('Liver Function Tests')
        ->and($test->parameters)->toHaveCount(1)
        ->and($test->parameters->first()->id)->toBe($keep->id);
});

test('a parameter with recorded results cannot be dropped on edit', function () {
    $test = LabTest::where('code', 'LFT')->with('parameters')->first();
    $param = $test->parameters->first();

    // Ordering the panel pre-creates a result row for every parameter.
    orderTests(['LFT']);

    // Try to save the test with the first parameter omitted.
    $survivor = $test->parameters->last();
    $this->actingAs(admin())->put("/tests/{$test->id}", [
        'name' => $test->name,
        'code' => $test->code,
        'test_category_id' => $test->test_category_id,
        'specimen_type' => $test->specimen_type,
        'price' => (string) $test->price,
        'is_active' => true,
        'is_panel' => true,
        'parameters' => [[
            'id' => $survivor->id,
            'name' => $survivor->name,
            'unit' => $survivor->unit,
            'result_type' => 'numeric',
            'decimals' => 2,
            'options' => [],
            'ranges' => [],
        ]],
    ])->assertSessionHasErrors('parameters');

    // The used parameter is still there.
    expect(TestParameter::find($param->id))->not->toBeNull();
});

test('activation can be toggled', function () {
    $test = LabTest::where('code', 'ESR')->first();
    expect($test->is_active)->toBeTrue();

    $this->actingAs(admin())->patch("/tests/{$test->id}/toggle")->assertRedirect();
    expect($test->refresh()->is_active)->toBeFalse();

    $this->actingAs(admin())->patch("/tests/{$test->id}/toggle")->assertRedirect();
    expect($test->refresh()->is_active)->toBeTrue();
});

test('a test that was never ordered can be deleted, an ordered one cannot', function () {
    $fresh = LabTest::where('code', 'ESR')->first();
    $this->actingAs(admin())->delete("/tests/{$fresh->id}")->assertRedirect();
    expect(LabTest::find($fresh->id))->toBeNull();

    $ordered = LabTest::where('code', 'CBC')->first();
    orderTests(['CBC']);

    $this->actingAs(admin())->delete("/tests/{$ordered->id}")->assertSessionHas('error');
    expect(LabTest::find($ordered->id))->not->toBeNull();
});
