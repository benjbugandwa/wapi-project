<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\ClientTypeEnum;

final readonly class SavePartieAdverseData
{
    public function __construct(
        public ClientTypeEnum $typePartie,
        public string $nom,
        public ?string $prenom = null,
        public ?string $email = null,
        public ?string $telephone = null,
        public ?string $adresse = null,
        public ?string $avocatNom = null,
        public ?string $avocatEmail = null,
        public ?string $notes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        return [
            'type_partie' => $this->typePartie,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'avocat_nom' => $this->avocatNom,
            'avocat_email' => $this->avocatEmail,
            'notes' => $this->notes,
        ];
    }
}
