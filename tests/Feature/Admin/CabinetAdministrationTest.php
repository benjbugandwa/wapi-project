<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\CabinetStatus;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('system admin can create a cabinet from livewire screen', function () {
    $systemAdmin = User::factory()->systemAdmin()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $this->actingAs($systemAdmin);

    Volt::test('admin.cabinets.create')
        ->set('denomination', 'Cabinet Central')
        ->set('adresse_cabinet', '1 Avenue de la Justice')
        ->set('nom_repondant', 'Responsable Central')
        ->set('telephone_repondant', '+243 810 000 001')
        ->set('email_repondant', 'central@example.com')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.cabinets.index', absolute: false));

    $cabinet = Cabinet::query()->where('denomination', 'Cabinet Central')->firstOrFail();

    expect($cabinet->statut_cabinet)->toBe(CabinetStatus::Active)
        ->and(AuditLog::query()->where('action', 'cabinet.created')->where('auditable_id', $cabinet->id)->exists())->toBeTrue();
});

test('cabinet admin cannot access system cabinet administration', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.cabinets.index'))
        ->assertForbidden();
});

test('system admin can suspend and reactivate a cabinet', function () {
    $systemAdmin = User::factory()->systemAdmin()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);
    $cabinet = Cabinet::factory()->create();

    $this->actingAs($systemAdmin);

    Volt::test('admin.cabinets.index')
        ->call('suspend', $cabinet->id)
        ->assertHasNoErrors();

    expect($cabinet->refresh()->statut_cabinet)->toBe(CabinetStatus::Suspended);

    Volt::test('admin.cabinets.index')
        ->call('activate', $cabinet->id)
        ->assertHasNoErrors();

    expect($cabinet->refresh()->statut_cabinet)->toBe(CabinetStatus::Active)
        ->and(AuditLog::query()->whereIn('action', ['cabinet.suspended', 'cabinet.activated'])->count())->toBe(2);
});
