<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Cabinet;
use App\Models\User;

class CabinetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewCabinets->value);
    }

    public function view(User $user, Cabinet $cabinet): bool
    {
        return $user->can(PermissionEnum::ViewCabinets->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CreateCabinets->value);
    }

    public function update(User $user, Cabinet $cabinet): bool
    {
        return $user->can(PermissionEnum::UpdateCabinets->value);
    }

    public function suspend(User $user, Cabinet $cabinet): bool
    {
        return $user->can(PermissionEnum::SuspendCabinets->value);
    }

    public function activate(User $user, Cabinet $cabinet): bool
    {
        return $user->can(PermissionEnum::ActivateCabinets->value);
    }
}
