<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Rappel;
use App\Models\User;

class RappelPolicy
{
    public function update(User $user, Rappel|string|null $rappel = null): bool
    {
        if (! $user->can(PermissionEnum::ManageAgendaNotifications->value)) {
            return false;
        }

        if (! $rappel instanceof Rappel) {
            return $user->cabinet_id !== null;
        }

        return $user->canAccessCabinet($rappel->cabinet_id);
    }
}
