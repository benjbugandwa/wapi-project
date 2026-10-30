<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if (! $user->can(PermissionEnum::ViewDocuments->value) || ! $user->canAccessCabinet($document->cabinet_id)) {
            return false;
        }

        if ($document->est_confidentiel) {
            return $this->canAccessConfidentialDocument($user, $document);
        }

        return true;
    }

    public function upload(User $user): bool
    {
        return $user->can(PermissionEnum::UploadDocuments->value)
            && $user->cabinet_id !== null;
    }

    public function download(User $user, Document $document): bool
    {
        if (! $user->can(PermissionEnum::DownloadDocuments->value) || ! $user->canAccessCabinet($document->cabinet_id)) {
            return false;
        }

        if ($document->est_confidentiel) {
            return $this->canAccessConfidentialDocument($user, $document);
        }

        return true;
    }

    public function delete(User $user, Document $document): bool
    {
        if (! $user->can(PermissionEnum::DeleteDocuments->value) || ! $user->canAccessCabinet($document->cabinet_id)) {
            return false;
        }

        if ($document->est_confidentiel) {
            return $this->canAccessConfidentialDocument($user, $document);
        }

        return true;
    }

    private function canAccessConfidentialDocument(User $user, Document $document): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        if (! $user->canAccessCabinet($document->cabinet_id)) {
            return false;
        }

        if ($user->hasRole(RoleEnum::Admin->value)) {
            return true;
        }

        if ($document->uploaded_by === $user->id) {
            return true;
        }

        if ($user->hasRole(RoleEnum::Avocat->value) && $document->dossier_id !== null) {
            return $user->assignedDossiers()
                ->where('dossiers.id', $document->dossier_id)
                ->exists();
        }

        return false;
    }
}
