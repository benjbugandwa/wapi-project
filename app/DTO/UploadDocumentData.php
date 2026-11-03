<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Client;
use App\Models\Dossier;
use Illuminate\Http\UploadedFile;

final readonly class UploadDocumentData
{
    public function __construct(
        public Client|Dossier $owner,
        public UploadedFile $file,
        public string $categorie,
        public bool $estConfidentiel = true,
    ) {}
}
