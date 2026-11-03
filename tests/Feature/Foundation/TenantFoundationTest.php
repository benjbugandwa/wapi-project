<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Enums\CabinetStatus;
use App\Enums\RoleEnum;
use App\Models\AuditLog;
use App\Models\Cabinet;
use App\Models\CabinetInvitation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

test('tenant models use uuid primary keys and relations', function () {
    $cabinet = Cabinet::factory()->create();
    $user = User::factory()->for($cabinet)->create();
    $invitation = CabinetInvitation::factory()->for($cabinet)->create();
    $auditLog = AuditLog::factory()->for($cabinet)->create();

    expect($cabinet->id)->toBeString()
        ->and($cabinet->id)->toHaveLength(36)
        ->and($cabinet->statut_cabinet)->toBe(CabinetStatus::Active)
        ->and($user->cabinet->is($cabinet))->toBeTrue()
        ->and($invitation->cabinet->is($cabinet))->toBeTrue()
        ->and($invitation->inviter->cabinet->is($cabinet))->toBeTrue()
        ->and($auditLog->cabinet->is($cabinet))->toBeTrue()
        ->and($auditLog->user->cabinet->is($cabinet))->toBeTrue();
});

test('users can receive spatie roles with uuid morph keys', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Admin->value);

    expect($user->hasRole(RoleEnum::Admin->value))->toBeTrue()
        ->and($user->can('users.invite'))->toBeTrue()
        ->and($user->can('cabinets.create'))->toBeFalse();
});
