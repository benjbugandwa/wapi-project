<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\CabinetInvitation;
use App\Models\User;
use App\Notifications\CabinetInvitationNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

beforeEach(function () {
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);
});

test('cabinet admin can invite a user to own cabinet', function () {
    $cabinet = Cabinet::factory()->create();
    $admin = User::factory()->for($cabinet)->create();
    $admin->assignRole(RoleEnum::Admin->value);

    $this->actingAs($admin);

    Volt::test('admin.cabinet-invitations.index')
        ->set('email', 'invite@example.com')
        ->set('role_name', RoleEnum::Avocat->value)
        ->call('invite')
        ->assertHasNoErrors();

    $invitation = CabinetInvitation::query()->where('email', 'invite@example.com')->firstOrFail();

    expect($invitation->cabinet->is($cabinet))->toBeTrue()
        ->and(AuditLog::query()->where('action', 'cabinet_invitation.created')->exists())->toBeTrue();

    Notification::assertSentTimes(CabinetInvitationNotification::class, 1);
});

test('cabinet admin cannot invite into another cabinet', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleEnum::Admin->value);
    $otherCabinet = Cabinet::factory()->create();

    $this->actingAs($admin);

    Volt::test('admin.cabinet-invitations.index')
        ->set('cabinet_id', $otherCabinet->id)
        ->set('email', 'cross@example.com')
        ->set('role_name', RoleEnum::Avocat->value)
        ->call('invite')
        ->assertForbidden();
});

test('invitation is created and generates link even if mail delivery fails', function () {
    $cabinet = Cabinet::factory()->create();
    $admin = User::factory()->for($cabinet)->create();
    $admin->assignRole(RoleEnum::Admin->value);

    $this->actingAs($admin);

    Notification::shouldReceive('send')
        ->andThrow(new \RuntimeException('Connection to mail host failed'));

    Volt::test('admin.cabinet-invitations.index')
        ->set('email', 'unreachable@example.com')
        ->set('role_name', RoleEnum::Avocat->value)
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSee('Copier le lien');

    expect(CabinetInvitation::query()->where('email', 'unreachable@example.com')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'cabinet_invitation.created')->exists())->toBeTrue();
});
