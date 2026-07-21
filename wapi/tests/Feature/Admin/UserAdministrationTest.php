<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('cabinet admin can activate deactivate and change role for own cabinet user', function () {
    $cabinet = Cabinet::factory()->create();
    $admin = User::factory()->for($cabinet)->create();
    $admin->assignRole(RoleEnum::Admin->value);
    $target = User::factory()->for($cabinet)->inactive()->create();
    $target->assignRole(RoleEnum::Avocat->value);

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->call('activate', $target->id)
        ->set("roleUpdates.$target->id", RoleEnum::Secretaire->value)
        ->call('updateRole', $target->id)
        ->call('deactivate', $target->id)
        ->assertHasNoErrors();

    $target->refresh();

    expect($target->is_active)->toBeFalse()
        ->and($target->hasRole(RoleEnum::Secretaire->value))->toBeTrue()
        ->and(AuditLog::query()->where('auditable_id', $target->id)->count())->toBe(3);
});

test('cabinet admin cannot manage another cabinet user', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::Admin->value);
    $otherCabinet = Cabinet::factory()->create();
    $target = User::factory()->for($otherCabinet)->create();
    $target->assignRole(RoleEnum::Avocat->value);

    $this->actingAs($admin);

    Volt::test('admin.users.index')
        ->call('deactivate', $target->id)
        ->assertForbidden();
});
