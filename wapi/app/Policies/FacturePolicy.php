<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Facture;
use App\Models\User;

class FacturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ViewFactures->value);
    }

    public function view(User $user, Facture $facture): bool
    {
        return $user->can(PermissionEnum::ViewFactures->value)
            && $user->canAccessCabinet($facture->cabinet_id);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::CreateFactures->value)
            && $user->cabinet_id !== null;
    }

    public function update(User $user, Facture $facture): bool
    {
        return $user->can(PermissionEnum::UpdateFactures->value)
            && $user->canAccessCabinet($facture->cabinet_id)
            && ! $facture->isPaid()
            && ! $facture->isCancelled();
    }

    public function send(User $user, Facture $facture): bool
    {
        return $user->can(PermissionEnum::SendFactures->value)
            && $user->canAccessCabinet($facture->cabinet_id)
            && ! $facture->isPaid()
            && ! $facture->isCancelled();
    }

    public function cancel(User $user, Facture $facture): bool
    {
        return $user->can(PermissionEnum::CancelFactures->value)
            && $user->canAccessCabinet($facture->cabinet_id)
            && ! $facture->isPaid()
            && ! $facture->isCancelled();
    }

    public function downloadPdf(User $user, Facture $facture): bool
    {
        return $user->can(PermissionEnum::DownloadFacturePdf->value)
            && $user->canAccessCabinet($facture->cabinet_id);
    }
}
