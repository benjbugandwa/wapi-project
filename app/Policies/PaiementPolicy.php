<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Paiement;
use App\Models\User;

class PaiementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewPaiements->value);
    }

    public function view(User $user, Paiement $paiement): bool
    {
        return $user->can(PermissionEnum::ViewPaiements->value)
            && $user->canAccessCabinet($paiement->cabinet_id);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CreatePaiements->value)
            && $user->cabinet_id !== null;
    }

    public function downloadReceipt(User $user, Paiement $paiement): bool
    {
        return $user->can(PermissionEnum::DownloadPaiementReceipt->value)
            && $user->canAccessCabinet($paiement->cabinet_id);
    }
}
