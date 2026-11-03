<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Evenement;
use App\Models\User;

class EvenementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewAgenda->value);
    }

    public function view(User $user, Evenement $evenement): bool
    {
        if (! $user->can(PermissionEnum::ViewAgenda->value) || ! $user->canAccessCabinet($evenement->cabinet_id)) {
            return false;
        }

        return $user->isSystemAdmin()
            || $user->can(PermissionEnum::ShareAgenda->value)
            || $evenement->created_by === $user->id
            || $evenement->participants()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CreateAgenda->value)
            && $user->cabinet_id !== null;
    }

    public function update(User $user, Evenement $evenement): bool
    {
        return $user->can(PermissionEnum::UpdateAgenda->value)
            && $user->canAccessCabinet($evenement->cabinet_id)
            && ! $evenement->isCancelled();
    }

    public function cancel(User $user, Evenement $evenement): bool
    {
        return $user->can(PermissionEnum::CancelAgenda->value)
            && $user->canAccessCabinet($evenement->cabinet_id)
            && ! $evenement->isCancelled();
    }

    public function share(User $user, Evenement $evenement): bool
    {
        return $user->can(PermissionEnum::ShareAgenda->value)
            && $user->canAccessCabinet($evenement->cabinet_id)
            && ! $evenement->isCancelled();
    }
}
