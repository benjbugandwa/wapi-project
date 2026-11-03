<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferencePolicy
{
    public function manage(User $user): bool
    {
        return $user->can(PermissionEnum::ManageAgendaNotifications->value)
            && $user->cabinet_id !== null;
    }

    public function update(User $user, NotificationPreference $notificationPreference): bool
    {
        return $user->can(PermissionEnum::ManageAgendaNotifications->value)
            && $user->id === $notificationPreference->user_id
            && $user->canAccessCabinet($notificationPreference->cabinet_id);
    }
}
