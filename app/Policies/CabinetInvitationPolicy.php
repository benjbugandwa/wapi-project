<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\CabinetInvitation;
use App\Models\User;

class CabinetInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::InviteUsers->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::InviteUsers->value);
    }

    public function view(User $user, CabinetInvitation $invitation): bool
    {
        return $user->can(PermissionEnum::InviteUsers->value)
            && $user->canAccessCabinet($invitation->cabinet_id);
    }
}
