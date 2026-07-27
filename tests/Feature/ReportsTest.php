<?php

use App\Actions\CreateVisit;
use App\Models\Test as LabTest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestCatalogSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TestCatalogSeeder::class]);
});

function reportAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function registerVisit(array $codes, float $paid): void
{
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    app(CreateVisit::class)->handle([
        'patient' => ['id' => null, 'name' => 'Report Patient', 'gender' => 'male', 'age_years' => 35],
        'doctor_id' => null,
        'test_ids' => LabTest::whereIn('code', $codes)->pluck('id')->all(),
        'discount' => 0,
        'paid_amount' => $paid,
    ], $receptionist);
}

test('business reports are admin-only', function () {
    foreach (['receptionist', 'technician', 'pathologist'] as $role) {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/reports')->assertForbidden();
        $this->actingAs($user)->get('/reports/daily-collection')->assertForbidden();
        $this->actingAs($user)->get('/reports/revenue')->assertForbidden();
        $this->actingAs($user)->get('/reports/volume-tat')->assertForbidden();
    }
});

test('the reports landing renders', function () {
    $this->actingAs(reportAdmin())
        ->get('/reports')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('reports/index'));
});

test('daily collection totals the day’s visits', function () {
    $net = (float) LabTest::whereIn('code', ['ESR', 'CBC'])->sum('price');
    registerVisit(['ESR', 'CBC'], 600);

    $this->actingAs(reportAdmin())
        ->get('/reports/daily-collection?date=' . now()->toDateString())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/daily-collection')
            ->where('summary.count', 1)
            ->where('summary.net', fn ($v) => (float) $v === $net)
            ->where('summary.collected', fn ($v) => (float) $v === 600.0)
            ->where('summary.outstanding', fn ($v) => (float) $v === $net - 600.0)
            ->has('rows', 1));
});

test('daily collection excludes other days', function () {
    registerVisit(['ESR'], 200);

    $this->actingAs(reportAdmin())
        ->get('/reports/daily-collection?date=' . now()->subDay()->toDateString())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('summary.count', 0)->has('rows', 0));
});

test('revenue report builds a daily series and category breakdown', function () {
    $net = (float) LabTest::whereIn('code', ['ESR', 'CBC'])->sum('price');
    registerVisit(['ESR', 'CBC'], 1000);

    $this->actingAs(reportAdmin())
        ->get('/reports/revenue?from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->toDateString())
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/revenue')
            ->where('totals.visits', 1)
            ->where('totals.net', fn ($v) => (float) $v === $net)
            ->has('daily', 1)
            ->has('byCategory'));
});

test('volume report counts ordered tests', function () {
    registerVisit(['ESR', 'CBC'], 0);
    registerVisit(['ESR'], 0);

    $this->actingAs(reportAdmin())
        ->get('/reports/volume-tat?from=' . now()->startOfMonth()->toDateString() . '&to=' . now()->toDateString())
        ->assertOk()
        ->assertInertia(function (Assert $page) {
            $page->component('reports/volume-tat')->has('volume');
            $volume = collect($page->toArray()['props']['volume']);
            expect($volume->firstWhere('code', 'ESR')['orders'])->toBe(2)
                ->and($volume->firstWhere('code', 'CBC')['orders'])->toBe(1);
        });
});

test('daily collection exports csv', function () {
    registerVisit(['ESR'], 200);

    $response = $this->actingAs(reportAdmin())
        ->get('/reports/daily-collection?date=' . now()->toDateString() . '&format=csv');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())->toContain('Visit,Time,Patient,MRN,Gross,Discount,Net,Paid,Balance,Status');
});
