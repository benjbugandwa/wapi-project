<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $user->can(PermissionEnum::ViewDocuments->value)
            && $user->canAccessCabinet($document->cabinet_id);
    }

    public function upload(User $user): bool
    {
        return $user->can(PermissionEnum::UploadDocuments->value)
            && $user->cabinet_id !== null;
    }

    public function download(User $user, Document $document): bool
    {
        return $user->can(PermissionEnum::DownloadDocuments->value)
            && $user->canAccessCabinet($document->cabinet_id);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can(PermissionEnum::DeleteDocuments->value)
            && $user->canAccessCabinet($document->cabinet_id);
    }
}
