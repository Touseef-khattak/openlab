<?php

use App\Models\LabSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function brandingAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('lab branding is limited to settings managers', function () {
    $receptionist = User::factory()->create();
    $receptionist->assignRole('receptionist');

    $this->actingAs($receptionist)->get('/settings/lab')->assertForbidden();
    $this->actingAs(brandingAdmin())->get('/settings/lab')->assertOk();
});

test('admin can update lab branding', function () {
    $this->actingAs(brandingAdmin())->put('/settings/lab', [
        'name' => 'Acme Diagnostics',
        'logo' => 'data:image/png;base64,iVBORw0KGgo=',
        'address' => '1 Main Street',
        'phone' => '0300-0000000',
        'email' => 'lab@acme.test',
        'report_header' => 'Diagnostic Report',
        'report_footer' => 'Thank you for choosing us.',
        'receipt_footer' => 'Get well soon.',
    ])->assertRedirect();

    $settings = LabSetting::current();
    expect($settings->name)->toBe('Acme Diagnostics')
        ->and($settings->logo)->toStartWith('data:image/')
        ->and($settings->address)->toBe('1 Main Street')
        ->and($settings->report_header)->toBe('Diagnostic Report')
        ->and($settings->receipt_footer)->toBe('Get well soon.');
});

test('the logo can be cleared', function () {
    LabSetting::current()->update(['logo' => 'data:image/png;base64,iVBORw0KGgo=']);

    $this->actingAs(brandingAdmin())->put('/settings/lab', [
        'name' => 'Acme Diagnostics',
        'remove_logo' => true,
    ])->assertRedirect();

    expect(LabSetting::current()->logo)->toBeNull();
});

test('an invalid logo payload is rejected', function () {
    $this->actingAs(brandingAdmin())->put('/settings/lab', [
        'name' => 'Acme Diagnostics',
        'logo' => 'definitely-not-an-image',
    ])->assertSessionHasErrors('logo');
});
