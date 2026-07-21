<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\UploadDocumentData;
use App\Models\Client;
use App\Models\Document;
use App\Models\Dossier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function upload(UploadDocumentData $data, User $actor): Document
    {
        return DB::transaction(function () use ($data, $actor): Document {
            $file = $data->file;
            $owner = $data->owner;
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $originalName = $file->getClientOriginalName();
            $mimeType = $this->mimeType($file);
            $sizeInBytes = $this->sizeInBytes($file);
            $systemName = Str::uuid().'.'.$extension;
            $path = $file->storeAs(
                $this->storageDirectory($owner),
                $systemName,
                'local',
            );

            $document = Document::query()->create([
                'cabinet_id' => $owner->cabinet_id,
                'client_id' => $owner instanceof Client ? $owner->id : null,
                'dossier_id' => $owner instanceof Dossier ? $owner->id : null,
                'nom_original' => $originalName,
                'nom_systeme' => $systemName,
                'chemin_acces' => $path,
                'extension' => $extension,
                'type_mime' => $mimeType,
                'taille_octets' => $sizeInBytes,
                'categorie' => $data->categorie,
                'est_confidentiel' => $data->estConfidentiel,
                'uploaded_by' => $actor->id,
            ]);

            $this->auditLogService->record(
                action: 'document.uploaded',
                user: $actor,
                cabinet: $owner->cabinet,
                auditable: $document,
                metadata: [
                    'client_id' => $owner instanceof Client ? $owner->id : null,
                    'dossier_id' => $owner instanceof Dossier ? $owner->id : null,
                ],
            );

            return $document;
        });
    }

    public function download(Document $document, User $actor): StreamedResponse
    {
        $this->auditLogService->record(
            action: 'document.downloaded',
            user: $actor,
            cabinet: $document->cabinet,
            auditable: $document,
            metadata: [
                'client_id' => $document->client_id,
                'dossier_id' => $document->dossier_id,
            ],
        );

        return Storage::disk('local')->download(
            $document->chemin_acces,
            $document->nom_original,
        );
    }

    public function delete(Document $document, User $actor): void
    {
        DB::transaction(function () use ($document, $actor): void {
            $document->delete();

            $this->auditLogService->record(
                action: 'document.deleted',
                user: $actor,
                cabinet: $document->cabinet,
                auditable: $document,
                metadata: [
                    'client_id' => $document->client_id,
                    'dossier_id' => $document->dossier_id,
                ],
            );
        });
    }

    private function storageDirectory(Client|Dossier $owner): string
    {
        if ($owner instanceof Client) {
            return 'client-documents/'.$owner->cabinet_id.'/'.$owner->id;
        }

        return 'dossier-documents/'.$owner->cabinet_id.'/'.$owner->id;
    }

    private function mimeType(UploadedFile $file): string
    {
        try {
            return $file->getMimeType() ?: 'application/octet-stream';
        } catch (Throwable) {
            return 'application/octet-stream';
        }
    }

    private function sizeInBytes(UploadedFile $file): int
    {
        try {
            return (int) ($file->getSize() ?: 0);
        } catch (Throwable) {
            $realPath = $file->getRealPath();

            if (is_string($realPath) && is_file($realPath)) {
                return (int) (filesize($realPath) ?: 0);
            }

            return 0;
        }
    }
}
