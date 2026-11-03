<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewClients->value);
    }

    public function view(User $user, Client $client): bool
    {
        return $user->can(PermissionEnum::ViewClients->value)
            && $user->canAccessCabinet($client->cabinet_id);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CreateClients->value)
            && $user->cabinet_id !== null;
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can(PermissionEnum::UpdateClients->value)
            && $user->canAccessCabinet($client->cabinet_id);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can(PermissionEnum::DeleteClients->value)
            && $user->canAccessCabinet($client->cabinet_id);
    }
}
