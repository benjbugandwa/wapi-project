<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewUsers->value);
    }

    public function view(User $user, User $target): bool
    {
        return $user->can(PermissionEnum::ViewUsers->value)
            && $user->canAccessCabinet($target->cabinet_id);
    }

    public function activate(User $user, User $target): bool
    {
        return $user->can(PermissionEnum::ActivateUsers->value)
            && $user->canAccessCabinet($target->cabinet_id);
    }

    public function deactivate(User $user, User $target): bool
    {
        return $user->can(PermissionEnum::DeactivateUsers->value)
            && $user->canAccessCabinet($target->cabinet_id);
    }

    public function updateRole(User $user, User $target): bool
    {
        return $user->can(PermissionEnum::AssignRoles->value)
            && $user->canAccessCabinet($target->cabinet_id);
    }
}
