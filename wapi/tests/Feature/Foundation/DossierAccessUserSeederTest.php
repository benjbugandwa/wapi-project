<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Enums\CabinetStatus;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\DossierAccessUserSeeder;

test('dossier access user seeder creates a verified active user with dossier permissions', function () {
    $this->seed(DossierAccessUserSeeder::class);

    $user = User::query()
        ->where('email', DossierAccessUserSeeder::USER_EMAIL)
        ->with('cabinet')
        ->firstOrFail();

    expect($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->cabinet)->not->toBeNull()
        ->and($user->cabinet->statut_cabinet)->toBe(CabinetStatus::Active)
        ->and($user->hasRole(RoleEnum::Avocat->value))->toBeTrue()
        ->and($user->can(PermissionEnum::ViewDossiers->value))->toBeTrue()
        ->and($user->can(PermissionEnum::CreateDossiers->value))->toBeTrue()
        ->and($user->can(PermissionEnum::UpdateDossiers->value))->toBeTrue()
        ->and($user->can(PermissionEnum::CloseDossiers->value))->toBeTrue()
        ->and($user->can(PermissionEnum::ArchiveDossiers->value))->toBeTrue()
        ->and($user->can(PermissionEnum::AssignDossiers->value))->toBeTrue();
});
