<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\UploadClientDocumentData;
use App\DTO\UploadDocumentData;
use App\Models\Document;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientDocumentService
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {}

    public function upload(UploadClientDocumentData $data, User $actor): Document
    {
        return $this->documentService->upload(new UploadDocumentData(
            owner: $data->client,
            file: $data->file,
            categorie: $data->categorie,
            estConfidentiel: $data->estConfidentiel,
        ), $actor);
    }

    public function download(Document $document, User $actor): StreamedResponse
    {
        return $this->documentService->download($document, $actor);
    }

    public function delete(Document $document, User $actor): void
    {
        $this->documentService->delete($document, $actor);
    }
}
