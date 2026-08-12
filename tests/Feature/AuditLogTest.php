<?php

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function auditAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('the audit log is admin-only', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $this->actingAs($receptionist)->get('/audit-log')->assertForbidden();
    $this->actingAs(auditAdmin())->get('/audit-log')->assertOk();
});

test('the audit log lists recorded activity with a field-level diff', function () {
    $patient = Patient::factory()->create();
    $patient->update(['name' => 'Corrected Name']);

    $this->actingAs(auditAdmin())
        ->get('/audit-log')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('audit/index')
            // newest first → the update
            ->where('activities.data.0.subject_type', 'Patient')
            ->where('activities.data.0.event', 'updated')
            ->where('activities.data.0.changes', fn ($changes) => collect($changes)->contains(
                fn ($c) => $c['field'] === 'name' && $c['new'] === 'Corrected Name'
            ))
            ->etc());
});

test('the audit log filters by event', function () {
    $patient = Patient::factory()->create();
    $patient->update(['phone' => '03001234567']);

    $this->actingAs(auditAdmin())
        ->get('/audit-log?event=created')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activities.data', fn ($rows) => collect($rows)->every(fn ($r) => $r['event'] === 'created')));
});
