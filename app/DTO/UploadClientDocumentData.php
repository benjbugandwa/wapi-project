<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Client;
use Illuminate\Http\UploadedFile;

final readonly class UploadClientDocumentData
{
    public function __construct(
        public Client $client,
        public UploadedFile $file,
        public string $categorie,
        public bool $estConfidentiel = true,
    ) {}
}
