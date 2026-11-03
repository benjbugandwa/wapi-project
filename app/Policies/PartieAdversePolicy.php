<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\PartieAdverse;
use App\Models\User;

class PartieAdversePolicy
{
    public function view(User $user, PartieAdverse $partieAdverse): bool
    {
        return $user->can(PermissionEnum::ViewDossiers->value)
            && $user->canAccessCabinet($partieAdverse->cabinet_id);
    }

    public function update(User $user, PartieAdverse $partieAdverse): bool
    {
        return $user->can(PermissionEnum::UpdateDossiers->value)
            && $user->canAccessCabinet($partieAdverse->cabinet_id);
    }
}
