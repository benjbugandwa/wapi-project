<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\ClientTypeEnum;

final readonly class UpdateClientData
{
    public function __construct(
        public ClientTypeEnum $typeClient,
        public string $nom,
        public ?string $prenom = null,
        public ?string $email = null,
        public ?string $telephonePrincipal = null,
        public ?string $adresseRue = null,
        public ?string $ville = null,
        public ?string $numeroIdentification = null,
        public ?string $representantLegal = null,
        public ?string $notesInternes = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toModelAttributes(): array
    {
        return [
            'type_client' => $this->typeClient,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone_principal' => $this->telephonePrincipal,
            'adresse_rue' => $this->adresseRue,
            'ville' => $this->ville,
            'numero_identification' => $this->numeroIdentification,
            'representant_legal' => $this->representantLegal,
            'notes_internes' => $this->notesInternes,
        ];
    }
}
