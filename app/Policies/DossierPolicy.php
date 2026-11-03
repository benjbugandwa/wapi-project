<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Dossier;
use App\Models\User;

class DossierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewDossiers->value);
    }

    public function view(User $user, Dossier $dossier): bool
    {
        return $user->can(PermissionEnum::ViewDossiers->value)
            && $user->canAccessCabinet($dossier->cabinet_id);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CreateDossiers->value)
            && $user->cabinet_id !== null;
    }

    public function update(User $user, Dossier $dossier): bool
    {
        return $user->can(PermissionEnum::UpdateDossiers->value)
            && $user->canAccessCabinet($dossier->cabinet_id)
            && ! $dossier->isArchived();
    }

    public function close(User $user, Dossier $dossier): bool
    {
        return $user->can(PermissionEnum::CloseDossiers->value)
            && $user->canAccessCabinet($dossier->cabinet_id)
            && ! $dossier->isClosed()
            && ! $dossier->isArchived();
    }

    public function archive(User $user, Dossier $dossier): bool
    {
        return $user->can(PermissionEnum::ArchiveDossiers->value)
            && $user->canAccessCabinet($dossier->cabinet_id)
            && $dossier->isClosed();
    }

    public function assign(User $user, Dossier $dossier): bool
    {
        return $user->can(PermissionEnum::AssignDossiers->value)
            && $user->canAccessCabinet($dossier->cabinet_id)
            && ! $dossier->isArchived();
    }
}
