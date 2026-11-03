<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\CabinetStatus;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;
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

test('system admin can view nouveau button and create active user with cabinet and role', function () {
    $systemAdmin = User::factory()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $cabinet = Cabinet::factory()->create([
        'statut_cabinet' => CabinetStatus::Active,
    ]);

    $this->actingAs($systemAdmin);

    Volt::test('admin.users.index')
        ->assertSee('Nouveau')
        ->call('openCreateModal')
        ->assertDispatched('open-modal', 'create-user-modal')
        ->set('nom', 'Maitre Marie Curie')
        ->set('email', 'marie.curie@wapi.test')
        ->set('password', 'SecretPassword123!')
        ->set('role_name', RoleEnum::Avocat->value)
        ->set('cabinet_id', $cabinet->id)
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal', 'create-user-modal');

    $newUser = User::query()->where('email', 'marie.curie@wapi.test')->first();

    expect($newUser)->not->toBeNull()
        ->and($newUser->nom)->toBe('Maitre Marie Curie')
        ->and($newUser->is_active)->toBeTrue()
        ->and($newUser->email_verified_at)->not->toBeNull()
        ->and($newUser->cabinet_id)->toBe($cabinet->id)
        ->and($newUser->hasRole(RoleEnum::Avocat->value))->toBeTrue()
        ->and(Hash::check('SecretPassword123!', $newUser->password))->toBeTrue()
        ->and(AuditLog::query()->where('auditable_id', $newUser->id)->where('action', 'user.created')->exists())->toBeTrue();
});

test('system admin can create another system admin user without cabinet', function () {
    $systemAdmin = User::factory()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $this->actingAs($systemAdmin);

    Volt::test('admin.users.index')
        ->set('nom', 'Second Super Admin')
        ->set('email', 'second.admin@wapi.test')
        ->set('password', 'SecretPassword123!')
        ->set('role_name', RoleEnum::SystemAdmin->value)
        ->set('cabinet_id', '')
        ->call('createUser')
        ->assertHasNoErrors();

    $newUser = User::query()->where('email', 'second.admin@wapi.test')->first();

    expect($newUser)->not->toBeNull()
        ->and($newUser->nom)->toBe('Second Super Admin')
        ->and($newUser->is_active)->toBeTrue()
        ->and($newUser->cabinet_id)->toBeNull()
        ->and($newUser->hasRole(RoleEnum::SystemAdmin->value))->toBeTrue();
});

test('cabinet admin cannot view nouveau button or create users', function () {
    $cabinet = Cabinet::factory()->create();
    $cabinetAdmin = User::factory()->for($cabinet)->create();
    $cabinetAdmin->assignRole(RoleEnum::Admin->value);

    $this->actingAs($cabinetAdmin);

    Volt::test('admin.users.index')
        ->assertDontSee('Nouveau')
        ->call('openCreateModal')
        ->assertForbidden();

    Volt::test('admin.users.index')
        ->call('createUser')
        ->assertForbidden();
});

test('user creation validates required fields and active cabinet', function () {
    $systemAdmin = User::factory()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $suspendedCabinet = Cabinet::factory()->create([
        'statut_cabinet' => CabinetStatus::Suspended,
    ]);

    $existingUser = User::factory()->create([
        'email' => 'existing@wapi.test',
    ]);

    $this->actingAs($systemAdmin);

    Volt::test('admin.users.index')
        ->set('nom', '')
        ->set('email', '')
        ->set('password', 'short')
        ->set('role_name', RoleEnum::Avocat->value)
        ->set('cabinet_id', '')
        ->call('createUser')
        ->assertHasErrors(['nom', 'email', 'password', 'cabinet_id']);

    Volt::test('admin.users.index')
        ->set('nom', 'Valid Nom')
        ->set('email', 'existing@wapi.test')
        ->set('password', 'ValidPassword123!')
        ->set('role_name', RoleEnum::Avocat->value)
        ->set('cabinet_id', $suspendedCabinet->id)
        ->call('createUser')
        ->assertHasErrors(['email', 'cabinet_id']);
});

test('system admin can view editer button and assign role and cabinet to an unassigned user', function () {
    $systemAdmin = User::factory()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $cabinet = Cabinet::factory()->create([
        'statut_cabinet' => CabinetStatus::Active,
    ]);

    $unassignedUser = User::factory()->inactive()->create([
        'cabinet_id' => null,
    ]);

    $this->actingAs($systemAdmin);

    Volt::test('admin.users.index')
        ->assertSee('Editer')
        ->call('openEditModal', $unassignedUser->id)
        ->assertDispatched('open-modal', 'edit-user-modal')
        ->assertSet('editingUserId', $unassignedUser->id)
        ->assertSet('editingUserNom', $unassignedUser->nom)
        ->assertSet('editingUserEmail', $unassignedUser->email)
        ->set('editingUserRole', RoleEnum::Comptable->value)
        ->set('editingUserCabinetId', $cabinet->id)
        ->set('editingUserIsActive', true)
        ->call('updateUserAssignment')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal', 'edit-user-modal');

    $unassignedUser->refresh();

    expect($unassignedUser->is_active)->toBeTrue()
        ->and($unassignedUser->cabinet_id)->toBe($cabinet->id)
        ->and($unassignedUser->hasRole(RoleEnum::Comptable->value))->toBeTrue()
        ->and(AuditLog::query()->where('auditable_id', $unassignedUser->id)->where('action', 'user.assigned')->exists())->toBeTrue();
});

test('system admin can assign system admin role without cabinet via edit modal', function () {
    $systemAdmin = User::factory()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $targetUser = User::factory()->create();

    $this->actingAs($systemAdmin);

    Volt::test('admin.users.index')
        ->call('openEditModal', $targetUser->id)
        ->set('editingUserRole', RoleEnum::SystemAdmin->value)
        ->set('editingUserCabinetId', '')
        ->call('updateUserAssignment')
        ->assertHasNoErrors();

    $targetUser->refresh();

    expect($targetUser->cabinet_id)->toBeNull()
        ->and($targetUser->hasRole(RoleEnum::SystemAdmin->value))->toBeTrue();
});

test('cabinet admin cannot view editer button or update user assignment', function () {
    $cabinet = Cabinet::factory()->create();
    $cabinetAdmin = User::factory()->for($cabinet)->create();
    $cabinetAdmin->assignRole(RoleEnum::Admin->value);

    $target = User::factory()->for($cabinet)->create();

    $this->actingAs($cabinetAdmin);

    Volt::test('admin.users.index')
        ->assertDontSee('Editer')
        ->call('openEditModal', $target->id)
        ->assertForbidden();

    Volt::test('admin.users.index')
        ->set('editingUserId', $target->id)
        ->set('editingUserRole', RoleEnum::Avocat->value)
        ->set('editingUserCabinetId', $cabinet->id)
        ->call('updateUserAssignment')
        ->assertForbidden();
});

test('user assignment validates role and active cabinet', function () {
    $systemAdmin = User::factory()->create();
    $systemAdmin->assignRole(RoleEnum::SystemAdmin->value);

    $suspendedCabinet = Cabinet::factory()->create([
        'statut_cabinet' => CabinetStatus::Suspended,
    ]);

    $target = User::factory()->create();

    $this->actingAs($systemAdmin);

    Volt::test('admin.users.index')
        ->call('openEditModal', $target->id)
        ->set('editingUserRole', RoleEnum::Avocat->value)
        ->set('editingUserCabinetId', '')
        ->call('updateUserAssignment')
        ->assertHasErrors(['editingUserCabinetId']);

    Volt::test('admin.users.index')
        ->call('openEditModal', $target->id)
        ->set('editingUserRole', RoleEnum::Avocat->value)
        ->set('editingUserCabinetId', $suspendedCabinet->id)
        ->call('updateUserAssignment')
        ->assertHasErrors(['editingUserCabinetId']);
});
